<?php
// Mencegah akses langsung ke file ini tanpa melalui router index.php
if (!isset($route)) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Statistik Dashboard Landing Page
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

/* Total Siswa */
$totalSiswa = 0;
$q = $conn->query("SELECT COUNT(*) AS total FROM siswa WHERE status='Aktif'");
if ($q) {
    $totalSiswa = (int)$q->fetch_assoc()['total'];
}

/* Hadir */
$hadir = 0;
$q = $conn->query("SELECT COUNT(*) AS total FROM absensi WHERE tanggal='$today' AND status='Hadir'");
if ($q) {
    $hadir = (int)$q->fetch_assoc()['total'];
}

/* Izin */
$izin = 0;
$q = $conn->query("SELECT COUNT(*) AS total FROM absensi WHERE tanggal='$today' AND status='Izin'");
if ($q) {
    $izin = (int)$q->fetch_assoc()['total'];
}

/* Sakit */
$sakit = 0;
$q = $conn->query("SELECT COUNT(*) AS total FROM absensi WHERE tanggal='$today' AND status='Sakit'");
if ($q) {
    $sakit = (int)$q->fetch_assoc()['total'];
}

/* Alfa */
$alfa = 0;
$q = $conn->query("SELECT COUNT(*) AS total FROM absensi WHERE tanggal='$today' AND status='Alfa'");
if ($q) {
    $alfa = (int)$q->fetch_assoc()['total'];
}

/* Tahun Pelajaran Aktif */
$tahunAktif = "-";
$q = $conn->query("SELECT nama_tahun FROM tahun_ajaran WHERE status='Aktif' LIMIT 1");
if ($q && $q->num_rows) {
    $tahunAktif = $q->fetch_assoc()['nama_tahun'];
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTA | Presensi Digital SMK Tiara Nusa</title>
    <meta name="description" content="PRESTA merupakan sistem presensi digital SMK Tiara Nusa yang membantu pengelolaan kehadiran siswa secara cepat, akurat, dan terintegrasi">

    <!-- FAVICON -->
    <link rel="icon" href="assets/img/favicon.ico">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Alpine.js Plugins & Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom CSS -->
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Poppins', sans-serif;
            --color-brand-50: #eff6ff;
            --color-brand-100: #dbeafe;
            --color-brand-500: #3b82f6;
            --color-brand-600: #2563eb;
            --color-brand-700: #1d4ed8;
        }
        body { font-family: var(--font-sans); @apply text-gray-700 bg-gray-50; }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .animate-float { animation: float 4s ease-in-out infinite; }
    </style>
</head>

