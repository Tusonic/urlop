<?php
declare(strict_types=1);

$leaveRequests = [];
$usedLeaveDays = 0;
$remainingLeaveDays = 0;
$calendarEvents = [];
$adminEmployees = [];
$adminVacationRequests = [];
$adminCalendarEvents = [];
$selectedEmployee = null;
$selectedEmployeeRequests = [];
$selectedEmployeeBuckets = ['past' => [], 'current' => [], 'future' => []];
$employeeScheduleMonth = parseMonthInput((string) ($_GET['schedule_month'] ?? date('Y-m'))) ?: new DateTimeImmutable('first day of this month');
$employeeScheduleDays = [];
$employeeScheduleHours = [];
$employeeScheduleMonthStatus = 'draft';
$employeeScheduleApprovedAt = null;
$adminScheduleMonth = parseMonthInput((string) ($_GET['schedule_month'] ?? date('Y-m'))) ?: new DateTimeImmutable('first day of this month');
$adminScheduleEmployeeId = filter_input(INPUT_GET, 'schedule_employee_id', FILTER_VALIDATE_INT) ?: 0;
$adminScheduleRows = [];
$adminScheduleTotals = [];
$adminScheduleCalendarDays = [];
$adminScheduleByDate = [];
$adminScheduleMonthStatuses = [];

if ($employee && !$isAdmin) {
    $usedStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(days), 0)
         FROM vacation_requests
         WHERE employee_id = :employee_id
           AND status IN (:pending_status, :approved_status)'
    );
    $usedStmt->execute([
        'employee_id' => (int) $employee['id'],
        'pending_status' => STATUS_PENDING,
        'approved_status' => STATUS_APPROVED,
    ]);
    $usedLeaveDays = (int) $usedStmt->fetchColumn();
    $remainingLeaveDays = max(0, (int) $employee['annual_leave_days'] - $usedLeaveDays);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_leave') {
        $startDate = parseDateInput((string) ($_POST['start_date'] ?? ''));
        $endDate = parseDateInput((string) ($_POST['end_date'] ?? ''));
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if (!$startDate || !$endDate) {
            $errors[] = 'Podaj poprawny zakres dat.';
        } elseif ($startDate > $endDate) {
            $errors[] = 'Data rozpoczęcia nie może być późniejsza niż data zakończenia.';
        } elseif (mb_strlen($comment) > 1000) {
            $errors[] = 'Komentarz może mieć maksymalnie 1000 znaków.';
        } else {
            $days = countVacationDays($startDate, $endDate, $daysOff);

            if ($days === 0) {
                $errors[] = 'Wybrany zakres nie zawiera żadnego dnia roboczego.';
            } elseif ($days > $remainingLeaveDays) {
                $errors[] = 'Wybrany zakres przekracza liczbę pozostałych dni urlopu.';
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO vacation_requests (employee_id, start_date, end_date, days, comment, status)
                     VALUES (:employee_id, :start_date, :end_date, :days, :comment, :status)'
                );
                $insertStmt->execute([
                    'employee_id' => (int) $employee['id'],
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days' => $days,
                    'comment' => $comment !== '' ? $comment : null,
                    'status' => STATUS_PENDING,
                ]);
                redirectToHome();
            }
        }
    }

    $requestsStmt = $pdo->prepare(
        'SELECT *
         FROM vacation_requests
         WHERE employee_id = :employee_id
         ORDER BY start_date DESC, id DESC'
    );
    $requestsStmt->execute(['employee_id' => (int) $employee['id']]);
    $leaveRequests = $requestsStmt->fetchAll();

    foreach ($leaveRequests as $request) {
        $exclusiveEndDate = (new DateTimeImmutable($request['end_date']))->modify('+1 day');
        $calendarEvents[] = [
            'title' => statusLabel($request['status']) . ' (' . $request['days'] . ' dni)',
            'start' => $request['start_date'],
            'end' => $exclusiveEndDate->format('Y-m-d'),
            'color' => calendarColor($request['status']),
            'textColor' => $request['status'] === STATUS_PENDING ? '#212529' : '#ffffff',
        ];
    }

    if ((int) ($employee['harmonogram'] ?? 0) === 1) {
        $employeeScheduleDays = monthDays($employeeScheduleMonth);
        $monthStatusStmt = $pdo->prepare(
            'SELECT status, approved_at
             FROM work_schedule_months
             WHERE employee_id = :employee_id AND schedule_month = :schedule_month'
        );
        $monthStatusStmt->execute([
            'employee_id' => (int) $employee['id'],
            'schedule_month' => $employeeScheduleMonth->format('Y-m-01'),
        ]);
        $monthStatus = $monthStatusStmt->fetch();

        if ($monthStatus) {
            $employeeScheduleMonthStatus = $monthStatus['status'];
            $employeeScheduleApprovedAt = $monthStatus['approved_at'];
        }

        $scheduleStmt = $pdo->prepare(
            'SELECT work_date, hours
             FROM work_schedules
             WHERE employee_id = :employee_id
               AND work_date BETWEEN :month_start AND :month_end'
        );
        $scheduleStmt->execute([
            'employee_id' => (int) $employee['id'],
            'month_start' => $employeeScheduleMonth->modify('first day of this month')->format('Y-m-d'),
            'month_end' => $employeeScheduleMonth->modify('last day of this month')->format('Y-m-d'),
        ]);

        foreach ($scheduleStmt->fetchAll() as $row) {
            $employeeScheduleHours[$row['work_date']] = $row['hours'];
        }
    }
}

