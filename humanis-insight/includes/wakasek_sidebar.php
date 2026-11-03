<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden" x-transition.opacity @click="sidebarOpen = false" style="display: none;"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 lg:w-64 flex flex-col border-r border-slate-800 shadow-xl">
    <!-- Brand Logo / Header Dinamis -->
    <div class="h-16 flex items-center px-6 bg-slate-950 border-b border-slate-800 gap-3">
        <?php if (!empty($app_data['logo'])): ?>
            <img class="h-8 w-auto object-contain" src="../../assets/img/<?= htmlspecialchars($app_data['logo']) ?>" alt="Logo" onerror="this.style.display='none'">
        <?php else: ?>
            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center font-bold text-white shadow-lg">H</div>
        <?php endif; ?>
        <span class="text-lg font-display font-bold text-white tracking-wide">Humanis <span class="text-blue-500">Insight</span></span>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-6">
        <!-- Manajemen / Utama -->
        <div>
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3 px-3">Menu Wakasek</div>
            <div class="space-y-1.5">
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors <?= $current_page == 'dashboard.php' ? 'bg-primary text-white shadow-md' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Kinerja Saya -->
        <div>
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3 px-3">Kinerja Pribadi</div>
            <div class="space-y-1.5">
                <a href="pelaporan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors <?= $current_page == 'pelaporan.php' || $current_page == 'edit_pelaporan.php' ? 'bg-primary text-white shadow-md' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm font-medium">Input Laporan</span>
                </a>

                <a href="pantauan_kinerja.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors <?= $current_page == 'pantauan_kinerja.php' ? 'bg-primary text-white shadow-md' : 'hover:bg-slate-800 hover:text-white text-slate-300' ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <span class="text-sm font-medium">Pantauan Kinerja</span>
                </a>
            </div>
        </div>
    </nav>
</aside>
<div class="flex-1 flex flex-col min-w-0">