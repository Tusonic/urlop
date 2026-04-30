<?php
declare(strict_types=1);

const STATUS_PENDING = 'oczekujący';
const STATUS_APPROVED = 'zaakceptowany';
const STATUS_REJECTED = 'odrzucony';
const STATUSES = [STATUS_PENDING, STATUS_APPROVED, STATUS_REJECTED];

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectToHome(array $params = []): void
{
    $query = $params ? '?' . http_build_query($params) : '';
    header('Location: index.php' . $query);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pullFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function parseDateInput(string $date): ?DateTimeImmutable
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date ? $parsed : null;
}

function parseMonthInput(string $month): ?DateTimeImmutable
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m', $month);
    return $parsed && $parsed->format('Y-m') === $month ? $parsed : null;
}

function monthDays(DateTimeImmutable $month): array
{
    $start = $month->modify('first day of this month');
    $end = $month->modify('last day of this month');
    $days = [];

    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        $days[] = $date;
    }

    return $days;
}

function decimalHours(string $value): ?string
{
    $value = str_replace(',', '.', trim($value));

    if ($value === '') {
        return '0.00';
    }

    if (!is_numeric($value)) {
        return null;
    }

    $hours = (float) $value;

    if (floor($hours) !== $hours) {
        return null;
    }

    if ($hours !== 0.0 && ($hours < 1 || $hours > 8)) {
        return null;
    }

    return number_format($hours, 0, '.', '');
}

function countVacationDays(DateTimeImmutable $startDate, DateTimeImmutable $endDate, array $daysOff): int
{
    $days = 0;

    for ($date = $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
        if (!isset($daysOff[$date->format('Y-m-d')])) {
            $days++;
        }
    }

    return $days;
}

function statusLabel(string $status): string
{
    return match ($status) {
        STATUS_APPROVED => 'Zaakceptowany',
        STATUS_REJECTED => 'Odrzucony',
        default => 'Oczekujący',
    };
}

function statusBadgeClass(string $status): string
{
    return match ($status) {
        STATUS_APPROVED => 'text-bg-success',
        STATUS_REJECTED => 'text-bg-danger',
        default => 'text-bg-warning',
    };
}

function calendarColor(string $status): string
{
    return match ($status) {
        STATUS_APPROVED => '#198754',
        STATUS_REJECTED => '#dc3545',
        default => '#ffc107',
    };
}

function vacationPeriod(string $startDate, string $endDate, string $today): string
{
    if ($endDate < $today) {
        return 'past';
    }

    if ($startDate > $today) {
        return 'future';
    }

    return 'current';
}

function fetchCurrentEmployee(PDO $pdo): ?array
{
    if (!isset($_SESSION['employee_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM employees WHERE id = :id');
    $stmt->execute(['id' => (int) $_SESSION['employee_id']]);
    $employee = $stmt->fetch();

    if (!$employee) {
        session_destroy();
        redirectToHome();
    }

    $_SESSION['employee_role'] = $employee['role'];
    return $employee;
}

function requireAdmin(?array $employee): void
{
    if (!$employee || ($_SESSION['employee_role'] ?? '') !== 'admin' || $employee['role'] !== 'admin') {
        http_response_code(403);
        exit('Brak uprawnień.');
    }
}

// Hash PIN-u wygenerujesz np. tak:
// <?php echo password_hash('1234', PASSWORD_DEFAULT);
