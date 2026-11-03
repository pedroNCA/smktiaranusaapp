<?php
// Pastikan app_config sudah dimuat agar variabel $app_data tersedia
if (!isset($app_data)) {
    require_once __DIR__ . '/app_config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Backdrop (Mobile) -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" style="display: none;"></div>

<!-- Sidebar Navigation -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto border-r border-slate-800">

    <!-- Brand Logo / Header Dinamis -->
    <div class="h-16 flex items-center px-6 bg-slate-950 border-b border-slate-800 gap-3">
        <img class="h-8 w-auto object-contain" src="../../assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo" onerror="this.style.display='none'">
        <span class="text-lg font-display font-bold text-white tracking-wide">Humanis <span class="text-blue-500">Insight</span></span>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors <?= $current_page === 'dashboard.php' ? 'bg-primary text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Dashboard
        </a>
        <a href="pelaporan.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors <?= $current_page === 'pelaporan.php' ? 'bg-primary text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Input Laporan
        </a>
        <a href="dashboard_kinerja.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors <?= $current_page === 'dashboard_kinerja.php' ? 'bg-primary text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2z" />
            </svg>
            Pantauan Kinerja
        </a>
    </nav>

</aside>