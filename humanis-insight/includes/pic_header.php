<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke database dan ambil konfigurasi sistem (termasuk favicon & nama sekolah)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/app_config.php';

// Validasi Hak Akses Khusus Guru/PIC
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guru/PIC') {
    header("Location: ../../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel PIC - <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'Humanis Insight') ?></title>

    <!-- Favicon Dinamis dari Data yang Di-upload -->
    <link rel="icon" type="image/png" href="../../assets/img/<?= htmlspecialchars($app_data['favicon'] ?? 'favicon.ico') ?>" />

    <!-- Tailwind CSS & Google Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Plus Jakarta Sans"', 'sans-serif'],
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        primary: '#1d4ed8',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">