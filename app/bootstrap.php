<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('Europe/Warsaw');

require __DIR__ . '/../config.php';
require __DIR__ . '/functions.php';

$daysOffConfig = require __DIR__ . '/../wolne.php';
$daysOff = array_fill_keys(array_keys($daysOffConfig['weekends'] + $daysOffConfig['holidays']), true);