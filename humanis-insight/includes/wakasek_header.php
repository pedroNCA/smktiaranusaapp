<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Karena file ini ada di folder includes/, akses config dan app_config perlu naik satu tingkat
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/app_config.php'; // Mengambil data sekolah dari db

// Proteksi Halaman: Pastikan hanya Wakil Kepala yang bisa mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Wakil Kepala') {
    header("Location: ../../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Wakil Kepala - <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'Humanis Insight') ?></title>

    <!-- Favicon Dinamis (Menyesuaikan letak folder assets dari wakasek) -->
    <link rel="icon" type="image/png" href="../../assets/img/<?= htmlspecialchars($app_data['favicon'] ?? 'favicon.ico') ?>" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind v4 & Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        @theme {
            --color-primary: #1e3a8a;
            --font-sans: 'Inter', sans-serif;
            --font-display: 'Lexend', sans-serif;
        }
        body { font-family: var(--font-sans); }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: var(--font-display); }
    </style>
</head>

<body class="h-full overflow-hidden" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen bg-slate-50">