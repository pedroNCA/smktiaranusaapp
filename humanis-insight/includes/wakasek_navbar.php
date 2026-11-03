<?php
require_once __DIR__ . '/../config/database.php';

$namaUser = $_SESSION['nama'] ?? '';

// --- PROSES AKSI NOTIFIKASI (POIN 1, 2, 3) ---
if (!empty($namaUser) && isset($pdo)) {
    // Proses jika SATU notifikasi diklik (tandai dibaca & redirect)
    if (isset($_GET['read_notif']) && isset($_GET['url'])) {
        $id_notif = (int)$_GET['read_notif'];
        $url_target = filter_var($_GET['url'], FILTER_SANITIZE_URL);
        
        try {
            $stmtUpdate = $pdo->prepare("UPDATE tb_notifikasi SET dibaca = 1 WHERE id_notifikasi = :id AND nama_penerima = :nama");
            $stmtUpdate->execute([':id' => $id_notif, ':nama' => $namaUser]);
        } catch (Exception $e) {}
        
        // Redirect via JS agar tidak error "Headers already sent"
        echo "<script>window.location.href = '" . $url_target . "';</script>";
        exit;
    }

    // Proses jika klik "Tandai semua dibaca" (POIN 1)
    if (isset($_GET['read_all_notif'])) {
        try {
            $stmtUpdateAll = $pdo->prepare("UPDATE tb_notifikasi SET dibaca = 1 WHERE nama_penerima = :nama AND dibaca = 0");
            $stmtUpdateAll->execute([':nama' => $namaUser]);
        } catch (Exception $e) {}
        
        $current_url = $_SERVER['PHP_SELF'];
        $params = $_GET;
        unset($params['read_all_notif']);
        if (!empty($params)) {
            $current_url .= '?' . http_build_query($params);
        }
        
        echo "<script>window.location.href = '" . $current_url . "';</script>";
        exit;
    }
}

// --- LOGIKA MENGAMBIL NOTIFIKASI ---
$unreadCount = 0;
$listNotif = [];
$limitNotif = 5; // POIN 4: Membatasi jumlah notifikasi yang tampil (Hanya 5 terbaru)

if (!empty($namaUser) && isset($pdo)) {
    try {
        // Ambil notifikasi terbaru
        $stmtNotif = $pdo->prepare("SELECT * FROM tb_notifikasi WHERE nama_penerima = :nama ORDER BY id_notifikasi DESC LIMIT :limit");
        $stmtNotif->bindValue(':nama', $namaUser, PDO::PARAM_STR);
        $stmtNotif->bindValue(':limit', $limitNotif, PDO::PARAM_INT);
        $stmtNotif->execute();
        $listNotif = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        // POIN 3: Hitung hanya yang belum dibaca (angka akan berkurang jika sudah dibaca)
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_notifikasi WHERE nama_penerima = :nama AND dibaca = 0");
        $stmtCount->execute([':nama' => $namaUser]);
        $unreadCount = $stmtCount->fetchColumn();
    } catch (Exception $e) {}
}
// --- AKHIR LOGIKA NOTIFIKASI ---

$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'dashboard.php' => 'Dashboard Wakasek',
    'validasi_data.php' => 'Validasi Kinerja Bawahan',
    'monitoring_bidang.php' => 'Monitoring Bidang',
    'laporan_bidang.php' => 'Cetak Laporan Bidang',
    'profil.php' => 'Profil Akun',
    'ganti_password.php' => 'Ganti Password'
];
$title = $page_titles[$current_page] ?? 'Panel Wakil Kepala';

$user_foto = $_SESSION['foto'] ?? '';
if (!empty($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmtNavbar = $pdo->prepare("SELECT foto FROM tb_users WHERE id_user = :id LIMIT 1");
        $stmtNavbar->execute([':id' => $_SESSION['user_id']]);
        $db_foto = $stmtNavbar->fetchColumn();
        if ($db_foto) {
            $user_foto = $db_foto;
        }
    } catch (Exception $e) {}
}

