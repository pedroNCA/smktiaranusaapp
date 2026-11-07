<?php
// Pengaturan Zona Waktu Berdasarkan Environment atau Default Jakarta
$timezone = $_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

// Pengaturan Error Reporting Berdasarkan Mode Debug
$debug = filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

return [
    'app_name' => $_ENV['APP_NAME'] ?? 'e-Raport SMK Tiara Nusa',
    'app_url'  => $_ENV['APP_URL'] ?? 'http://localhost/eraport',
    'timezone' => $timezone,
    'debug'    => $debug
];