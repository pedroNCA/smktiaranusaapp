<?php

require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Services/DashboardService.php';

Auth::admin();

$tanggal_hari_ini = date('Y-m-d');

$dashboard = DashboardService::dashboardAdmin($conn);

$summary      = $dashboard['summary'];
$attendance   = $dashboard['attendance'];
$distribution = $dashboard['distribution'];
$verification = $dashboard['verification'];
$percentage   = $dashboard['percentage'];

$totalVerified = 0;

foreach ($verification as $item) {
    if ($item['status'] == 'selesai') {
        $totalVerified++;
    }
}

$totalClass = count($distribution);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PRESTA</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
</head>

<body class="bg-slate-50 antialiased text-gray-800">

    <!-- Wrapper Utama Layout Admin -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <!-- Konten Utama di Sebelah Kanan (Topbar + Isi Halaman) -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Topbar -->
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <!-- Area Konten Utama (Scrollable) -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Dashboard Header -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Dashboard Administrator
                            </h1>
                            <p class="text-xs text-gray-500 mt-1">
                                Monitoring Presensi Seluruh Siswa &bull; <?= date('d F Y', strtotime($tanggal_hari_ini)); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Summary Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Total Siswa -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Siswa</span>
                                <h2 class="text-xl font-extrabold text-gray-900 mt-0.5"><?= number_format($summary['total_siswa']) ?></h2>
                            </div>
                        </div>

                        <!-- Total Guru -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M22 10v6M2 10v6" />
                                    <path d="M12 2L2 7l10 5 10-5-10-5z" />
                                    <path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Guru</span>
                                <h2 class="text-xl font-extrabold text-gray-900 mt-0.5"><?= number_format($summary['total_guru']) ?></h2>
                            </div>
                        </div>

                        <!-- Total Kelas -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <path d="M7 8h10M7 12h10M7 16h6" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Kelas</span>
                                <h2 class="text-xl font-extrabold text-gray-900 mt-0.5"><?= number_format($summary['total_kelas']) ?></h2>
                            </div>
                        </div>

                        <!-- Tahun Pelajaran -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 10l9-7 9 7" />
                                    <path d="M9 22V12h6v10" />
                                    <path d="M5 10v12h14V10" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Tahun Pelajaran</span>
                                <h2 class="text-sm font-extrabold text-gray-900 mt-1"><?= htmlspecialchars($summary['tahun_pelajaran']) ?></h2>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <span class="text-xs text-gray-400 font-bold uppercase">Hadir</span>
                            <h2 class="text-xl font-extrabold text-green-600 mt-1"><?= $attendance['hadir'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <span class="text-xs text-gray-400 font-bold uppercase">Izin</span>
                            <h2 class="text-xl font-extrabold text-blue-600 mt-1"><?= $attendance['izin'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <span class="text-xs text-gray-400 font-bold uppercase">Sakit</span>
                            <h2 class="text-xl font-extrabold text-amber-500 mt-1"><?= $attendance['sakit'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <span class="text-xs text-gray-400 font-bold uppercase">Alfa</span>
                            <h2 class="text-xl font-extrabold text-red-600 mt-1"><?= $attendance['alfa'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <span class="text-xs text-gray-400 font-bold uppercase">Terlambat</span>
                            <h2 class="text-xl font-extrabold text-purple-600 mt-1"><?= $attendance['terlambat'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <span class="text-xs text-gray-400 font-bold uppercase">Belum Diisi</span>
                            <h2 class="text-xl font-extrabold text-gray-500 mt-1"><?= $attendance['belum'] ?? 0 ?></h2>
                        </div>
                    </div>

                    <!-- Progress Overview -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Progress Verifikasi Hari Ini</h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <?= $totalVerified ?> dari <?= $totalClass ?> kelas telah selesai diverifikasi
                                </p>
                            </div>
                            <span class="text-2xl font-extrabold text-brand-600">
                                <?= $totalClass > 0 ? round(($totalVerified / $totalClass) * 100) : 0 ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div class="bg-brand-600 h-3 rounded-full transition-all duration-500"
                                style="width:<?= $totalClass > 0 ? (($totalVerified / $totalClass) * 100) : 0 ?>%">
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring Verifikasi Kelas -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Monitoring Verifikasi Kelas</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Pantau proses verifikasi presensi seluruh kelas secara realtime.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($distribution as $kelas): ?>
                                <?php
                                $workflow = null;
                                foreach ($verification as $item) {
                                    if ($item['kelas_id'] == $kelas['id']) {
                                        $workflow = $item;
                                        break;
                                    }
                                }

                                $progress = 0;
                                $statusText = "Belum Diproses";
                                $statusClass = "bg-gray-50 text-gray-600 border-gray-200";
                                $sekretaris = false;
                                $mapel = false;
                                $piket = false;

                                if ($workflow) {
                                    switch ($workflow['status']) {
                                        case 'sekretaris':
                                            $progress = 33;
                                            $sekretaris = true;
                                            $statusText = "Menunggu Guru/Walas";
                                            $statusClass = "bg-blue-50 text-blue-700 border-blue-100";
                                            break;
                                        case 'mapel':
                                            $progress = 66;
                                            $sekretaris = true;
                                            $mapel = true;
                                            $statusText = "Menunggu Guru Piket";
                                            $statusClass = "bg-amber-50 text-amber-700 border-amber-100";
                                            break;
                                        case 'selesai':
                                            $progress = 100;
                                            $sekretaris = true;
                                            $mapel = true;
                                            $piket = true;
                                            $statusText = "Selesai";
                                            $statusClass = "bg-emerald-50 text-emerald-700 border-emerald-100";
                                            break;
                                    }
                                }
                                ?>

                                <div class="p-5 rounded-2xl border border-gray-100 bg-slate-50/50 flex flex-col justify-between gap-4 shadow-sm hover:shadow transition-shadow">
                                    <div class="flex justify-between items-start gap-2">
                                        <h4 class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($kelas['nama_kelas']) ?></h4>
                                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 font-medium">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                        </svg>
                                        <span><?= $kelas['total_siswa'] ?> Siswa</span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-semibold">
                                        <div class="p-1.5 rounded-lg <?= $sekretaris ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' ?>">
                                            Sekretaris
                                        </div>
                                        <div class="p-1.5 rounded-lg <?= $mapel ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' ?>">
                                            Walas/Guru
                                        </div>
                                        <div class="p-1.5 rounded-lg <?= $piket ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' ?>">
                                            Guru Piket
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <div class="flex justify-between text-xs font-bold text-gray-600">
                                            <span>Progress</span>
                                            <span><?= $progress ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-brand-600 h-2 rounded-full" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>

                                    <a href="index.php?route=admin/presensi_detail&kelas_id=<?= $kelas['id'] ?>" class="w-full py-2 bg-white border border-gray-200 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 text-gray-700 text-xs font-bold rounded-xl text-center transition-colors shadow-sm">
                                        Lihat Detail
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>

    </div>


</body>

</html>