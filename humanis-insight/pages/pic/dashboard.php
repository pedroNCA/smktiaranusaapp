<?php
/**
 * Halaman Dashboard PIC / Guru
 */
require_once '../../includes/pic_header.php';
require_once '../../includes/pic_sidebar.php';
require_once '../../includes/pic_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */
$nama_pic = $_SESSION['nama'] ?? '';
$bidang_pic = $_SESSION['bidang'] ?? 'Semua';

// Statistik Ringkas untuk PIC ini
try {
    $stmtIndikator = $pdo->prepare("SELECT COUNT(*) FROM tb_indikator WHERE pic = :nama");
    $stmtIndikator->execute([':nama' => $nama_pic]);
    $totalIndikator = $stmtIndikator->fetchColumn();

    $stmtLaporan = $pdo->prepare("SELECT COUNT(*) FROM tb_pelaporan WHERE nama_pic = :nama");
    $stmtLaporan->execute([':nama' => $nama_pic]);
    $totalLaporan = $stmtLaporan->fetchColumn();

    // Ambil daftar laporan terbaru yang diinput oleh PIC ini
    $stmtList = $pdo->prepare("SELECT p.*, i.indikator FROM tb_pelaporan p JOIN tb_indikator i ON p.id_indikator = i.id_indikator WHERE p.nama_pic = :nama ORDER BY p.id_pelaporan DESC LIMIT 5");
    $stmtList->execute([':nama' => $nama_pic]);
    $listTerbaru = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $totalIndikator = 0;
    $totalLaporan = 0;
    $listTerbaru = [];
}
?>

<!-- Welcome Banner -->
<div class="mb-8 bg-gradient-to-r from-blue-900 to-primary rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <span class="inline-block px-3 py-1 rounded-full bg-blue-800/60 text-blue-200 text-xs font-semibold mb-3 border border-blue-700">Bidang: <?= htmlspecialchars($bidang_pic) ?></span>
        <h2 class="text-2xl sm:text-3xl font-display font-bold">Selamat Datang, <?= htmlspecialchars($nama_pic) ?>!</h2>
        <p class="text-sm text-blue-100 mt-2">Pantau capaian indikator kinerja dan kelola pelaporan penjaminan mutu bidang Anda dengan mudah.</p>
    </div>
    <a href="pelaporan.php" class="inline-flex items-center justify-center bg-white text-primary text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-blue-50 transition-colors shadow-sm shrink-0">
        Input Laporan Baru &rarr;
    </a>
</div>

<!-- Statistik Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-slate-200 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Indikator (KPI)</p>
            <h3 class="text-2xl font-display font-bold text-slate-900 mt-1"><?= $totalIndikator ?></h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-primary flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-slate-200 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Laporan Terkirim</p>
            <h3 class="text-2xl font-display font-bold text-slate-900 mt-1"><?= $totalLaporan ?></h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-slate-200 flex items-center justify-between sm:col-span-2 lg:col-span-1">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status Akun PIC</p>
            <h3 class="text-lg font-display font-bold text-emerald-600 mt-1">Aktif & Terverifikasi</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
</div>

<!-- Tabel Aktivitas / Laporan Terbaru -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-base font-display font-bold text-slate-900">Aktivitas Laporan Terbaru</h3>
        <a href="pelaporan.php" class="text-xs font-semibold text-primary hover:underline">Lihat Semua &rarr;</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-3">Indikator</th>
                    <th class="px-4 py-3 text-center">Capaian</th>
                    <th class="px-4 py-3">Bukti GDrive</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (count($listTerbaru) > 0): ?>
                    <?php foreach ($listTerbaru as $lap): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900"><?= htmlspecialchars($lap['indikator']) ?></td>
                            <td class="px-4 py-3 text-center font-bold text-primary"><?= $lap['capaian'] ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($lap['bukti_gdrive'])): ?>
                                    <a href="<?= htmlspecialchars($lap['bukti_gdrive']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline font-semibold">Buka Link</a>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                    <?= htmlspecialchars($lap['status_verifikasi']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada aktivitas laporan yang dimasukkan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/pic_footer.php'; ?>