<body class="antialiased selection:bg-brand-500 selection:text-white overflow-x-hidden">

    <!-- ================= NAVBAR ================= -->
    <header x-data="{ mobileMenuOpen: false, scrolled: false }"
        @scroll.window="scrolled = (window.pageYOffset > 20)"
        :class="scrolled ? 'bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 py-0' : 'bg-transparent py-2'"
        class="fixed top-0 inset-x-0 z-50 transition-all duration-500">

        <nav class="container mx-auto px-4 sm:px-6 lg:px-8" aria-label="Global">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex lg:flex-1">
                    <a href="#" class="-m-1.5 p-1.5 flex items-center gap-3 group">
                        <img src="assets/img/logo.png" alt="Logo PRESTA" class="w-11 h-11 object-contain group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 leading-tight tracking-tight group-hover:text-brand-600 transition-colors">PRESTA</h2>
                            <span class="text-xs text-gray-500 font-medium">SMK Tiara Nusa</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden lg:flex lg:gap-x-8">
                    <a href="#fitur" class="text-sm font-semibold text-gray-700 hover:text-brand-600 hover:-translate-y-0.5 transition-all duration-300">Fitur</a>
                    <a href="#alur" class="text-sm font-semibold text-gray-700 hover:text-brand-600 hover:-translate-y-0.5 transition-all duration-300">Alur Sistem</a>
                    <a href="#pengguna" class="text-sm font-semibold text-gray-700 hover:text-brand-600 hover:-translate-y-0.5 transition-all duration-300">Pengguna</a>
                </div>

                <!-- CTA & Mobile Toggle -->
                <div class="flex flex-1 justify-end items-center gap-4">
                    <a href="index.php?route=auth/login" class="hidden lg:inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-md shadow-brand-500/20 hover:-translate-y-0.5 transition-all duration-300">
                        Masuk Sistem
                    </a>
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700 lg:hidden hover:bg-gray-100 transition-colors">
                        <span class="sr-only">Buka menu</span>
                        <svg x-show="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg x-show="mobileMenuOpen" style="display: none;" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-5"
            x-transition:enter-end="opacity-100 translate-y-0"
            style="display: none;" class="lg:hidden bg-white border-b border-gray-100 shadow-xl absolute w-full">
            <div class="space-y-1 px-4 pb-6 pt-2">
                <a href="#fitur" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-brand-50 hover:text-brand-600">Fitur</a>
                <a href="#alur" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-brand-50 hover:text-brand-600">Alur Sistem</a>
                <a href="#pengguna" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-brand-50 hover:text-brand-600">Pengguna</a>
                <div class="pt-4 mt-2 border-t border-gray-100">
                    <a href="index.php?route=auth/login" class="block w-full text-center rounded-lg px-3 py-2.5 text-base font-semibold text-white bg-brand-600 hover:bg-brand-700">Masuk Sistem</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)" class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-white">
        <div class="absolute inset-y-0 right-1/2 -z-10 mr-16 w-[200%] origin-bottom-left skew-x-[-30deg] bg-white shadow-xl shadow-brand-600/10 ring-1 ring-brand-50 sm:mr-28 lg:mr-0 xl:mr-16 xl:origin-center"></div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                <!-- LEFT: Content -->
                <div class="max-w-2xl transform transition-all duration-1000" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-gray-900 mb-6">
                        Presensi Digital <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-cyan-500">SMK Tiara Nusa</span>
                    </h1>
                    <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                        PRESTA membantu sekolah dalam mengelola kehadiran siswa secara cepat, akurat, transparan, dan terintegrasi mulai dari Sekretaris Kelas, Wali Kelas hingga Guru Piket.
                    </p>
                </div>

                <!-- RIGHT: Dashboard Preview -->
                <div class="relative lg:ml-auto w-full max-w-lg transform transition-all duration-1000 delay-300 animate-float" :class="show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-12'">
                    <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-cyan-400 rounded-[2rem] blur-2xl opacity-20 transition-opacity duration-1000 hover:opacity-40"></div>
                    <div class="relative bg-white/90 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50/80 border-b border-gray-100 px-4 py-3 flex items-center gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="mx-auto text-xs font-medium text-gray-500">PRESTA Dashboard - <?= date('d M Y', strtotime($today)) ?></div>
                        </div>

                        <div class="p-6">
                            <div class="bg-gradient-to-br from-brand-600 to-brand-700 rounded-xl p-5 text-white shadow-lg mb-6 flex justify-between items-center transform hover:scale-[1.02] transition-transform duration-300">
                                <div>
                                    <p class="text-brand-100 text-sm font-medium mb-1">Total Siswa Aktif</p>
                                    <h3 class="text-3xl font-bold"><?= number_format($totalSiswa) ?></h3>
                                </div>
                                <div class="p-3 bg-white/20 rounded-lg backdrop-blur-sm">
                                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-green-50 border border-green-100 rounded-xl p-4 transform hover:-translate-y-1 transition-transform duration-300">
                                    <span class="text-green-600 text-sm font-semibold block mb-1">Hadir</span>
                                    <strong class="text-2xl text-green-700"><?= number_format($hadir) ?></strong>
                                </div>
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 transform hover:-translate-y-1 transition-transform duration-300">
                                    <span class="text-blue-600 text-sm font-semibold block mb-1">Izin</span>
                                    <strong class="text-2xl text-blue-700"><?= number_format($izin) ?></strong>
                                </div>
                                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 transform hover:-translate-y-1 transition-transform duration-300">
                                    <span class="text-amber-600 text-sm font-semibold block mb-1">Sakit</span>
                                    <strong class="text-2xl text-amber-700"><?= number_format($sakit) ?></strong>
                                </div>
                                <div class="bg-red-50 border border-red-100 rounded-xl p-4 transform hover:-translate-y-1 transition-transform duration-300">
                                    <span class="text-red-600 text-sm font-semibold block mb-1">Alfa</span>
                                    <strong class="text-2xl text-red-700"><?= number_format($alfa) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= STATISTIC ================= -->
    <section x-data="{ shown: false }" x-intersect.once="shown = true" class="py-10 bg-white border-y border-gray-100">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-gray-100 transform transition-all duration-1000"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'">
                <div class="text-center px-4 hover:scale-105 transition-transform duration-300">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">100%</h3>
                    <p class="text-sm text-gray-500 font-medium">Realtime Monitoring</p>
                </div>
                <div class="text-center px-4 hover:scale-105 transition-transform duration-300 delay-100">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">3</h3>
                    <p class="text-sm text-gray-500 font-medium">Level Hak Akses</p>
                </div>
                <div class="text-center px-4 hover:scale-105 transition-transform duration-300 delay-200">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">Excel</h3>
                    <p class="text-sm text-gray-500 font-medium">Export Laporan</p>
                </div>
                <div class="text-center px-4 hover:scale-105 transition-transform duration-300 delay-300">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">24/7</h3>
                    <p class="text-sm text-gray-500 font-medium">Akses Sistem</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FITUR ================= -->
    <section id="fitur" x-data="{ shown: false }" x-intersect.half="shown = true" class="py-24 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 transform transition-all duration-1000"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'">

                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Pengelolaan Presensi Modern</h3>
                <p class="text-lg text-gray-600">PRESTA membantu sekolah mengelola data kehadiran secara efisien melalui sistem yang cepat, aman, dan terintegrasi.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Fitur 1 -->
                <article class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 group transform"
                    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'" style="transition-delay: 100ms;">
                    <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand-600 group-hover:text-white group-hover:rotate-6 transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Sinkronisasi Realtime</h3>
                    <p class="text-gray-600 leading-relaxed">Seluruh perubahan data langsung diperbarui kepada Wali Kelas, Guru Piket dan Sekretaris secara seketika.</p>
                </article>

                <!-- Fitur 2 -->
                <article class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 group transform"
                    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'" style="transition-delay: 200ms;">
                    <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand-600 group-hover:text-white group-hover:-rotate-6 transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Validasi Otomatis</h3>
                    <p class="text-gray-600 leading-relaxed">Meningkatkan kedisiplinan dengan validasi waktu serta perubahan status kehadiran secara otomatis dari sistem.</p>
                </article>

                <!-- Fitur 3 -->
                <article class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 group transform"
                    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'" style="transition-delay: 300ms;">
                    <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand-600 group-hover:text-white group-hover:scale-110 transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6M13 17V7M17 17v-3M5 19h14" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Rekap Laporan</h3>
                    <p class="text-gray-600 leading-relaxed">Rekap kehadiran dapat diekspor ke format Excel berdasarkan kelas maupun tahun pelajaran aktif dengan mudah.</p>
                </article>

                <!-- Fitur 4 -->
                <article class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 group transform"
                    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'" style="transition-delay: 400ms;">
                    <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand-600 group-hover:text-white group-hover:rotate-6 transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="10" rx="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Hak Akses Aman</h3>
                    <p class="text-gray-600 leading-relaxed">Setiap pengguna memiliki hak akses sesuai perannya sehingga privasi dan keamanan data sekolah tetap terjaga.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ================= ALUR ================= -->
    <section id="alur" x-data="{ shown: false }" x-intersect.half="shown = true" class="py-24 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 transform transition-all duration-1000"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'">
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Proses Pengelolaan Presensi</h3>
                <p class="text-lg text-gray-600">Proses presensi dilakukan berjenjang untuk memastikan data valid dan akurat.</p>
            </div>

            <div class="relative max-w-5xl mx-auto">
                <!-- Line background for desktop -->
                <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 rounded-full transform origin-left transition-all duration-1000 delay-300"
                    :class="shown ? 'scale-x-100' : 'scale-x-0'"></div>

                <div class="grid md:grid-cols-4 gap-8 relative z-10">
                    <!-- Step 1 -->
                    <div class="relative text-center group transform transition-all duration-700"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'" style="transition-delay: 200ms;">
                        <div class="w-16 h-16 mx-auto bg-white border-4 border-brand-100 text-brand-600 font-bold text-xl rounded-full flex items-center justify-center mb-4 group-hover:border-brand-500 group-hover:text-white group-hover:bg-brand-500 group-hover:scale-110 transition-all duration-300 shadow-sm cursor-default">
                            01
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Sekretaris Kelas</h4>
                        <p class="text-sm text-gray-600">Menginput data kehadiran siswa sebelum batas waktu yang ditentukan.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center group transform transition-all duration-700"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'" style="transition-delay: 400ms;">
                        <div class="w-16 h-16 mx-auto bg-white border-4 border-brand-100 text-brand-600 font-bold text-xl rounded-full flex items-center justify-center mb-4 group-hover:border-brand-500 group-hover:text-white group-hover:bg-brand-500 group-hover:scale-110 transition-all duration-300 shadow-sm cursor-default">
                            02
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Wali Kelas</h4>
                        <p class="text-sm text-gray-600">Melakukan verifikasi dan penyesuaian data sesuai kondisi kelas.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center group transform transition-all duration-700"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'" style="transition-delay: 600ms;">
                        <div class="w-16 h-16 mx-auto bg-white border-4 border-brand-100 text-brand-600 font-bold text-xl rounded-full flex items-center justify-center mb-4 group-hover:border-brand-500 group-hover:text-white group-hover:bg-brand-500 group-hover:scale-110 transition-all duration-300 shadow-sm cursor-default">
                            03
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Guru Piket</h4>
                        <p class="text-sm text-gray-600">Memantau keterlambatan, finalisasi, serta memastikan ketertiban.</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative text-center group transform transition-all duration-700"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'" style="transition-delay: 800ms;">
                        <div class="w-16 h-16 mx-auto bg-white border-4 border-brand-100 text-brand-600 font-bold text-xl rounded-full flex items-center justify-center mb-4 group-hover:border-brand-500 group-hover:text-white group-hover:bg-brand-500 group-hover:scale-110 transition-all duration-300 shadow-sm cursor-default">
                            04
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Kesiswaan</h4>
                        <p class="text-sm text-gray-600">Mengelola master data, user, serta memantau laporan semua kelas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= PENGGUNA ================= -->
    <section id="pengguna" x-data="{ shown: false }" x-intersect.half="shown = true" class="py-24 bg-gray-50 border-t border-gray-100">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 transform transition-all duration-1000"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'">
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Pengguna Sistem PRESTA</h3>
                <p class="text-lg text-gray-600">Setiap level akun dirancang khusus untuk mempermudah peran dan tugas masing-masing personel sekolah.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Role 1 -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-brand-300 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 text-center group transform"
                    :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'" style="transition-delay: 100ms;">
                    <div class="mx-auto w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">Sekretaris Kelas</h4>
                    <p class="text-sm text-gray-600">Menginput data kehadiran harian siswa secara rutin.</p>
                </div>

                <!-- Role 2 -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-brand-300 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 text-center group transform"
                    :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'" style="transition-delay: 200ms;">
                    <div class="mx-auto w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mb-4 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.25l9 4-9 4-9-4 9-4zm0 0v8.5m9-4.5v5.5a1 1 0 01-1 1H4a1 1 0 01-1-1V8.25" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">Wali Kelas</h4>
                    <p class="text-sm text-gray-600">Memantau dan menyetujui laporan dari sekretaris kelas.</p>
                </div>

                <!-- Role 3 -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-brand-300 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 text-center group transform"
                    :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'" style="transition-delay: 300ms;">
                    <div class="mx-auto w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mb-4 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">Guru Piket</h4>
                    <p class="text-sm text-gray-600">Mencatat keterlambatan dan menjaga ketertiban sekolah.</p>
                </div>

                <!-- Role 4 -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-brand-300 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 text-center group transform"
                    :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'" style="transition-delay: 400ms;">
                    <div class="mx-auto w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">Kesiswaan</h4>
                    <p class="text-sm text-gray-600">Kontrol penuh pengguna, data master, dan rekap sekolah.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="bg-white border-t border-gray-200">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12">

                <!-- Brand Info -->
                <div class="md:col-span-5 lg:col-span-4">
                    <a href="#" class="flex items-center gap-3 mb-4 group">
                        <!-- LOGO DARI ASSETS -->
                        <img src="assets/img/logo.png" alt="Logo PRESTA" class="w-10 h-10 object-contain group-hover:scale-110 transition-transform">
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight group-hover:text-brand-600 transition-colors">PRESTA</h3>
                    </a>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6">
                        Sistem Informasi Manajemen Presensi Digital Terpadu SMK Tiara Nusa. Membantu digitalisasi pencatatan kedisiplinan dan absensi siswa.
                    </p>
                </div>

                <!-- Navigasi -->
                <div class="md:col-span-3 lg:col-span-2 lg:col-start-7">
                    <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Navigasi</h4>
                    <ul class="space-y-3">
                        <li><a href="#fitur" class="inline-block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-all">Fitur Utama</a></li>
                        <li><a href="#alur" class="inline-block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-all">Alur Sistem</a></li>
                        <li><a href="#pengguna" class="inline-block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-all">Role Pengguna</a></li>
                    </ul>
                </div>

                <!-- Sistem -->
                <div class="md:col-span-4 lg:col-span-3">
                    <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Sistem Terkait</h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="index.php?route=auth/login" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Portal Login
                            </a>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="mt-12 pt-8 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-500">
                    &copy; <?php echo date('Y'); ?> SMK Tiara Nusa. Seluruh Hak Cipta Dilindungi.
                </p>
                <p class="text-xs text-gray-400">
                    Sistem versi 1.5.2
                </p>
            </div>
        </div>
    </footer>

</body>

</html>