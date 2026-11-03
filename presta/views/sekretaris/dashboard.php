<?php
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Services/DashboardService.php';

Auth::sekretaris();

$tanggal_hari_ini = date('Y-m-d');
$dashboard = DashboardService::dashboardSekretaris($conn, Auth::id(), $tanggal_hari_ini);

$kelas      = $dashboard['kelas'];
$stats      = $dashboard['stats'];
$workflow   = $dashboard['workflow'];
$totalSiswa = $dashboard['total_siswa'];
$progress   = $dashboard['progress'];
$canInput   = $dashboard['can_input'];

$bulanIndonesia = [1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU', 9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'];
$hariIndonesia = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];

// --- Memanggil Layout Global (Head, Sidebar, Topbar) ---
$pageTitle = "Dashboard Sekretaris - PRESTA";
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ================= HERO ================= -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-2 text-white">
                    Selamat Datang, <?= htmlspecialchars(Auth::name()) ?>
                </h1>

            </div>

            <!-- Date Card (Right) -->
            <div class="rounded-xl p-5 text-center min-w-[130px] ">
                <!-- Status Badge -->
                <?php
                $status = $dashboard['verification']['status'] ?? 'draft';
                $statusConfig = [
                    'draft' => ['bg-gray-100 text-gray-700 border-gray-200', 'bg-gray-400', 'Belum Mengisi'],
                    'sekretaris' => ['bg-green-50 text-green-700 border-green-100', 'bg-green-500', 'Sudah Dikirim'],
                    'mapel' => ['bg-blue-50 text-blue-700 border-blue-100', 'bg-blue-500', 'Diverifikasi Guru'],
                    'selesai' => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-emerald-500', 'Selesai']
                ];
                $sc = $statusConfig[$status] ?? $statusConfig['draft'];
                ?>
                <div class="inline-flex items-center gap-2.5 px-4 py-2 <?= $sc[0] ?> rounded-lg text-sm font-semibold shadow-sm border">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 <?= $sc[1] ?>"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 <?= $sc[1] ?>"></span>
                    </span>
                    <?= $sc[2] ?>
                </div>
            </div>
        </section>

        <!-- ================= STATISTIK ================= -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 transform transition-all duration-700 delay-100 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <?php
            $cards = [
                ['Hadir', $stats['hadir'], 'bg-green-50 text-green-600', 'M5 13l4 4L19 7'],
                ['Izin', $stats['izin'], 'bg-blue-50 text-blue-600', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['Sakit', $stats['sakit'], 'bg-amber-50 text-amber-500', 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                ['Alfa', $stats['alfa'], 'bg-red-50 text-red-600', 'M6 18L18 6M6 6l12 12'],
                ['Terlambat', $stats['terlambat'], 'bg-purple-50 text-purple-600', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['Belum Diisi', $stats['belum'], 'bg-slate-100 text-slate-500', 'M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z']
            ];
            foreach ($cards as $c): ?>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                    <div class="w-10 h-10 shrink-0 rounded-lg <?= $c[2] ?> flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $c[3] ?>" />
                        </svg>
                    </div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1"><?= $c[0] ?></p>
                    <h2 class="text-2xl font-extrabold text-gray-900"><?= $c[1] ?></h2>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- ================= INFO & PROGRESS ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 transform transition-all duration-700 delay-200 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <!-- Detail Kelas -->
            <section class="lg:col-span-1 bg-white p-7 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-5 justify-center">
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Kelas</p>
                    <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                </div>
                <div class="border-t border-gray-50"></div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Wali Kelas</p>
                    <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($kelas['wali_kelas']) ?></h3>
                </div>
                <div class="border-t border-gray-50"></div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Total Siswa</p>
                    <h3 class="text-lg font-bold text-brand-600"><?= number_format($totalSiswa) ?> <span class="text-sm font-semibold text-gray-500 normal-case">Siswa</span></h3>
                </div>
            </section>

            <!-- Workflow Verifikasi -->
            <section class="lg:col-span-2 bg-white p-7 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-1">Progress Verifikasi</h2>
                        <p class="text-sm text-gray-500">Alur verifikasi presensi kelas hari ini.</p>
                    </div>
                    <span class="text-3xl font-extrabold text-brand-600"><?= $progress ?>%</span>
                </div>
                <!-- Progress Bar Utama -->
                <div class="w-full bg-gray-100 rounded-full h-3 mb-10 overflow-hidden">
                    <div class="bg-brand-600 h-3 rounded-full transition-all duration-1000 ease-out" style="width: <?= $progress ?>%;"></div>
                </div>

                <?php
                $isStep1 = in_array($workflow['status'] ?? 'draft', ['draft', 'sekretaris', 'mapel', 'selesai']);
                $isStep2 = in_array($workflow['status'] ?? 'draft', ['mapel', 'selesai']);
                $isStep3 = (($workflow['status'] ?? 'draft') == 'selesai');
                ?>
                <!-- Steps Indicator -->
                <div class="relative w-full pb-10">
                    <div class="absolute left-[16%] right-[16%] top-5 h-1 bg-gray-100 -z-0 rounded-full"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex flex-col items-center justify-center relative w-1/3 text-center">
                            <div class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center border-4 transition-colors duration-300 <?= $isStep1 ? 'bg-brand-600 border-brand-100 text-white shadow-md' : 'bg-white border-gray-200 text-gray-300' ?>"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg></div>
                            <span class="absolute top-12 text-[11px] sm:text-xs font-bold <?= $isStep1 ? 'text-brand-700' : 'text-gray-400' ?> whitespace-nowrap">Sekretaris</span>
                        </div>
                        <div class="flex flex-col items-center justify-center relative w-1/3 text-center">
                            <div class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center border-4 transition-colors duration-300 <?= $isStep2 ? 'bg-brand-600 border-brand-100 text-white shadow-md' : 'bg-white border-gray-200 text-gray-300' ?>"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg></div>
                            <span class="absolute top-12 text-[11px] sm:text-xs font-bold <?= $isStep2 ? 'text-brand-700' : 'text-gray-400' ?> whitespace-nowrap">Guru / Wali</span>
                        </div>
                        <div class="flex flex-col items-center justify-center relative w-1/3 text-center">
                            <div class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center border-4 transition-colors duration-300 <?= $isStep3 ? 'bg-brand-600 border-brand-100 text-white shadow-md' : 'bg-white border-gray-200 text-gray-300' ?>"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg></div>
                            <span class="absolute top-12 text-[11px] sm:text-xs font-bold <?= $isStep3 ? 'text-brand-700' : 'text-gray-400' ?> whitespace-nowrap">Guru Piket</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ================= CALL TO ACTION ================= -->
        <section class="flex pt-4 pb-12 transform transition-all duration-700 delay-300 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <a href="index.php?route=sekretaris/presensi" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-8 py-4 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700 shadow-lg shadow-brand-500/30 transition-all duration-300 hover:-translate-y-0.5 focus:ring-4 focus:ring-brand-500/30 outline-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
                Mulai Presensi Kelas
            </a>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>