<?php
// config/env_parser.php

function loadEnv($path) {
    if (!file_exists($path)) {
        die("File .env tidak ditemukan!");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Abaikan baris komentar
        if (strpos(trim($line), '#') === 0) continue;

        // Pisahkan nama variabel dan nilainya
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Masukkan ke environment PHP
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Eksekusi fungsi saat file ini dipanggil, arahkan ke root folder
loadEnv(__DIR__ . '/../.env');
?>