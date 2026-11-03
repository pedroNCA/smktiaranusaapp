<?php
// Tentukan label role dan cek wali kelas untuk topbar
$userIdTop = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'sekretaris';
$labelRole = ucfirst($userRole);

if ($userRole === 'guru') {
    $queryWali = $conn->prepare("SELECT nama_kelas FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
    if ($queryWali) {
        $queryWali->bind_param("i", $userIdTop);
        $queryWali->execute();
        $resultWali = $queryWali->get_result();
        if ($dataWali = $resultWali->fetch_assoc()) {
            $labelRole = 'Wali Kelas ' . $dataWali['nama_kelas'];
        }
        $queryWali->close();
    }
} elseif ($userRole === 'sekretaris') {
    $labelRole = 'Sekretaris Kelas';
} elseif ($userRole === 'piket') {
    $labelRole = 'Guru Piket';
}

$bulanIndonesia = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$tanggalIndonesia = date('d') . ' ' . $bulanIndonesia[(int) date('n')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'head.php'; ?>
</head>
<body class="app-layout antialiased text-gray-800 bg-slate-50">

    <!-- ================= SIDEBAR ================= -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="main-content flex flex-col h-[100dvh] overflow-hidden">

        <!-- ================= TOPBAR ================= -->
        <header class="bg-white border-b border-gray-100 h-[70px] flex-none px-4 sm:px-6 flex items-center justify-between sticky top-0 z-40 shadow-sm w-full">
            <!-- Kiri: Tombol Toggle Sidebar & Informasi Halaman -->
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="p-2 -ml-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/30 lg:hidden flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <div class="flex flex-col">
                    <h3 class="text-sm sm:text-base font-bold text-gray-900 leading-tight"><?= $pageTitle ?? 'Dashboard'; ?></h3>
                    <small id="todayDate" class="text-[11px] text-gray-400 font-semibold tracking-wide">
                        <?= $tanggalIndonesia ?>
                    </small>
                </div>
            </div>

            <!-- Kanan: Profil Dropdown (Menggunakan Alpine.js) -->
            <div class="flex items-center h-full relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                
                <button @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-3 focus:outline-none p-1 sm:pr-4 rounded-full border border-transparent hover:border-gray-100 hover:bg-gray-50 transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 shrink-0 rounded-full bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center font-bold text-lg shadow-sm">
                        <?= strtoupper(substr(Auth::name() ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="hidden sm:flex flex-col items-start text-left">
                        <span class="text-sm font-bold text-gray-800 leading-tight"><?= htmlspecialchars(Auth::name() ?? '') ?></span>
                        <span class="text-[11px] font-semibold text-brand-600 uppercase tracking-wider leading-tight mt-0.5"><?= htmlspecialchars($labelRole) ?></span>
                    </div>
                    <svg class="hidden sm:block w-4 h-4 text-gray-400 ml-1 transition-transform duration-300" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="dropdownOpen"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                    style="display: none;"
                    class="absolute right-0 top-14 w-56 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50">
                    
                    <div class="px-4 py-3 border-b border-gray-50 bg-slate-50">
                        <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5">Masuk sebagai</p>
                        <p class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars(Auth::name() ?? '') ?></p>
                    </div>

                    <div class="p-1.5">
                        <a href="index.php?route=auth/logout" class="flex items-center gap-2 px-3 py-2 text-sm font-semibold text-red-600 rounded-lg hover:bg-red-50 hover:text-red-700 transition-colors group" style="text-decoration: none !important;">
                            <svg class="w-4 h-4 text-red-500 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>

            </div>
        </header>

        <!-- Container untuk Konten (div content-area ditutup di footer.php) -->