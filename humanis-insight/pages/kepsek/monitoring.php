<?php

/**
 * Halaman Monitoring Seluruh Bidang (Kepala Sekolah) - Versi Lengkap & Komprehensif
 * Diperbarui: Menampilkan Data Realisasi Aktual dan Kalkulasi Ketercapaian Target
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */

// Konfigurasi Filter, Search, & Pagination
$selected_bidang = $_GET['bidang'] ?? 'Semua';
$search          = $_GET['search'] ?? '';
$limit           = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page            = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset          = ($page - 1) * $limit;

try {
    // 1. Query Dasar & Filter (Fokus pada Tabel Indikator sebagai Base)
    $whereClauses = [];
    $params = [];

    if ($selected_bidang !== 'Semua') {
        $whereClauses[] = "i.bidang = :bidang";
        $params[':bidang'] = $selected_bidang;
    }

    if (!empty($search)) {
        // Tambahkan pencarian nilai_humanis
        $whereClauses[] = "(i.indikator LIKE :search OR i.pic LIKE :search OR i.bidang LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(' AND ', $whereClauses) : "";

    // 2. Hitung Total Data Indikator (untuk pagination)
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_indikator i $whereSql");
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // 3. Ambil Data Indikator di-LEFT JOIN dengan Pelaporan Terakhir (Max ID)
    // Ditambahkan: p.realisasi dan i.jenis_target
    $sql = "SELECT 
                i.id_indikator, i.indikator, i.bidang, i.pic as nama_pic, i.target, i.satuan, i.jenis_target,
                p.capaian, p.realisasi, p.status_verifikasi, p.bukti_gdrive, p.nilai_humanis
            FROM tb_indikator i 
            LEFT JOIN (
                SELECT p1.* 
                FROM tb_pelaporan p1
                INNER JOIN (
                    SELECT id_indikator, MAX(id_pelaporan) as max_id 
                    FROM tb_pelaporan 
                    GROUP BY id_indikator
                ) p2 ON p1.id_pelaporan = p2.max_id
            ) p ON i.id_indikator = p.id_indikator
            $whereSql 
            ORDER BY i.bidang ASC, i.pic ASC 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Ambil daftar bidang untuk Dropdown Filter
    $stmtBidang = $pdo->query("SELECT DISTINCT bidang FROM tb_indikator WHERE bidang IS NOT NULL AND bidang != '' ORDER BY bidang ASC");
    $listBidang = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);

    // 5. Statistik Cepat untuk Kepsek (Mengabaikan pagination, menghitung total keseluruhan)
    $stmtStats = $pdo->query("
        SELECT 
            COUNT(i.id_indikator) as total_kpi,
            SUM(CASE WHEN p.id_pelaporan IS NOT NULL THEN 1 ELSE 0 END) as kpi_berjalan,
            SUM(CASE WHEN p.id_pelaporan IS NULL THEN 1 ELSE 0 END) as kpi_kosong,
            AVG(IFNULL(p.capaian, 0)) as rata_capaian
        FROM tb_indikator i
        LEFT JOIN (
            SELECT id_indikator, MAX(id_pelaporan) as max_id 
            FROM tb_pelaporan 
            GROUP BY id_indikator
        ) p_max ON i.id_indikator = p_max.id_indikator
        LEFT JOIN tb_pelaporan p ON p_max.max_id = p.id_pelaporan
    ");
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $laporan = [];
    $listBidang = [];
    $totalPages = 1;
    $stats = ['total_kpi' => 0, 'kpi_berjalan' => 0, 'kpi_kosong' => 0, 'rata_capaian' => 0];
}
?>

<div class="mb-8">
    <h2 class="text-2xl font-display font-bold text-slate-900">Monitoring Seluruh Bidang</h2>
    <p class="text-sm text-slate-500 mt-1">Pantau progres tahapan, hasil realisasi angka, dan kinerja setiap bidang secara mendetail.</p>
</div>

<!-- Statistik Kartu Ringkasan -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total KPI Sekolah</p>
        <h3 class="text-2xl font-bold text-slate-900 mt-1"><?= $stats['total_kpi'] ?></h3>
        <span class="text-xs text-slate-500">Keseluruhan target kinerja</span>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-rata Progres</p>
        <h3 class="text-2xl font-bold text-primary mt-1"><?= round($stats['rata_capaian'], 1) ?>%</h3>
        <span class="text-xs text-emerald-600 font-medium">Dari seluruh indikator</span>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200 border-b-4 border-emerald-500">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">KPI Berjalan</p>
        <h3 class="text-2xl font-bold text-emerald-600 mt-1"><?= $stats['kpi_berjalan'] ?></h3>
        <span class="text-xs text-slate-500">Sudah ada laporan masuk</span>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm ring-1 ring-slate-200 border-b-4 border-red-500">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">KPI Belum Lapor</p>
        <h3 class="text-2xl font-bold text-red-500 mt-1"><?= $stats['kpi_kosong'] ?></h3>
        <span class="text-xs text-slate-500">Masih 0% progres</span>
    </div>
</div>

<!-- Panel Filter, Search & Limit -->
<div class="bg-white rounded-2xl p-4 shadow-sm ring-1 ring-slate-200 mb-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
        <div class="sm:col-span-5">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cari Laporan</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama PIC, Indikator, atau Bidang..." class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Bidang</label>
            <select name="bidang" class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                <option value="Semua">Semua Bidang</option>
                <?php foreach ($listBidang as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= $selected_bidang == $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tampilkan</label>
            <select name="limit" class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 baris</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 baris</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 baris</option>
            </select>
        </div>
        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="w-full bg-slate-900 text-white text-sm font-semibold p-2.5 rounded-xl hover:bg-slate-800 transition-colors">Terapkan Filter</button>
        </div>
    </form>
</div>

<!-- Tabel Monitoring -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4">Bidang & PIC</th>
                    <th class="px-6 py-4">Indikator Kinerja & Nilai HUMANIS</th>
                    <th class="px-4 py-4 text-center">Progres & Realisasi</th>
                    <th class="px-4 py-4 text-center">Status Laporan</th>
                    <th class="px-4 py-4 text-center">Bukti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (count($laporan) > 0): ?>
                    <?php foreach ($laporan as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full bg-blue-50 border border-blue-100 px-2.5 py-0.5 text-[10px] font-semibold text-blue-700 mb-1"><?= htmlspecialchars($row['bidang']) ?></span>
                                <span class="block text-xs font-medium text-slate-900">PIC: <?= htmlspecialchars($row['nama_pic']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-800 font-semibold"><?= htmlspecialchars($row['indikator']) ?></p>
                                <p class="text-[11px] text-slate-500 mt-0.5 mb-1.5">
                                    Target: <strong class="text-slate-700"><?= htmlspecialchars($row['jenis_target'] . ' ' . $row['target'] . ' ' . $row['satuan']) ?></strong>
                                </p>

                                <!-- Tampilan Nilai HUMANIS -->
                                <?php if (!empty($row['nilai_humanis'])): ?>
                                    <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 border border-purple-100 px-2 py-0.5 rounded text-[10px] font-bold">
                                        <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <?= htmlspecialchars($row['nilai_humanis']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($row['capaian'] !== null): ?>
                                    <div class="text-xs text-slate-500 mb-1">Progres: <span class="font-bold text-blue-600"><?= $row['capaian'] ?>%</span></div>

                                    <!-- Menampilkan Realisasi & Status -->
                                    <?php if (isset($row['realisasi']) && trim($row['realisasi']) !== ''): ?>
                                        <div class="text-sm font-bold text-slate-900 mt-1">Realisasi: <?= floatval($row['realisasi']) . ' ' . htmlspecialchars($row['satuan']) ?></div>

                                        <?php
                                        // Kalkulasi Ketercapaian Target
                                        $real = floatval($row['realisasi']);
                                        $tgt = floatval($row['target']);
                                        $jenis = $row['jenis_target'];

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
                                    <span class="text-xs font-semibold text-red-500 bg-red-50 px-2 py-1 rounded-md border border-red-100">0% (Belum Lapor)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if (!empty($row['status_verifikasi'])): ?>
                                    <?php
                                    $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($row['status_verifikasi'] === 'Disetujui') $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($row['status_verifikasi'] === 'Ditolak') $badge = 'bg-red-50 text-red-700 border-red-200';
                                    ?>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold border <?= $badge ?>">
                                        <?= $row['status_verifikasi'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-[11px] text-slate-400 italic">Menunggu PIC</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if (!empty($row['bukti_gdrive'])): ?>
                                    <a href="<?= htmlspecialchars($row['bukti_gdrive']) ?>" target="_blank" class="inline-flex items-center text-[11px] font-semibold text-blue-600 hover:underline bg-blue-50 px-2 py-1 rounded border border-blue-100">Buka Link</a>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">Tidak ada data indikator yang ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="bg-white rounded-2xl p-4 shadow-sm ring-1 ring-slate-200 flex items-center justify-between">
        <span class="text-xs text-slate-500">Menampilkan Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong></span>
        <div class="flex items-center justify-center gap-1">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&bidang=<?= urlencode($selected_bidang) ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&bidang=<?= urlencode($selected_bidang) ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg border <?= $i == $page ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&bidang=<?= urlencode($selected_bidang) ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../../includes/kepsek_footer.php'; ?>