<?php

/**
 * Halaman Pantauan / Rekap Kinerja PIC (1 Baris per Indikator dengan Laporan Terbaru & Indikator Kosong)
 * Diperbarui: Mendukung tampilan Realisasi Aktual & Status Ketercapaian
 */
require_once '../../includes/pic_header.php';
require_once '../../includes/pic_sidebar.php';
require_once '../../includes/pic_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */
$nama_pic = $_SESSION['nama'] ?? '';

try {
    // Statistik Ringkasan
    // 1. Total Indikator Tanggung Jawab
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tb_indikator WHERE pic = :nama");
    $stmtTotal->execute([':nama' => $nama_pic]);
    $totalIndikator = $stmtTotal->fetchColumn();

    // 2. Rata-rata Capaian Tahapan PIC (dari laporan yang disetujui)
    $stmtAvg = $pdo->prepare("SELECT AVG(p.capaian) FROM tb_pelaporan p JOIN tb_indikator i ON p.id_indikator = i.id_indikator WHERE p.nama_pic = :nama AND p.status_verifikasi = 'Disetujui'");
    $stmtAvg->execute([':nama' => $nama_pic]);
    $avgCapaian = round($stmtAvg->fetchColumn() ?: 0, 1);

    // 3. Status Laporan (Disetujui, Menunggu, Ditolak)
    $stmtStatus = $pdo->prepare("SELECT status_verifikasi, COUNT(*) as jumlah FROM tb_pelaporan WHERE nama_pic = :nama GROUP BY status_verifikasi");
    $stmtStatus->execute([':nama' => $nama_pic]);
    $statusCounts = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

    $jmlDisetujui = $statusCounts['Disetujui'] ?? 0;
    $jmlMenunggu  = $statusCounts['Menunggu Persetujuan'] ?? 0;
    $jmlDitolak   = $statusCounts['Ditolak'] ?? 0;

    // Ambil Daftar Indikator PIC, digabung dengan laporan TERBARU saja (1 baris per indikator)
    // Ditambahkan: p.realisasi
    $stmtDetil = $pdo->prepare("
        SELECT 
            i.*, 
            p.capaian, 
            p.realisasi,
            p.nilai_humanis,
            p.status_verifikasi, 
            p.bukti_gdrive, 
            p.refleksi_kendala 
        FROM tb_indikator i 
        LEFT JOIN (
            SELECT p1.* 
            FROM tb_pelaporan p1
            INNER JOIN (
                SELECT id_indikator, MAX(id_pelaporan) as max_id 
                FROM tb_pelaporan 
                WHERE nama_pic = :nama1
                GROUP BY id_indikator
            ) p2 ON p1.id_pelaporan = p2.max_id
        ) p ON i.id_indikator = p.id_indikator
        WHERE i.pic = :nama2
        ORDER BY i.bidang ASC
    ");
    $stmtDetil->execute([':nama1' => $nama_pic, ':nama2' => $nama_pic]);
    $listPantauan = $stmtDetil->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $totalIndikator = 0;
    $avgCapaian = 0;
    $jmlDisetujui = $jmlMenunggu = $jmlDitolak = 0;
    $listPantauan = [];
}
?>

<div class="space-y-6">
    <!-- Header Halaman -->
    <div>
        <h2 class="text-2xl font-display font-bold text-slate-900">Pantauan & Rekap Kinerja</h2>
        <p class="text-sm text-slate-500 mt-1">Ringkasan progres indikator kinerja, hasil realisasi angka, dan status validasi laporan Anda.</p>
    </div>

    <!-- Statistik Kartu Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Indikator</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1"><?= $totalIndikator ?></h3>
            <span class="text-xs text-slate-500">Tanggung jawab bidang</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-rata Progres</p>
            <h3 class="text-2xl font-bold text-primary mt-1"><?= $avgCapaian ?>%</h3>
            <span class="text-xs text-emerald-600 font-medium">Laporan disetujui</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Disetujui</p>
            <h3 class="text-2xl font-bold text-emerald-600 mt-1"><?= $jmlDisetujui ?></h3>
            <span class="text-xs text-slate-500">Tervalidasi pimpinan</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Menunggu Verifikasi</p>
            <h3 class="text-2xl font-bold text-amber-500 mt-1"><?= $jmlMenunggu ?></h3>
            <span class="text-xs text-slate-500">Belum diperiksa</span>
        </div>
    </div>

    <!-- Tabel Rekap Detil Indikator Kinerja -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Daftar Indikator & Status Pencapaian Terakhir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Indikator Kinerja & Nilai HUMANIS</th>
                        <th class="px-4 py-4 text-center">Ketentuan Target</th>
                        <th class="px-4 py-4 text-center">Progres & Realisasi</th>
                        <th class="px-4 py-4 text-center">Status Verifikasi</th>
                        <th class="px-4 py-4 text-center">Bukti Dokumen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($listPantauan) > 0): ?>
                        <?php foreach ($listPantauan as $item): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= htmlspecialchars($item['indikator']) ?>
                                    <div class="text-xs font-normal text-slate-400">Bidang: <?= htmlspecialchars($item['bidang']) ?></div>

                                    <!-- Menampilkan Nilai HUMANIS -->
                                    <?php if (!empty($item['nilai_humanis'])): ?>
                                        <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-100">
                                            Kaitan Nilai: <?= htmlspecialchars($item['nilai_humanis']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-4 text-center text-xs font-medium text-slate-700">
                                    <?= htmlspecialchars($item['jenis_target'] . ' ' . $item['target'] . ' ' . $item['satuan']) ?>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <?php if ($item['capaian'] !== null): ?>
                                        <div class="text-xs text-slate-500 mb-1">Progres Tahap: <span class="font-bold text-blue-600"><?= $item['capaian'] ?>%</span></div>

                                        <!-- Menampilkan Realisasi Angka Aktual & Status -->
                                        <?php if (isset($item['realisasi']) && trim($item['realisasi']) !== ''): ?>
                                            <div class="text-sm font-bold text-slate-900 mt-1">Realisasi: <?= floatval($item['realisasi']) . ' ' . htmlspecialchars($item['satuan']) ?></div>

                                            <?php
                                            // Kalkulasi Ketercapaian Target
                                            $real = floatval($item['realisasi']);
                                            $tgt = floatval($item['target']);
                                            $jenis = $item['jenis_target'];

                                            $status_tercapai = false;
                                            if ($jenis === '≥' && $real >= $tgt) $status_tercapai = true;
                                            if ($jenis === '≤' && $real <= $tgt) $status_tercapai = true;
                                            if ($jenis === '=' && $real == $tgt) $status_tercapai = true;
                                            ?>
                                            <?php if ($status_tercapai): ?>
                                                <div class="mt-1.5 inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Target Tercapai</div>
                                            <?php else: ?>
                                                <div class="mt-1.5 inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">Tidak Tercapai</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="mt-1.5 inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Belum diinput</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Belum Dimulai</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <?php if (!empty($item['status_verifikasi'])): ?>
                                        <?php
                                        $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                        if ($item['status_verifikasi'] === 'Disetujui') $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        if ($item['status_verifikasi'] === 'Ditolak') $badge = 'bg-red-50 text-red-700 border-red-200';
                                        ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border <?= $badge ?>">
                                            <?= htmlspecialchars($item['status_verifikasi']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                            Belum Ada Laporan
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <?php if (!empty($item['bukti_gdrive'])): ?>
                                        <a href="<?= htmlspecialchars($item['bukti_gdrive']) ?>" target="_blank" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:underline bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">Buka GDrive</a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada indikator kinerja yang ditugaskan kepada Anda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/pic_footer.php'; ?>