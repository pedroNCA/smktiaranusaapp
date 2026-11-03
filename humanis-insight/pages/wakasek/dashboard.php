<?php
require_once '../../includes/wakasek_header.php';
require_once '../../includes/wakasek_sidebar.php';
require_once '../../includes/wakasek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */
$nama_saya = $_SESSION['nama'] ?? '';
$bidang_wakasek = $_SESSION['bidang'] ?? '';

try {
    // ==========================================
    // STATISTIK KINERJA PRIBADI WAKASEK
    // ==========================================

    // Total Indikator/KPI Milik Wakasek
    $stmtIndPribadi = $pdo->prepare("SELECT COUNT(*) FROM tb_indikator WHERE pic = :nama");
    $stmtIndPribadi->execute([':nama' => $nama_saya]);
    $total_kpi_saya = $stmtIndPribadi->fetchColumn();

    // Laporan Pribadi - Menunggu
    $stmtMenungguSaya = $pdo->prepare("SELECT COUNT(*) FROM tb_pelaporan WHERE nama_pic = :nama AND status_verifikasi = 'Menunggu Persetujuan'");
    $stmtMenungguSaya->execute([':nama' => $nama_saya]);
    $menunggu_saya = $stmtMenungguSaya->fetchColumn();

    // Laporan Pribadi - Disetujui
    $stmtDisetujuiSaya = $pdo->prepare("SELECT COUNT(*) FROM tb_pelaporan WHERE nama_pic = :nama AND status_verifikasi = 'Disetujui'");
    $stmtDisetujuiSaya->execute([':nama' => $nama_saya]);
    $disetujui_saya = $stmtDisetujuiSaya->fetchColumn();

    // Laporan Pribadi - Ditolak
    $stmtDitolakSaya = $pdo->prepare("SELECT COUNT(*) FROM tb_pelaporan WHERE nama_pic = :nama AND status_verifikasi = 'Ditolak'");
    $stmtDitolakSaya->execute([':nama' => $nama_saya]);
    $ditolak_saya = $stmtDitolakSaya->fetchColumn();
} catch (Exception $e) {
    // Fallback jika error
    $total_kpi_saya = $menunggu_saya = $disetujui_saya = $ditolak_saya = 0;
}
?>

<div class="mb-8 flex flex-col gap-2">
    <h2 class="text-2xl font-display font-bold text-slate-900">Dashboard Wakasek</h2>
    <p class="text-sm text-slate-500">Selamat datang, <span class="font-bold text-slate-700"><?= htmlspecialchars($nama_saya) ?></span>. Pantau target pencapaian dan status pelaporan kinerja pribadi Anda.</p>
</div>

<!-- STATISTIK KINERJA PRIBADI -->
<div class="mb-10">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Rekapitulasi Kinerja Pribadi</h3>
        <a href="pelaporan.php" class="text-xs font-semibold text-primary hover:underline">Kelola Laporan Saya &rarr;</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card: KPI -->
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Target KPI Saya</p>
                    <h4 class="text-3xl font-display font-bold text-slate-900"><?= $total_kpi_saya ?></h4>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-xs text-slate-400">Total indikator yang ditugaskan</span>
            </div>
        </div>

        <!-- Card: Menunggu Persetujuan Kepsek -->
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Menunggu Validasi</p>
                    <h4 class="text-3xl font-display font-bold text-amber-600"><?= $menunggu_saya ?></h4>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center border border-amber-100">
                    <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-xs text-amber-600/70">Dalam antrean Kepala Sekolah</span>
            </div>
        </div>

        <!-- Card: Disetujui -->
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Laporan Disetujui</p>
                    <h4 class="text-3xl font-display font-bold text-emerald-600"><?= $disetujui_saya ?></h4>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center border border-emerald-100">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-xs text-emerald-600/70">Telah valid 100%</span>
            </div>
        </div>

        <!-- Card: Ditolak -->
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Perlu Diperbaiki</p>
                    <h4 class="text-3xl font-display font-bold text-red-600"><?= $ditolak_saya ?></h4>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center border border-red-100">
                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-xs text-red-500/70">Ditolak oleh Kepala Sekolah</span>
            </div>
        </div>
    </div>
</div>


<?php require_once '../../includes/wakasek_footer.php'; ?>