<?php
// Podstawowa konfiguracja polaczenia z baza danych MySQL.
// Dostosuj wartosci ponizej do swojej lokalnej konfiguracji XAMPP.

declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbName = 'urlop';
$dbUser = 'urlop';
$dbPass = 'urlop';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Blad polaczenia z baza danych. Sprawdz ustawienia w pliku config.php.');
}
