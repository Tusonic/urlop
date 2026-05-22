<?php
declare(strict_types=1);

$errors = [];
$flashMessages = pullFlash();
$today = (new DateTimeImmutable('today'))->format('Y-m-d');

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    redirectToHome();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $pin = trim((string) ($_POST['pin'] ?? ''));

    if ($pin === '') {
        $errors[] = 'Wpisz PIN.';
    } else {
        $stmt = $pdo->prepare('SELECT id, role FROM employees WHERE pin_code = :pin_code');
        $stmt->execute(['pin_code' => $pin]);
        $matchedEmployee = $stmt->fetch() ?: null;

        if ($matchedEmployee) {
            session_regenerate_id(true);
            $_SESSION['employee_id'] = (int) $matchedEmployee['id'];
            $_SESSION['employee_role'] = $matchedEmployee['role'];
            redirectToHome();
        }

        $errors[] = 'Nieprawidłowy PIN.';
    }
}

$employee = fetchCurrentEmployee($pdo);
$isAdmin = $employee && ($_SESSION['employee_role'] ?? '') === 'admin' && $employee['role'] === 'admin';

if ($employee && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'remote_work_punch') {
        if ($isAdmin) {
            http_response_code(403);
            exit('Brak uprawnień.');
        }

        $workDate = date('Y-m-d');
        $punchTime = date('Y-m-d H:i:s');

        $punchStmt = $pdo->prepare(
            'INSERT INTO remote_work_attendances (employee_id, work_date, first_punch_at)
             VALUES (:employee_id, :work_date, :punch_time)
             ON DUPLICATE KEY UPDATE last_punch_at = VALUES(first_punch_at)'
        );
        $punchStmt->execute([
            'employee_id' => (int) $employee['id'],
            'work_date' => $workDate,
            'punch_time' => $punchTime,
        ]);

        flash(
            'success',
            $punchStmt->rowCount() === 1
                ? 'Rozpoczęcie pracy zdalnej zostało zapisane.'
                : 'Zakończenie pracy zdalnej zostało zapisane.'
        );

        redirectToHome(['employee_tab' => 'rcp']);
    }

    if ($action === 'create_employee') {
        requireAdmin($employee);

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $pinCode = trim((string) ($_POST['pin_code'] ?? ''));
        $annualLeaveDays = filter_input(INPUT_POST, 'annual_leave_days', FILTER_VALIDATE_INT);
        $role = (string) ($_POST['role'] ?? 'employee');
        $harmonogram = (string) ($_POST['harmonogram'] ?? '0');

        if ($firstName === '' || $lastName === '' || $pinCode === '') {
            flash('danger', 'Imię, nazwisko i PIN są wymagane.');
        } elseif ($annualLeaveDays === false || $annualLeaveDays === null || $annualLeaveDays < 0 || $annualLeaveDays > 365) {
            flash('danger', 'Podaj poprawny limit urlopu.');
        } elseif (!in_array($role, ['employee', 'admin'], true) || !in_array($harmonogram, ['0', '1'], true)) {
            flash('danger', 'Nieprawidłowe dane pracownika.');
        } else {
            $pinStmt = $pdo->prepare('SELECT id FROM employees WHERE pin_code = :pin_code');
            $pinStmt->execute(['pin_code' => $pinCode]);

            if ($pinStmt->fetch()) {
                flash('danger', 'Taki PIN już istnieje. Nadaj inny numer PIN.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO employees (first_name, last_name, pin_code, role, harmonogram, annual_leave_days)
                     VALUES (:first_name, :last_name, :pin_code, :role, :harmonogram, :annual_leave_days)'
                );
                $stmt->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'pin_code' => $pinCode,
                    'role' => $role,
                    'harmonogram' => (int) $harmonogram,
                    'annual_leave_days' => $annualLeaveDays,
                ]);
                flash('success', 'Nowe konto pracownika zostało utworzone.');
            }
        }

        redirectToHome(['tab' => 'employees']);
    }

    if ($action === 'update_employee') {
        requireAdmin($employee);

        $targetEmployeeId = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $pinCode = trim((string) ($_POST['pin_code'] ?? ''));
        $annualLeaveDays = filter_input(INPUT_POST, 'annual_leave_days', FILTER_VALIDATE_INT);
        $role = (string) ($_POST['role'] ?? 'employee');
        $harmonogram = (string) ($_POST['harmonogram'] ?? '0');

        if (!$targetEmployeeId || $firstName === '' || $lastName === '' || $pinCode === '') {
            flash('danger', 'Imię, nazwisko i PIN są wymagane.');
        } elseif ($annualLeaveDays === false || $annualLeaveDays === null || $annualLeaveDays < 0 || $annualLeaveDays > 365) {
            flash('danger', 'Podaj poprawny limit urlopu.');
        } elseif (!in_array($role, ['employee', 'admin'], true) || !in_array($harmonogram, ['0', '1'], true)) {
            flash('danger', 'Nieprawidłowe dane pracownika.');
        } else {
            $pinStmt = $pdo->prepare('SELECT id FROM employees WHERE pin_code = :pin_code AND id <> :id');
            $pinStmt->execute([
                'pin_code' => $pinCode,
                'id' => $targetEmployeeId,
            ]);

            if ($pinStmt->fetch()) {
                flash('danger', 'Taki PIN już istnieje. Nadaj inny numer PIN.');
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE employees
                     SET first_name = :first_name,
                         last_name = :last_name,
                         pin_code = :pin_code,
                         role = :role,
                         harmonogram = :harmonogram,
                         annual_leave_days = :annual_leave_days
                     WHERE id = :id'
                );
                $stmt->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'pin_code' => $pinCode,
                    'role' => $role,
                    'harmonogram' => (int) $harmonogram,
                    'annual_leave_days' => $annualLeaveDays,
                    'id' => $targetEmployeeId,
                ]);

                if ($targetEmployeeId === (int) $employee['id']) {
                    $_SESSION['employee_role'] = $role;
                }

                flash('success', 'Dane pracownika zostały zapisane.');
            }
        }

        redirectToHome(['tab' => 'employees']);
    }

    if ($action === 'update_vacation_status') {
        requireAdmin($employee);
        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $newStatus = (string) ($_POST['status'] ?? '');

        if (!$requestId || !in_array($newStatus, [STATUS_APPROVED, STATUS_REJECTED], true)) {
            flash('danger', 'Nieprawidłowa akcja administracyjna.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE vacation_requests
                 SET status = :status
                 WHERE id = :id AND status = :pending_status'
            );
            $stmt->execute([
                'status' => $newStatus,
                'id' => $requestId,
                'pending_status' => STATUS_PENDING,
            ]);
            flash('success', 'Status wniosku został zaktualizowany.');
        }

        redirectToHome(['tab' => 'requests']);
    }

    if ($action === 'edit_vacation') {
        requireAdmin($employee);
        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $startDate = parseDateInput((string) ($_POST['start_date'] ?? ''));
        $endDate = parseDateInput((string) ($_POST['end_date'] ?? ''));
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $status = (string) ($_POST['status'] ?? '');

        if (!$requestId) {
            flash('danger', 'Nieprawidłowe ID wniosku.');
        } elseif (!$startDate || !$endDate) {
            flash('danger', 'Data od i data do są wymagane.');
        } elseif ($endDate < $startDate) {
            flash('danger', 'Data do nie może być wcześniejsza niż data od.');
        } elseif (!in_array($status, STATUSES, true)) {
            flash('danger', 'Wybrano nieprawidłowy status.');
        } elseif (mb_strlen($comment) > 1000) {
            flash('danger', 'Komentarz może mieć maksymalnie 1000 znaków.');
        } else {
            $days = countVacationDays($startDate, $endDate, $daysOff);

            if ($days === 0) {
                flash('danger', 'Wybrany zakres nie zawiera żadnego dnia roboczego.');
                redirectToHome(['tab' => 'requests']);
            }

            $stmt = $pdo->prepare(
                'UPDATE vacation_requests
                 SET start_date = :start_date,
                     end_date = :end_date,
                     days = :days,
                     comment = :comment,
                     status = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $days,
                'comment' => $comment !== '' ? $comment : null,
                'status' => $status,
                'id' => $requestId,
            ]);
            flash('success', 'Wniosek urlopowy został zapisany.');
        }

        redirectToHome(['tab' => 'requests']);
    }

    if ($action === 'delete_vacation') {
        requireAdmin($employee);
        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);

        if (!$requestId) {
            flash('danger', 'Nieprawidłowe ID wniosku.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM vacation_requests WHERE id = :id');
            $stmt->execute(['id' => $requestId]);
            flash('success', 'Wniosek urlopowy został usunięty.');
        }

        redirectToHome(['tab' => 'requests']);
    }

    if ($action === 'toggle_schedule') {
        requireAdmin($employee);
        $targetEmployeeId = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
        $enabled = (string) ($_POST['harmonogram'] ?? '');

        if (!$targetEmployeeId || !in_array($enabled, ['0', '1'], true)) {
            flash('danger', 'Nieprawidłowe dane harmonogramu.');
        } else {
            $stmt = $pdo->prepare('UPDATE employees SET harmonogram = :harmonogram WHERE id = :id');
            $stmt->execute([
                'harmonogram' => (int) $enabled,
                'id' => $targetEmployeeId,
            ]);
            flash('success', 'Ustawienie harmonogramu pracownika zostało zapisane.');
        }

        redirectToHome(['tab' => 'employees']);
    }

    if ($action === 'set_schedule_month_status') {
        requireAdmin($employee);
        $targetEmployeeId = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
        $scheduleMonth = parseMonthInput((string) ($_POST['schedule_month'] ?? ''));
        $status = (string) ($_POST['status'] ?? '');

        if (!$targetEmployeeId || !$scheduleMonth || !in_array($status, ['draft', 'approved'], true)) {
            flash('danger', 'Nieprawidłowe dane akceptacji harmonogramu.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO work_schedule_months (employee_id, schedule_month, status, approved_by, approved_at)
                 VALUES (:employee_id, :schedule_month, :status, :approved_by, :approved_at)
                 ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    approved_by = VALUES(approved_by),
                    approved_at = VALUES(approved_at)'
            );
            $stmt->execute([
                'employee_id' => $targetEmployeeId,
                'schedule_month' => $scheduleMonth->format('Y-m-01'),
                'status' => $status,
                'approved_by' => $status === 'approved' ? (int) $employee['id'] : null,
                'approved_at' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
            ]);

            flash('success', $status === 'approved' ? 'Harmonogram został zaakceptowany i zablokowany.' : 'Harmonogram został odblokowany do edycji.');
        }

        redirectToHome([
            'tab' => 'schedules',
            'schedule_month' => $scheduleMonth ? $scheduleMonth->format('Y-m') : date('Y-m'),
            'schedule_employee_id' => $targetEmployeeId ?: 0,
        ]);
    }

    if ($action === 'save_work_schedule') {
        if ($isAdmin || (int) ($employee['harmonogram'] ?? 0) !== 1) {
            http_response_code(403);
            exit('Brak uprawnień.');
        }

        $scheduleMonth = parseMonthInput((string) ($_POST['schedule_month'] ?? ''));
        $scheduleHours = $_POST['hours'] ?? [];

        if (!$scheduleMonth || !is_array($scheduleHours)) {
            flash('danger', 'Nieprawidłowy miesiąc harmonogramu.');
            redirectToHome(['employee_tab' => 'schedule']);
        }

        $lockStmt = $pdo->prepare(
            'SELECT status
             FROM work_schedule_months
             WHERE employee_id = :employee_id AND schedule_month = :schedule_month'
        );
        $lockStmt->execute([
            'employee_id' => (int) $employee['id'],
            'schedule_month' => $scheduleMonth->format('Y-m-01'),
        ]);

        if ($lockStmt->fetchColumn() === 'approved') {
            flash('danger', 'Ten harmonogram został zaakceptowany przez administratora i nie można go już edytować.');
            redirectToHome([
                'employee_tab' => 'schedule',
                'schedule_month' => $scheduleMonth->format('Y-m'),
            ]);
        }

        $monthStart = $scheduleMonth->modify('first day of this month')->format('Y-m-d');
        $monthEnd = $scheduleMonth->modify('last day of this month')->format('Y-m-d');
        $rowsToInsert = [];

        foreach ($scheduleHours as $date => $hoursValue) {
            $dateObject = parseDateInput((string) $date);
            $hours = decimalHours((string) $hoursValue);

            if (!$dateObject || $dateObject->format('Y-m') !== $scheduleMonth->format('Y-m') || $hours === null) {
                flash('danger', 'Godziny w harmonogramie muszą być pełną liczbą: 0 albo od 1 do 8.');
                redirectToHome([
                    'employee_tab' => 'schedule',
                    'schedule_month' => $scheduleMonth->format('Y-m'),
                ]);
            }

            if ((float) $hours > 0) {
                $rowsToInsert[] = [
                    'work_date' => $dateObject->format('Y-m-d'),
                    'hours' => $hours,
                ];
            }
        }

        $pdo->beginTransaction();
        $deleteStmt = $pdo->prepare(
            'DELETE FROM work_schedules
             WHERE employee_id = :employee_id
               AND work_date BETWEEN :month_start AND :month_end'
        );
        $deleteStmt->execute([
            'employee_id' => (int) $employee['id'],
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
        ]);

        if ($rowsToInsert) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO work_schedules (employee_id, work_date, hours)
                 VALUES (:employee_id, :work_date, :hours)'
            );

            foreach ($rowsToInsert as $row) {
                $insertStmt->execute([
                    'employee_id' => (int) $employee['id'],
                    'work_date' => $row['work_date'],
                    'hours' => $row['hours'],
                ]);
            }
        }

        $pdo->commit();
        flash('success', 'Harmonogram pracy został zapisany.');
        redirectToHome([
            'employee_tab' => 'schedule',
            'schedule_month' => $scheduleMonth->format('Y-m'),
        ]);
    }
}
