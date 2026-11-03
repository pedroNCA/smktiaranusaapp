<?php
// Pastikan koneksi database tersedia untuk mengambil foto terbaru jika belum ada di session
require_once __DIR__ . '/../config/database.php';

$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'dashboard.php' => 'Dashboard Operator',
    'master_bidang.php' => 'Kelola Master Bidang',
    'master_data.php' => 'Kelola Master KPI',
    'kelola_user.php' => 'Kelola User & Akses',
    'backup.php' => 'Backup Database',
    'pengaturan_sistem.php' => 'Pengaturan Sistem',
    'profil.php' => 'Profil Akun',
    'ganti_password.php' => 'Ganti Password'
];
$title = $page_titles[$current_page] ?? 'Panel Operator';

// Ambil foto profil terbaru dari database agar langsung berubah tanpa perlu relogin
$user_foto = $_SESSION['foto'] ?? '';
if (!empty($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmtNavbar = $pdo->prepare("SELECT foto FROM tb_users WHERE id_user = :id LIMIT 1");
        $stmtNavbar->execute([':id' => $_SESSION['user_id']]);
        $db_foto = $stmtNavbar->fetchColumn();
        if ($db_foto) {
            $user_foto = $db_foto;
        }
    } catch (Exception $e) {
        // Abaikan jika terjadi kendala koneksi sementara
    }
}
?>
<!-- Area Konten Utama -->
<div class="flex-1 flex flex-col overflow-hidden">
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-10 shadow-sm">
        <div class="flex items-center">
            <button @click="sidebarOpen = true" class="text-slate-500 hover:text-slate-700 focus:outline-none lg:hidden mr-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h1 class="text-xl font-display font-semibold text-slate-900"><?= htmlspecialchars($title) ?></h1>
        </div>

        <!-- Aksi Sebelah Kanan (Dropdown Profil) -->
        <div class="flex items-center space-x-4">
            <div class="relative" x-data="{ navDropdown: false }">
                <button @click="navDropdown = !navDropdown" class="flex items-center gap-3 focus:outline-none group py-1.5 px-2 rounded-lg hover:bg-slate-50 transition-colors">

                    <!-- Avatar / Foto Profil Dinamis -->
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-primary/10 text-primary flex items-center justify-center font-bold text-xs uppercase shadow-inner shrink-0">
                        <?php if (!empty($user_foto) && file_exists(__DIR__ . '/../assets/img/profiles/' . $user_foto)): ?>
                            <img src="../../assets/img/profiles/<?= htmlspecialchars($user_foto) ?>" alt="Foto Profil" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= substr($_SESSION['nama'] ?? 'O', 0, 1) ?>
                        <?php endif; ?>
                    </div>

                    <div class="hidden sm:block text-left">
                        <span class="block text-xs font-semibold text-slate-800 leading-tight"><?= htmlspecialchars($_SESSION['nama'] ?? 'Operator') ?></span>
                        <span class="block text-[10px] text-slate-400 font-medium leading-none mt-0.5"><?= htmlspecialchars($_SESSION['role'] ?? 'Operator') ?></span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-transform" :class="navDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="navDropdown" @click.outside="navDropdown = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50" style="display: none;">

                    <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                        <p class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($_SESSION['nama'] ?? 'Operator') ?></p>
                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($_SESSION['role'] ?? 'Operator') ?></p>
                    </div>

                    <a href="profil.php" class="flex items-center px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profil Akun
                    </a>

                    <a href="ganti_password.php" class="flex items-center px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Ganti Password
                    </a>

                    <div class="border-t border-slate-100 my-1"></div>

                    <a href="../../auth/logout.php" class="flex items-center px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4 mr-2.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar Sistem
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main class="flex-1 overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">