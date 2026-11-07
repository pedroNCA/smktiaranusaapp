<?php $route = $_GET['route'] ?? 'dashboard'; ?>
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-slate-900 text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto">
    <div class="flex items-center justify-center h-20 bg-slate-950 px-4 border-b border-slate-800">
        <img src="/assets/img/logo.png" alt="Logo" class="w-8 h-8 mr-2 object-contain">
        <span class="text-xl font-bold tracking-wider text-emerald-400">e-Raport Tinus</span>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto text-sm font-medium">
        <a href="/dashboard" class="flex items-center px-4 py-3 rounded-xl <?= ($route == 'dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        <!-- MENU ADMINISTRATOR -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="pt-4 pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Administrator</div>
            <a href="/admin/guru" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'admin/guru') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Data Guru</a>
            <a href="/admin/siswa" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'admin/siswa') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Data Siswa</a>
            <a href="/admin/walikelas" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'admin/walikelas') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Data Wali Kelas</a>
            <a href="/admin/mapel" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'admin/mapel') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Data Mapel</a>
            <a href="/admin/sekolah" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'admin/sekolah') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Profil Sekolah</a>
            <!-- Tambahkan di bagian Menu Administrator pada views/layouts/sidebar.php -->
<a href="/admin/rekap/download-semua" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'admin/rekap') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">
    Download Rekap Kelas
</a>
            <?php endif; ?>

        <!-- MENU WALI KELAS -->
        <?php if (isset($_SESSION['is_walikelas']) && $_SESSION['is_walikelas'] == true): ?>
            <div class="pt-4 pb-2 text-xs font-bold text-emerald-500 uppercase tracking-wider">Wali Kelas</div>
            <a href="/walikelas/nilai" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'walikelas/nilai') === 0 && strpos($route, 'download-excel') === false) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Input Nilai Kelas</a>
            <a href="/walikelas/absensi" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'walikelas/absensi') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Input Absensi</a>
            <a href="/walikelas/nilai/download-excel" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'walikelas/nilai/download-excel') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">
                Download Rekap
            </a>
            <a href="/walikelas/raport" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'walikelas/raport') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Cetak Raport</a>
            <a href="/walikelas/ttd" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'walikelas/ttd') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Upload TTD</a>
        <?php endif; ?>

        <!-- MENU GURU MAPEL -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'guru'): ?>
            <div class="pt-4 pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Guru Mapel</div>
            <a href="/guru/nilai" class="flex items-center px-4 py-3 rounded-xl <?= (strpos($route, 'guru/nilai') === 0) ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-colors">Input Nilai Mapel</a>
        <?php endif; ?>
    </nav>
</aside>