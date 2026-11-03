<?php

$env = __DIR__ . '/../.env';

if (!file_exists($env)) {
    throw new Exception('.env tidak ditemukan.');
}

$lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) {
        continue;
    }

    if (!str_contains($line, '=')) {
        continue;
    }

    [$name, $value] = explode('=', $line, 2);

    $_ENV[trim($name)] = trim($value);
}
