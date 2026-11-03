<?php
// Pastikan app_config sudah dimuat agar variabel $app_data tersedia
if (!isset($app_data)) {
    require_once __DIR__ . '/app_config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Backdrop (Mobile) -->
<div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden" x-transition.opacity @click="sidebarOpen = false" style="display: none;"></div>

<!-- Sidebar Utama -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 lg:w-64 flex flex-col border-r border-slate-800">

    <!-- Brand Logo / Header Dinamis -->
    <div class="h-16 flex items-center px-6 bg-slate-950 border-b border-slate-800 gap-3 shrink-0">
        <img class="h-8 w-auto object-contain" src="../../assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo" onerror="this.style.display='none'">
        <span class="text-lg font-display font-bold text-white tracking-wide">Humanis <span class="text-blue-500">Insight</span></span>
    </div>

    <!-- Menu Navigasi Operator -->
    <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-2">

        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 px-3">Menu Administrator</div>

        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-3 <?= $current_page == 'dashboard.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        <div class="border-t border-slate-800 my-4"></div>
        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 px-3">Pemeliharaan</div>

        <a href="backup.php" class="<?= $current_page == 'backup.php' ? 'bg-primary text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-3 <?= $current_page == 'backup.php' ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            Backup Data
        </a>



    </nav>

</aside>