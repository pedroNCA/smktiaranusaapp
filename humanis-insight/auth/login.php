<?php
session_start();
// Hubungkan database dan konfigurasi
require_once '../config/database.php';
require_once '../includes/app_config.php'; // Mengambil data sekolah dari db

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'Humanis Insight') ?></title>

    <!-- Favicon Dinamis -->
    <link rel="icon" type="image/png" href="../assets/img/<?= htmlspecialchars($app_data['favicon'] ?? 'favicon.ico') ?>" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        @theme { --color-primary: #1e3a8a; --font-sans: 'Inter', sans-serif; --font-display: 'Lexend', sans-serif; }
        body { font-family: var(--font-sans); }
    </style>
</head>

<body class="bg-white min-h-screen flex antialiased">

    <!-- Form Login (Kiri) -->
    <div class="flex flex-1 flex-col justify-center px-4 sm:px-6 lg:flex-none lg:w-1/2 lg:px-20 xl:px-24 py-12">
        <div class="mx-auto w-full max-w-sm lg:w-96">

            <!-- Logo & Brand Dinamis -->
            <div class="flex items-center gap-3 mb-10">
                <img class="h-10 w-auto" src="../assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo Sekolah" onerror="this.style.display='none'">
                <span class="font-display text-xl font-bold text-slate-900"><?= htmlspecialchars($app_data['nama_sekolah'] ?? 'Humanis Insight') ?></span>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-900">Masuk ke akun Anda</h2>
                <p class="mt-2 text-sm text-gray-500">Gunakan kredensial yang telah diberikan oleh operator sekolah</p>
            </div>

            <!-- Pesan Error -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mt-6 p-3 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">Username atau password salah.</div>
            <?php endif; ?>

            <div class="mt-8">
                <form action="../actions/auth_login.php" method="POST" class="space-y-6">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-1">Username</label>
                        <input name="username" type="text" required
                            class="block w-full rounded-lg border border-slate-300 py-3 px-4 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>

                    <!-- Password dengan Toggle -->
                    <div x-data="{ show: false }">
                        <label class="block text-sm font-medium text-gray-900 mb-1">Password</label>
                        <div class="relative">
                            <input name="password" :type="show ? 'text' : 'password'" required
                                class="block w-full rounded-lg border border-slate-300 py-3 px-4 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-all pr-10">

                            <!-- Tombol Mata dengan Ikon SVG -->
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <!-- Ikon Mata Terbuka (Muncul saat show = false) -->
                                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <!-- Ikon Mata Tertutup (Muncul saat show = true) -->
                                <svg x-show="show" style="display:none" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full flex justify-center rounded-full bg-primary py-3 text-sm font-semibold text-white hover:bg-blue-800 transition-all shadow-md">
                        Masuk &rarr;
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <a href="../index.php" class="text-sm font-medium text-gray-500 hover:text-gray-900">&larr; Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Gradient (Kanan) -->
    <div class="hidden lg:flex lg:flex-1 w-full bg-slate-900 relative items-center justify-center p-12">
        <div class="text-white text-center space-y-4">
            <h2 class="text-4xl font-bold font-display"><?= htmlspecialchars($app_data['nama_sekolah'] ?? 'Humanis Insight') ?></h2>
            <p class="text-blue-200"><?= htmlspecialchars($app_data['slogan'] ?? 'Sistem Manajemen Kinerja Profesional') ?></p>
        </div>
    </div>

</body>

</html>