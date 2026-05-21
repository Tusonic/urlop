DROP DATABASE IF EXISTS urlop;

CREATE DATABASE urlop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE urlop;

-- UWAGA:
-- Ten plik jest pelnym instalatorem bazy danych od zera.
-- DROP DATABASE usuwa wszystkie istniejace dane w bazie urlop.

CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    pin_code VARCHAR(20) NULL,
    pin_hash VARCHAR(255) NOT NULL,
    role ENUM('employee', 'admin') NOT NULL DEFAULT 'employee',
    harmonogram TINYINT(1) NOT NULL DEFAULT 0,
    annual_leave_days INT UNSIGNED NOT NULL DEFAULT 26,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employees_role (role),
    INDEX idx_employees_harmonogram (harmonogram)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vacation_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days INT UNSIGNED NOT NULL,
    comment TEXT NULL,
    status ENUM('oczekujący', 'zaakceptowany', 'odrzucony') NOT NULL DEFAULT 'oczekujący',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vacation_requests_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id)
        ON DELETE CASCADE,
    INDEX idx_vacation_requests_employee_status (employee_id, status),
    INDEX idx_vacation_requests_dates (start_date, end_date),
    INDEX idx_vacation_requests_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    hours TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_work_schedules_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_work_schedules_hours
        CHECK (hours = 0 OR (hours BETWEEN 1 AND 8)),
    UNIQUE KEY uq_work_schedules_employee_date (employee_id, work_date),
    INDEX idx_work_schedules_date (work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_schedule_months (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    schedule_month DATE NOT NULL,
    status ENUM('draft', 'approved') NOT NULL DEFAULT 'draft',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_work_schedule_months_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_work_schedule_months_approved_by
        FOREIGN KEY (approved_by) REFERENCES employees(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_work_schedule_months_employee_month (employee_id, schedule_month),
    INDEX idx_work_schedule_months_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE remote_work_attendances (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hash PIN-u wygenerujesz w PHP np. tak:
-- <?php echo password_hash('1234', PASSWORD_DEFAULT);
--
-- Przykladowe konta testowe:
-- Jan Kowalski, pracownik: PIN 1234
-- Anna Nowak, pracownik: PIN 5678
-- Piotr Zielinski, pracownik: PIN 2468
-- Admin Urlopowy, administrator: PIN 9999

INSERT INTO employees (first_name, last_name, pin_code, pin_hash, role, harmonogram, annual_leave_days)
VALUES
('Jan', 'Kowalski', '1234', '$2y$10$3MWiZdviQyNqJYaDhIormeKEZmyz1pLnKemlwk.zTGcwxg6jkMl/a', 'employee', 1, 26),
('Anna', 'Nowak', '5678', '$2y$10$F8KK2I1flmkPO9WYb5hPK.AufnNFWtT65wKGI1hC0wh6TxqCl6Q0O', 'employee', 1, 26),
('Piotr', 'Zielinski', '2468', '$2y$10$kiwTqNeixfNgFxsL6BuFFuvbJBdoMaYkyKQ8elcy6L7Oou724hRwi', 'employee', 0, 26),
('Admin', 'Urlopowy', '9999', '$2y$10$3XrarV5l1LgC8rAdcKFGqu2/LPrAlIjplEgkbvBEmoM60JQWcs1le', 'admin', 0, 26);

INSERT INTO vacation_requests (employee_id, start_date, end_date, days, comment, status, created_at)
VALUES
(1, '2026-03-02', '2026-03-06', 5, 'Urlop zimowy', 'zaakceptowany', '2026-02-10 09:15:00'),
(1, '2026-04-27', '2026-04-30', 4, 'Sprawy rodzinne', 'zaakceptowany', '2026-04-20 10:30:00'),
(1, '2026-05-18', '2026-05-22', 5, 'Planowany wypoczynek', 'oczekujący', '2026-04-22 14:05:00'),
(2, '2026-04-01', '2026-04-03', 3, 'Krótki urlop', 'odrzucony', '2026-03-20 08:45:00'),
(2, '2026-04-28', '2026-05-04', 4, 'Wyjazd wakacyjny', 'oczekujący', '2026-04-23 12:10:00'),
(2, '2026-08-03', '2026-08-07', 5, 'Urlop letni', 'zaakceptowany', '2026-04-24 12:10:00'),
(3, '2026-02-16', '2026-02-20', 5, 'Ferie', 'zaakceptowany', '2026-01-25 11:20:00'),
(3, '2026-06-15', '2026-06-19', 5, 'Planowany urlop', 'oczekujący', '2026-04-25 15:40:00'),
(3, '2026-09-14', '2026-09-18', 5, 'Jesienny wyjazd', 'odrzucony', '2026-04-26 16:00:00');

INSERT INTO work_schedules (employee_id, work_date, hours)
VALUES
(1, '2026-04-01', 8),
(1, '2026-04-02', 8),
(1, '2026-04-03', 6),
(1, '2026-04-07', 8),
(1, '2026-04-08', 8),
(1, '2026-04-09', 8),
(1, '2026-04-10', 6),
(2, '2026-04-01', 7),
(2, '2026-04-02', 7),
(2, '2026-04-07', 8),
(2, '2026-04-08', 8),
(2, '2026-04-09', 8),
(2, '2026-04-10', 5);

INSERT INTO work_schedule_months (employee_id, schedule_month, status, approved_by, approved_at)
VALUES
(1, '2026-04-01', 'approved', 4, '2026-04-15 12:00:00'),
(2, '2026-04-01', 'draft', NULL, NULL);