if ($employee && $isAdmin) {
    $employeesStmt = $pdo->prepare(
        "SELECT
            e.*,
            COALESCE(SUM(CASE WHEN vr.status = 'zaakceptowany' THEN vr.days ELSE 0 END), 0) AS used_days,
            COALESCE(SUM(CASE WHEN vr.status = 'oczekujący' THEN vr.days ELSE 0 END), 0) AS pending_days
         FROM employees e
         LEFT JOIN vacation_requests vr ON vr.employee_id = e.id
         GROUP BY e.id, e.first_name, e.last_name, e.pin_hash, e.role, e.harmonogram, e.annual_leave_days, e.created_at
         ORDER BY e.role DESC, e.last_name, e.first_name"
    );
    $employeesStmt->execute();
    $adminEmployees = $employeesStmt->fetchAll();

    $requestsStmt = $pdo->prepare(
        'SELECT vr.*, e.first_name, e.last_name
         FROM vacation_requests vr
         INNER JOIN employees e ON e.id = vr.employee_id
         ORDER BY vr.start_date DESC, vr.id DESC'
    );
    $requestsStmt->execute();
    $adminVacationRequests = $requestsStmt->fetchAll();

    foreach ($adminVacationRequests as $request) {
        $exclusiveEndDate = (new DateTimeImmutable($request['end_date']))->modify('+1 day');
        $employeeName = $request['first_name'] . ' ' . $request['last_name'];
        $adminCalendarEvents[] = [
            'id' => (int) $request['id'],
            'title' => $employeeName . ' - ' . $request['status'],
            'start' => $request['start_date'],
            'end' => $exclusiveEndDate->format('Y-m-d'),
            'color' => calendarColor($request['status']),
            'textColor' => $request['status'] === STATUS_PENDING ? '#212529' : '#ffffff',
            'extendedProps' => [
                'requestId' => (int) $request['id'],
                'employeeId' => (int) $request['employee_id'],
                'employee' => $employeeName,
                'startDate' => $request['start_date'],
                'endDate' => $request['end_date'],
                'days' => (int) $request['days'],
                'statusRaw' => $request['status'],
                'status' => statusLabel($request['status']),
                'comment' => $request['comment'] ?: '-',
                'createdAt' => $request['created_at'],
            ],
        ];
    }

    $selectedEmployeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT);
    if ($selectedEmployeeId) {
        $selectedStmt = $pdo->prepare(
            "SELECT
                e.*,
                COALESCE(SUM(CASE WHEN vr.status = 'zaakceptowany' THEN vr.days ELSE 0 END), 0) AS used_days,
                COALESCE(SUM(CASE WHEN vr.status = 'oczekujący' THEN vr.days ELSE 0 END), 0) AS pending_days
             FROM employees e
             LEFT JOIN vacation_requests vr ON vr.employee_id = e.id
             WHERE e.id = :id
             GROUP BY e.id, e.first_name, e.last_name, e.pin_hash, e.role, e.harmonogram, e.annual_leave_days, e.created_at"
        );
        $selectedStmt->execute(['id' => $selectedEmployeeId]);
        $selectedEmployee = $selectedStmt->fetch() ?: null;

        if ($selectedEmployee) {
            $historyStmt = $pdo->prepare(
                'SELECT *
                 FROM vacation_requests
                 WHERE employee_id = :employee_id
                 ORDER BY start_date DESC, id DESC'
            );
            $historyStmt->execute(['employee_id' => $selectedEmployeeId]);
            $selectedEmployeeRequests = $historyStmt->fetchAll();

            foreach ($selectedEmployeeRequests as $request) {
                $selectedEmployeeBuckets[vacationPeriod($request['start_date'], $request['end_date'], $today)][] = $request;
            }
        }
    }

    $scheduleSql = 'SELECT ws.*, e.first_name, e.last_name
                    FROM work_schedules ws
                    INNER JOIN employees e ON e.id = ws.employee_id
                    WHERE ws.work_date BETWEEN :month_start AND :month_end';
    $scheduleParams = [
        'month_start' => $adminScheduleMonth->modify('first day of this month')->format('Y-m-d'),
        'month_end' => $adminScheduleMonth->modify('last day of this month')->format('Y-m-d'),
    ];

    if ($adminScheduleEmployeeId > 0) {
        $scheduleSql .= ' AND ws.employee_id = :employee_id';
        $scheduleParams['employee_id'] = $adminScheduleEmployeeId;
    }

    $scheduleSql .= ' ORDER BY e.last_name, e.first_name, ws.work_date';
    $scheduleStmt = $pdo->prepare($scheduleSql);
    $scheduleStmt->execute($scheduleParams);
    $adminScheduleRows = $scheduleStmt->fetchAll();

    $statusSql = 'SELECT wsm.*, e.first_name, e.last_name
                  FROM work_schedule_months wsm
                  INNER JOIN employees e ON e.id = wsm.employee_id
                  WHERE wsm.schedule_month = :schedule_month';
    $statusParams = ['schedule_month' => $adminScheduleMonth->format('Y-m-01')];

    if ($adminScheduleEmployeeId > 0) {
        $statusSql .= ' AND wsm.employee_id = :employee_id';
        $statusParams['employee_id'] = $adminScheduleEmployeeId;
    }

    $statusStmt = $pdo->prepare($statusSql);
    $statusStmt->execute($statusParams);

    foreach ($statusStmt->fetchAll() as $row) {
        $adminScheduleMonthStatuses[(int) $row['employee_id']] = $row;
    }

    foreach ($adminScheduleRows as $row) {
        $employeeKey = $row['employee_id'];
        if (!isset($adminScheduleTotals[$employeeKey])) {
            $adminScheduleTotals[$employeeKey] = [
                'employee' => $row['first_name'] . ' ' . $row['last_name'],
                'hours' => 0.0,
            ];
        }
        $adminScheduleTotals[$employeeKey]['hours'] += (float) $row['hours'];
        $adminScheduleByDate[$row['work_date']][] = $row;
    }

    $adminScheduleCalendarDays = monthDays($adminScheduleMonth);
}

$requestedTab = $_GET['tab'] ?? 'employees';
$activeTab = in_array($requestedTab, ['employees', 'requests', 'calendar', 'schedules'], true) ? $requestedTab : 'employees';
