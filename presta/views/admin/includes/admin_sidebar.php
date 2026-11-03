<?php
// Deteksi halaman aktif berdasarkan parameter route
$current_route = $_GET['route'] ?? 'admin/dashboard';

// Cek kelompok halaman aktif untuk otomatis membuka dropdown
$is_guru_active = in_array($current_route, ['admin/guru', 'admin/guru_piket']);
$is_siswa_active = in_array($current_route, ['admin/kelas', 'admin/siswa', 'admin/sekretaris']);
$is_laporan_active = in_array($current_route, ['admin/laporan', 'admin/laporan_hari_ini']);
?>

<!-- Pastikan class statis -translate-x-full ada di sini -->
<aside id="sidebar" class="sidebar fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col transition-transform duration-300 shadow-lg lg:shadow-none -translate-x-full lg:translate-x-0">

    <!-- Logo / Brand -->
    <div class="h-[70px] flex items-center px-6 border-b border-gray-100">
        <a href="index.php?route=admin/dashboard" class="text-lg font-extrabold text-brand-600 tracking-wider">
            PRESTA <span class="text-xs font-semibold px-2 py-0.5 bg-brand-50 text-brand-600 rounded-md ml-1">ADMIN</span>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1.5 font-medium text-sm">

        <!-- Dashboard -->
        <?php $isActive = ($current_route == 'admin/dashboard'); ?>
        <a href="index.php?route=admin/dashboard" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 <?= $isActive ? 'text-white' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span>Dashboard</span>
        </a>

        <!-- Tahun Pelajaran -->
        <?php $isActive = ($current_route == 'admin/tahun_ajaran'); ?>
        <a href="index.php?route=admin/tahun_ajaran" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 <?= $isActive ? 'text-white' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Tahun Pelajaran</span>
        </a>

        <!-- Batas Waktu Presensi -->
        <?php $isActive = ($current_route == 'admin/pengaturan_presensi'); ?>
        <a href="index.php?route=admin/pengaturan_presensi" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 <?= $isActive ? 'text-white' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Batas Waktu Presensi</span>
        </a>

        <!-- 1. GURU (Dropdown) -->
        <div x-data="{ open: <?= $is_guru_active ? 'true' : 'false' ?> }" class="space-y-1">
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors <?= $is_guru_active ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Guru</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200 text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-transition class="pl-4 space-y-1 pt-1" style="display: none;">
                <?php $isActive = ($current_route == 'admin/guru'); ?>
                <a href="index.php?route=admin/guru" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Data Guru</span>
                </a>
                <?php $isActive = ($current_route == 'admin/guru_piket'); ?>
                <a href="index.php?route=admin/guru_piket" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Data Guru Piket</span>
                </a>
            </div>
        </div>

        <!-- 2. SISWA (Dropdown) -->
        <div x-data="{ open: <?= $is_siswa_active ? 'true' : 'false' ?> }" class="space-y-1">
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors <?= $is_siswa_active ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Siswa</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200 text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-transition class="pl-4 space-y-1 pt-1" style="display: none;">
                <?php $isActive = ($current_route == 'admin/kelas'); ?>
                <a href="index.php?route=admin/kelas" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Data Kelas</span>
                </a>
                <?php $isActive = ($current_route == 'admin/siswa'); ?>
                <a href="index.php?route=admin/siswa" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Data Siswa</span>
                </a>
                <?php $isActive = ($current_route == 'admin/sekretaris'); ?>
                <a href="index.php?route=admin/sekretaris" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Data Sekretaris</span>
                </a>
            </div>
        </div>

        <!-- Data Presensi -->
        <?php $isActive = ($current_route == 'admin/presensi'); ?>
        <a href="index.php?route=admin/presensi" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 <?= $isActive ? 'text-white' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Presensi Hari Ini</span>
        </a>

        <!-- 3. LAPORAN (Dropdown) -->
        <div x-data="{ open: <?= $is_laporan_active ? 'true' : 'false' ?> }" class="space-y-1">
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors <?= $is_laporan_active ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Laporan</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200 text-gray-400" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-transition class="pl-4 space-y-1 pt-1" style="display: none;">
                <?php $isActive = ($current_route == 'admin/laporan_hari_ini'); ?>
                <a href="index.php?route=admin/laporan_hari_ini" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Laporan Hari Ini</span>
                </a>
                <?php $isActive = ($current_route == 'admin/laporan'); ?>
                <a href="index.php?route=admin/laporan" class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition-colors text-xs <?= $isActive ? 'bg-brand-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <span>Laporan Rekap</span>
                </a>
            </div>
        </div>

    </nav>
</aside>

<!-- SCRIPT PINTAR: Akan berjalan otomatis di semua halaman -->
<script>
    function toggleSidebar(e) {
        if (e) e.stopPropagation();
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('-translate-x-full');
        }
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        // Tutup jika layar mobile DAN sidebar sedang terbuka DAN klik terjadi di luar sidebar
        if (window.innerWidth < 1024 && sidebar && !sidebar.classList.contains('-translate-x-full')) {
            if (!sidebar.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    });
</script>