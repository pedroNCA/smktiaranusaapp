<?php
// Pastikan app_config sudah dimuat agar variabel $app_data tersedia
if (!isset($app_data)) {
    require_once __DIR__ . '/app_config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Backdrop (Mobile) -->
<div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden" x-transition.opacity @click="sidebarOpen = false"></div>

<!-- Sidebar Utama -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 lg:w-64 flex flex-col border-r border-slate-800">

    <!-- Brand Logo / Header Dinamis -->
    <div class="h-16 flex items-center px-6 bg-slate-950 border-b border-slate-800 gap-3">
        <img class="h-8 w-auto object-contain" src="../../assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo" onerror="this.style.display='none'">
        <span class="text-lg font-display font-bold text-white tracking-wide">Humanis <span class="text-blue-500">Insight</span></span>
    </div>

    <!-- Menu Navigasi Eksekutif -->
    <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-6">

        <!-- KELOMPOK 1: DASBOR & LAPORAN -->
        <div class="space-y-2">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 px-3">Dasbor & Laporan</div>

            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'dashboard.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard Eksekutif
            </a>

            <a href="laporan.php" class="<?= $current_page == 'laporan.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'laporan.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Laporan & Cetak
            </a>
        </div>

        <!-- KELOMPOK 2: PENGAWASAN & EVALUASI -->
        <div class="space-y-2">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 px-3">Pengawasan & Evaluasi</div>

            <a href="tindak_lanjut.php" class="<?= $current_page == 'tindak_lanjut.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'tindak_lanjut.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Persetujuan & Tindak Lanjut
            </a>

            <a href="monitoring.php" class="<?= $current_page == 'monitoring.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'monitoring.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Monitoring Seluruh Bidang
            </a>

        </div>

        <!-- KELOMPOK 3: KONFIGURASI & MASTER DATA -->
        <div class="space-y-2">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 px-3">Konfigurasi & Master Data</div>

            <a href="master_bidang.php" class="<?= $current_page == 'master_bidang.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'master_bidang.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Kelola Master Bidang
            </a>

            <a href="kelola_user.php" class="<?= $current_page == 'kelola_user.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'kelola_user.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Kelola User & Akses
            </a>

            <a href="master_data.php" class="<?= $current_page == 'master_data.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'master_data.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Kelola Master Data (KPI)
            </a>

            <a href="pengaturan.php" class="<?= $current_page == 'pengaturan.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3 <?= $current_page == 'pengaturan.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Pengaturan Sistem
            </a>
        </div>

    </nav>
</aside>