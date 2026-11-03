<?php
// Pastikan path ke folder vendor benar (mundur 1 folder dari config)
require_once __DIR__ . '/../vendor/autoload.php';

// Inisialisasi Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');

try {
    // Gunakan safeLoad agar aplikasi tidak crash jika file .env tidak ditemukan
    $dotenv->safeLoad();
} catch (Exception $e) {
    // Abaikan jika error (misal: di DirectAdmin environment variable diset dari panel)
}

// Definisikan Konstanta URL Dasar dengan fallback
// Menggunakan BASE_URL sesuai dengan yang kita set di file .env
$baseUrl = $_ENV['BASE_URL'] ?? getenv('BASE_URL') ?? 'http://localhost/humanis-insight';

define('BASE_URL', $baseUrl);

// Tidak perlu menambahkan tag penutup "? >" di akhir file murni PHP