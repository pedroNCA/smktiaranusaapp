<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/env.php';

date_default_timezone_set($_ENV['APP_TIMEZONE']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    $_ENV['DB_DATABASE']
);

$conn->set_charset('utf8mb4');