$folder_upload = realpath(__DIR__ . '/../assets/img/profiles/');
$file_path = $folder_upload ? $folder_upload . '/' . $user_foto : __DIR__ . '/../assets/img/profiles/' . $user_foto;
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

        <div class="flex items-center space-x-4">
            
            <!-- Ikon Notifikasi -->
            <div class="relative" x-data="{ openNotif: false }">
                <button @click="openNotif = !openNotif" class="relative p-2 text-slate-500 hover:text-primary transition-colors focus:outline-none rounded-full hover:bg-slate-50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </button>

                <!-- Panel Dropdown Notifikasi -->
                <div x-show="openNotif" @click.away="openNotif = false" style="display: none;" class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50">
                    
                    <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Notifikasi</span>
                        <?php if ($unreadCount > 0): ?>
                            <!-- POIN 1: Tombol Tandai Semua Dibaca -->
                            <a href="?read_all_notif=1" class="text-[10px] text-primary hover:text-blue-800 font-semibold transition-colors">Tandai semua dibaca</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="max-h-64 overflow-y-auto">
                        <?php if (!empty($listNotif)): foreach ($listNotif as $n): ?>
                                <!-- POIN 2: Perubahan warna jika sudah dibaca (bg-white vs bg-blue-50/30) -->
                                <a href="?read_notif=<?= $n['id_notifikasi'] ?>&url=<?= urlencode($n['link']) ?>" 
                                   class="block px-4 py-3 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors <?= $n['dibaca'] == 0 ? 'bg-blue-50/30' : 'bg-white opacity-75' ?>">
                                    <p class="text-xs <?= $n['dibaca'] == 0 ? 'text-slate-900 font-bold' : 'text-slate-600 font-medium' ?> leading-relaxed">
                                        <?= htmlspecialchars($n['pesan']) ?>
                                    </p>
                                    <span class="text-[10px] <?= $n['dibaca'] == 0 ? 'text-primary font-medium' : 'text-slate-400' ?> mt-1 block">
                                        <?= date('d M Y, H:i', strtotime($n['tanggal'])) ?>
                                    </span>
                                </a>
                            <?php endforeach;
                        else: ?>
                            <div class="px-4 py-6 text-center text-xs text-slate-400">Belum ada notifikasi saat ini.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Avatar Dropdown Kanan -->
            <div class="relative" x-data="{ navDropdown: false }">
                <button @click="navDropdown = !navDropdown" class="flex items-center gap-3 focus:outline-none group py-1.5 px-2 rounded-lg hover:bg-slate-50 transition-colors">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-primary/10 text-primary flex items-center justify-center font-bold text-xs uppercase shadow-inner shrink-0">
                        <?php if (!empty($user_foto) && file_exists($file_path)): ?>
                            <img src="../../assets/img/profiles/<?= htmlspecialchars($user_foto) ?>" alt="Foto Profil" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= substr($_SESSION['nama'] ?? 'W', 0, 1) ?>
                        <?php endif; ?>
                    </div>

                    <div class="hidden sm:block text-left">
                        <span class="block text-xs font-semibold text-slate-800 leading-tight"><?= htmlspecialchars($_SESSION['nama'] ?? 'Wakil Kepala') ?></span>
                        <span class="block text-[10px] text-slate-400 font-medium leading-none mt-0.5"><?= htmlspecialchars($_SESSION['role'] ?? 'Wakil Kepala') ?> <?= htmlspecialchars($_SESSION['bidang'] ?? '') ?></span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-transform" :class="navDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="navDropdown" @click.outside="navDropdown = false" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50" style="display: none;">
                    <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                        <p class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($_SESSION['nama'] ?? 'Wakil Kepala') ?></p>
                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($_SESSION['role'] ?? 'Wakil Kepala') ?></p>
                    </div>

                    <a href="profil.php" class="flex items-center px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Profil Akun
                    </a>

                    <a href="ganti_password.php" class="flex items-center px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        Ganti Password
                    </a>

                    <div class="border-t border-slate-100 my-1"></div>

                    <a href="../../auth/logout.php" class="flex items-center px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4 mr-2.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        Keluar Sistem
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main class="flex-1 overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">