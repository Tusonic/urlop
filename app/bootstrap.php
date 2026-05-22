<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('Europe/Warsaw');

require __DIR__ . '/../config.php';
require __DIR__ . '/functions.php';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS remote_work_attendances (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        work_date DATE NOT NULL,
        first_punch_at DATETIME NOT NULL,
        last_punch_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_remote_work_attendances_employee
            FOREIGN KEY (employee_id) REFERENCES employees(id)
            ON DELETE CASCADE,
        UNIQUE KEY uq_remote_work_attendances_employee_date (employee_id, work_date),
        INDEX idx_remote_work_attendances_work_date (work_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$daysOffConfig = require __DIR__ . '/../wolne.php';
$daysOff = array_fill_keys(array_keys($daysOffConfig['weekends'] + $daysOffConfig['holidays']), true);
