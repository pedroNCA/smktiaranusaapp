<?php
// includes/bk_sidebar.php

// Ambil nama file halaman yang sedang diakses saat ini
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Overlay gelap saat sidebar terbuka di layar HP -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-20 lg:hidden" style="display: none;" x-transition></div>

<!-- Sidebar Kiri -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-30 w-64 bg-primary text-white transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 shadow-xl flex flex-col">

    <!-- Area Logo & Nama Sekolah -->
    <div class="flex items-center justify-center h-16 bg-primary-light border-b border-blue-800/50 shadow-sm">
        <img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-8 mr-3 object-contain bg-white rounded-full p-0.5" onerror="this.style.display='none'">
        <span class="text-base font-bold tracking-wide uppercase">SMK Tiara Nusa</span>
    </div>

    <!-- Menu Navigasi -->
    <nav class="flex-1 px-4 py-6 space-y-2">

        <!-- Menu Dashboard -->
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors <?= ($currentPage == 'dashboard.php') ? 'bg-white/10 text-white border-l-4 border-white' : 'text-blue-200 hover:bg-white/10 hover:text-white border-l-4 border-transparent' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Dashboard
        </a>

        <!-- Menu Master Data Siswa -->
        <a href="master_siswa.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors <?= ($currentPage == 'master_siswa.php') ? 'bg-white/10 text-white border-l-4 border-white' : 'text-blue-200 hover:bg-white/10 hover:text-white border-l-4 border-transparent' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            Master Data Siswa
        </a>

        <!-- Menu Data Asesmen (Menyala di asesmen.php atau detail_siswa.php atau detail_jawaban.php) -->
        <a href="asesmen.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors <?= ($currentPage == 'asesmen.php' || $currentPage == 'detail_siswa.php' || $currentPage == 'detail_jawaban.php') ? 'bg-white/10 text-white border-l-4 border-white' : 'text-blue-200 hover:bg-white/10 hover:text-white border-l-4 border-transparent' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Data Asesmen
        </a>

        <!-- Pemisah -->
        <div class="border-t border-blue-800/50 my-2"></div>

        <!-- Menu Download Rekap Asesmen -->
        <a href="../../proses/export_semua_jawaban.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-blue-200 hover:bg-white/10 hover:text-white border-l-4 border-transparent transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Download Rekap XLSX
        </a>

    </nav>
</aside>

<!-- Wrapper untuk konten utama (mengisi sisa ruang di sebelah kanan) -->
<div class="flex-1 flex flex-col min-w-0 overflow-hidden">