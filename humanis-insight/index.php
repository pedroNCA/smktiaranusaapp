<?php
session_start();

// Hubungkan ke database dan ambil konfigurasi sistem dinamis
require_once 'config/database.php';
require_once 'includes/app_config.php'; // Mengambil variabel $app_data

// Menentukan URL Dashboard dinamis sesuai hak akses (Role) jika user sudah login
$dashboard_url = 'auth/login.php';
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'Operator') {
        $dashboard_url = 'pages/operator/dashboard.php';
    } elseif ($role === 'PIC') {
        $dashboard_url = 'pages/pic/dashboard.php';
    } elseif ($role === 'Wakil Kepala') {
        $dashboard_url = 'pages/wakasek/dashboard.php';
    } elseif ($role === 'Kepala Sekolah') {
        $dashboard_url = 'pages/kepsek/dashboard.php';
    } else {
        $dashboard_url = 'pages/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?> - Manajemen Kinerja Sekolah</title>

    <!-- Favicon & Logo Dinamis dari Tabel Pengaturan -->
    <link rel="icon" type="image/png" href="assets/img/<?= htmlspecialchars($app_data['favicon'] ?? 'favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 Browser Build -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Konfigurasi Tema Tailwind v4 -->
    <style type="text/tailwindcss">
        @theme {
            --color-primary: #1e3a8a;
            --color-secondary: #047857;
            --font-sans: 'Inter', sans-serif;
            --font-display: 'Lexend', sans-serif;
        }
        
        body {
            font-family: var(--font-sans);
        }
        h1, h2, h3, h4, h5, h6, .font-display {
            font-family: var(--font-display);
        }
    </style>
</head>

<body class="bg-white text-slate-900 antialiased flex flex-col min-h-screen">

    <!-- Header / Navbar -->
    <header class="py-4 sm:py-6 border-b border-slate-100">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="relative z-50 flex justify-between items-center gap-4">

                <!-- Brand / Logo (Menggunakan min-w-0 agar teks bisa di-truncate di layar HP) -->
                <div class="flex items-center flex-1 min-w-0 pr-2">
                    <a href="index.php" class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <img class="h-8 sm:h-10 w-auto object-contain shrink-0" src="assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo Institusi" onerror="this.style.display='none'">
                        <span class="font-display text-sm sm:text-xl font-bold text-slate-900 truncate">
                            <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?>
                        </span>
                    </a>
                </div>

                <!-- Tombol Aksi (Menggunakan shrink-0 agar tidak terdorong/terpotong) -->
                <div class="flex items-center shrink-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= $dashboard_url ?>" class="group inline-flex items-center justify-center rounded-full py-2 px-4 sm:px-5 text-xs sm:text-sm font-semibold bg-primary text-white hover:bg-blue-800 transition-colors shadow-sm whitespace-nowrap">
                            <span>Dashboard Saya</span>
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="group inline-flex items-center justify-center rounded-full py-2 px-4 sm:px-5 text-xs sm:text-sm font-semibold bg-slate-900 text-white hover:bg-slate-700 transition-colors shadow-sm whitespace-nowrap">
                            <span>Masuk Sistem</span>
                        </a>
                    <?php endif; ?>
                </div>

            </nav>
        </div>
    </header>

    <main class="flex-grow">
        <!-- Hero Section: Tentang Humanis Insight -->
        <section class="pt-20 pb-16 text-center lg:pt-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <h1 class="mx-auto max-w-4xl font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-6xl">
                    Humanis<span class="text-blue-600"> Insight</span>
                </h1>

                <p class="mx-auto mt-6 max-w-3xl text-lg tracking-tight text-slate-600 leading-relaxed">
                    Sistem penjaminan mutu dan manajemen kinerja institusi berbasis data untuk mewujudkan tata kelola sekolah yang transparan, akuntabel, dan berkelanjutan.
                </p>

                <!-- Tombol Hero responsif: flex-col di HP, flex-row di desktop -->
                <div class="mt-10 flex flex-col sm:flex-row justify-center gap-3 sm:gap-4 items-center">
                    <a href="<?= isset($_SESSION['user_id']) ? $dashboard_url : 'auth/login.php' ?>" class="w-full sm:w-auto group inline-flex items-center justify-center rounded-full py-3 px-6 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-700 shadow-md transition-all">
                        Mulai Refleksi & Pelaporan
                    </a>
                    <a href="#humanis" class="w-full sm:w-auto group inline-flex items-center justify-center rounded-full py-3 px-6 text-sm font-semibold ring-1 ring-slate-200 text-slate-700 hover:text-slate-900 hover:ring-slate-300 transition-all">
                        Pelajari Nilai HUMANIS
                    </a>
                </div>
            </div>
        </section>

        <!-- Section Khusus: Nilai HUMANIS -->
        <section id="humanis" class="py-20 bg-slate-50 border-y border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <div class="bg-primary text-white text-center py-4 px-6 rounded-2xl shadow-sm mb-10">
                    <h2 class="font-display text-xl sm:text-2xl font-bold tracking-wider uppercase">Nilai HUMANIS</h2>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Setiap indikator kinerja selaras dengan nilai-nilai HUMANIS</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-600 text-white font-bold text-lg shrink-0 shadow-inner">H</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Harmoni</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Membangun hubungan yang selaras dan kolaboratif</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white font-bold text-lg shrink-0 shadow-inner">U</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Unggul</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Berorientasi pada kualitas dan prestasi</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold text-lg shrink-0 shadow-inner">M</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Mulia</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Berakhlak, berintegritas, dan berkarakter</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-white font-bold text-lg shrink-0 shadow-inner">A</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Akses Terbuka</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Terbuka terhadap informasi dan kesempatan</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-600 text-white font-bold text-lg shrink-0 shadow-inner">N</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Nyaman</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Menciptakan lingkungan belajar yang aman dan nyaman</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-600 text-white font-bold text-lg shrink-0 shadow-inner">I</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Inklusi</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Menghargai keberagaman dan memberi kesempatan untuk semua</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex items-start gap-4 md:col-span-2 md:max-w-xl md:mx-auto md:w-full">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-600 text-white font-bold text-lg shrink-0 shadow-inner">S</div>
                        <div>
                            <h3 class="font-display text-base font-bold text-slate-900">Sejahtera</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Berorientasi pada kesejahteraan seluruh warga sekolah</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Kompetensi Keahlian / Jurusan -->
        <section id="jurusan" class="py-20 bg-white">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-12">
                    <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900">Kompetensi Keahlian Unggulan</h2>
                    <p class="mt-2 text-sm text-slate-600">Program keahlian utama yang diselenggarakan di lingkungan <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?>.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($app_data['kompetensi_keahlian_1'])): ?>
                        <div class="bg-slate-50 rounded-2xl p-8 shadow-sm ring-1 ring-slate-200 flex items-center gap-5">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shrink-0 font-bold text-xl">
                                01
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-blue-600">Program Keahlian</span>
                                <h3 class="font-display text-xl font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($app_data['kompetensi_keahlian_1']) ?></h3>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($app_data['kompetensi_keahlian_2'])): ?>
                        <div class="bg-slate-50 rounded-2xl p-8 shadow-sm ring-1 ring-slate-200 flex items-center gap-5">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shrink-0 font-bold text-xl">
                                02
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Program Keahlian</span>
                                <h3 class="font-display text-xl font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($app_data['kompetensi_keahlian_2']) ?></h3>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 4 Pilar Section -->
        <section id="fitur" class="py-20 bg-slate-50 border-y border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">4 Pilar Humanis Insight</h2>
                    <p class="mt-4 text-lg text-slate-700">Prinsip dasar dalam meningkatkan mutu pendidikan dan tata kelola sekolah yang terintegrasi.</p>
                </div>

                <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-6 sm:gap-8 lg:mt-20 lg:max-w-none lg:grid-cols-4">
                    <div class="flex flex-col rounded-3xl bg-slate-50 px-6 py-8 shadow-sm ring-1 ring-slate-200/60 hover:shadow-md transition-shadow">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 font-display text-lg font-semibold text-slate-900">Berbasis Data</h3>
                        <p class="mt-2 text-sm text-slate-600">Keputusan diambil berdasarkan data kinerja yang akurat, terukur, dan real-time.</p>
                    </div>

                    <div class="flex flex-col rounded-3xl bg-slate-50 px-6 py-8 shadow-sm ring-1 ring-slate-200/60 hover:shadow-md transition-shadow">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 font-display text-lg font-semibold text-slate-900">Transparan & Akuntabel</h3>
                        <p class="mt-2 text-sm text-slate-600">Proses penilaian yang jelas, adil, dan dapat dipertanggungjawabkan secara komprehensif.</p>
                    </div>

                    <div class="flex flex-col rounded-3xl bg-slate-50 px-6 py-8 shadow-sm ring-1 ring-slate-200/60 hover:shadow-md transition-shadow">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 font-display text-lg font-semibold text-slate-900">Kolaboratif</h3>
                        <p class="mt-2 text-sm text-slate-600">Melibatkan semua warga sekolah dalam siklus refleksi dan peningkatan mutu berkelanjutan.</p>
                    </div>

                    <div class="flex flex-col rounded-3xl bg-slate-50 px-6 py-8 shadow-sm ring-1 ring-slate-200/60 hover:shadow-md transition-shadow">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 font-display text-lg font-semibold text-slate-900">Aman & Terpusat</h3>
                        <p class="mt-2 text-sm text-slate-600">Data tersimpan dengan enkripsi di server terpusat dan dapat diakses sesuai hak akses Role-Based.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Dinamis -->
    <footer class="py-20 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-between md:flex-row gap-4">
            <div class="flex items-center gap-3 text-slate-900">
                <img class="h-8 w-auto object-contain grayscale opacity-70" src="assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo Institusi" onerror="this.style.display='none'">
                <span class="font-display font-semibold"><?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?></span>
            </div>

            <div class="text-center md:text-right text-xs text-slate-500 space-y-1">
                <p>&copy; <?php echo date('Y'); ?> <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?></p>
                <p>Telp: <?= htmlspecialchars($app_data['no_telepon'] ?? '-') ?> | Email: <?= htmlspecialchars($app_data['email_sekolah'] ?? '-') ?></p>
            </div>
        </div>
    </footer>

</body>

</html>