<?php

/**
 * Halaman Kelola Master Data Indikator / KPI (Kepala Sekolah)
 * Termasuk Search, Filtering, Pagination, Checklist, & Bulk Delete
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */

$pesan_sukses = '';
$pesan_error = '';

if (isset($_GET['status']) && $_GET['status'] === 'sukses') {
    $pesan_sukses = "Proses import data indikator berhasil dilakukan (" . ($_GET['berhasil'] ?? 0) . " data masuk).";
}
if (isset($_GET['status']) && $_GET['status'] === 'error') {
    $pesan_error = "Terjadi kesalahan: " . ($_GET['pesan'] ?? '');
}

// CRUD & BULK DELETE HANDLING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    // HAPUS BERDASARKAN CHECKLIST (BULK DELETE)
    if ($aksi === 'hapus_banyak') {
        $ids = $_POST['selected_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM tb_indikator WHERE id_indikator IN ($placeholders)");
                $stmt->execute($ids);
                $pesan_sukses = count($ids) . " data indikator berhasil dihapus.";
            } catch (Exception $e) {
                $pesan_error = "Gagal menghapus data terpilih: " . $e->getMessage();
            }
        } else {
            $pesan_error = "Tidak ada indikator yang dipilih untuk dihapus.";
        }
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $bidang       = trim($_POST['bidang']);
        $indikator    = trim($_POST['indikator']);
        $bobot        = floatval($_POST['bobot']);
        $target       = floatval($_POST['target']);
        $jenis_target = trim($_POST['jenis_target']);
        $satuan       = trim($_POST['satuan']);
        $prioritas    = trim($_POST['prioritas']);
        $frekuensi    = trim($_POST['frekuensi']);
        $pic          = trim($_POST['pic']);

        try {
            if ($aksi === 'tambah') {
                $stmt = $pdo->prepare("INSERT INTO tb_indikator (bidang, indikator, bobot, target, jenis_target, satuan, prioritas, frekuensi, pic) VALUES (:bidang, :indikator, :bobot, :target, :jenis_target, :satuan, :prioritas, :frekuensi, :pic)");
                $stmt->execute([':bidang' => $bidang, ':indikator' => $indikator, ':bobot' => $bobot, ':target' => $target, ':jenis_target' => $jenis_target, ':satuan' => $satuan, ':prioritas' => $prioritas, ':frekuensi' => $frekuensi, ':pic' => $pic]);
                $pesan_sukses = "Indikator kinerja (KPI) berhasil ditambahkan.";
            } else {
                $stmt = $pdo->prepare("UPDATE tb_indikator SET bidang = :bidang, indikator = :indikator, bobot = :bobot, target = :target, jenis_target = :jenis_target, satuan = :satuan, prioritas = :prioritas, frekuensi = :frekuensi, pic = :pic WHERE id_indikator = :id");
                $stmt->execute([':bidang' => $bidang, ':indikator' => $indikator, ':bobot' => $bobot, ':target' => $target, ':jenis_target' => $jenis_target, ':satuan' => $satuan, ':prioritas' => $prioritas, ':frekuensi' => $frekuensi, ':pic' => $pic, ':id' => $_POST['id_indikator']]);
                $pesan_sukses = "Indikator kinerja (KPI) berhasil diperbarui.";
            }
        } catch (Exception $e) {
            $pesan_error = "Gagal memproses data: " . $e->getMessage();
        }
    } elseif ($aksi === 'hapus') {
        try {
            $stmt = $pdo->prepare("DELETE FROM tb_indikator WHERE id_indikator = :id");
            $stmt->execute([':id' => $_POST['id_indikator']]);
            $pesan_sukses = "Indikator berhasil dihapus.";
        } catch (Exception $e) {
            $pesan_error = "Gagal menghapus: " . $e->getMessage();
        }
    }
}

// ==========================================
// SEARCH, FILTERING, & PAGINATION SETUP
// ==========================================
$search        = trim($_GET['search'] ?? '');
$filter_bidang = trim($_GET['bidang'] ?? '');
$filter_prio   = trim($_GET['prioritas'] ?? '');
// Limit sekarang dinamis berdasarkan input user (default 10)
$limit         = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
$page          = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page          = max($page, 1);
$offset        = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(indikator LIKE :search OR pic LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($filter_bidang)) {
    $whereClauses[] = "bidang = :bidang";
    $params[':bidang'] = $filter_bidang;
}
if (!empty($filter_prio)) {
    $whereClauses[] = "prioritas = :prioritas";
    $params[':prioritas'] = $filter_prio;
}

$sqlWhere = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";

try {
    // Hitung total data untuk pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_indikator $sqlWhere");
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // Ambil data sesuai filter dan limit pagination
    $sqlData = "SELECT * FROM tb_indikator $sqlWhere ORDER BY bidang ASC, id_indikator ASC LIMIT :limit OFFSET :offset";
    $stmtData = $pdo->prepare($sqlData);
    foreach ($params as $key => $val) {
        $stmtData->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtData->execute();
    $listIndikator = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // Ambil master bidang dan PIC untuk dropdown filter & form
    $listBidang = $pdo->query("SELECT Bidang FROM tb_bidang ORDER BY Bidang ASC")->fetchAll(PDO::FETCH_COLUMN);
    $listPic = $pdo->query("SELECT nama_lengkap FROM tb_users ORDER BY nama_lengkap ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $listIndikator = [];
    $listBidang = [];
    $listPic = [];
    $totalPages = 1;
}
?>

<div x-data="{ 
    modalOpen: false, 
    modalImportOpen: false,
    modalType: 'tambah', 
    form: { id_indikator: '', bidang: '<?= !empty($listBidang) ? $listBidang[0] : '' ?>', indikator: '', bobot: '', target: '', jenis_target: '≥', satuan: '%', prioritas: 'P1', frekuensi: 'Semester', pic: '<?= !empty($listPic) ? $listPic[0] : '' ?>' },
    selectAll: false,
    selected: [],
    toggleAll() {
        if (this.selectAll) {
            this.selected = [<?php foreach ($listIndikator as $ind) {
                                    echo "'" . $ind['id_indikator'] . "',";
                                } ?>];
        } else {
            this.selected = [];
        }
    }
}">

    <!-- Header & Tombol Aksi -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-display font-bold text-slate-900">Master Data Indikator / KPI</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola target sasaran mutu terintegrasi dengan data bidang, PIC, serta fitur Excel.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="../../actions/download_template_indikator.php" class="inline-flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 text-sm font-semibold transition-colors">Template Excel</a>
            <button @click="modalImportOpen = true" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-sm font-semibold transition-colors shadow-sm">Import Excel</button>
            <a href="../../actions/export_indikator.php" class="inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 text-sm font-semibold transition-colors shadow-sm">Export Excel</a>
            <button @click="modalType = 'tambah'; form = { id_indikator: '', bidang: '<?= !empty($listBidang) ? $listBidang[0] : '' ?>', indikator: '', bobot: '', target: '', jenis_target: '≥', satuan: '%', prioritas: 'P1', frekuensi: 'Semester', pic: '<?= !empty($listPic) ? $listPic[0] : '' ?>' }; modalOpen = true" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 transition-colors">Tambah Indikator Baru</button>
        </div>
    </div>

    <!-- Notifikasi -->
    <?php if (!empty($pesan_sukses)): ?><div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200"><?= $pesan_sukses ?></div><?php endif; ?>
    <?php if (!empty($pesan_error)): ?><div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200"><?= $pesan_error ?></div><?php endif; ?>

    <!-- Bagian Search, Filter, Limit & Tombol Hapus Massal -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4 mb-6 flex flex-col xl:flex-row items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3 w-full xl:w-auto flex-1">
            <!-- Menampilkan Data Per Halaman -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-600">Tampilkan:</span>
                <select name="limit" onchange="this.form.submit()" class="rounded-lg border border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary bg-slate-50">
                    <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span class="text-sm font-medium text-slate-600 mr-2">Entri</span>
            </div>

            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari indikator atau PIC..." class="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-primary focus:border-primary">
            </div>

            <div>
                <select name="bidang" class="rounded-lg border border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary">
                    <option value="">Semua Bidang</option>
                    <?php foreach ($listBidang as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $filter_bidang === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <select name="prioritas" class="rounded-lg border border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary">
                    <option value="">Semua Prioritas</option>
                    <option value="P1" <?= $filter_prio === 'P1' ? 'selected' : '' ?>>P1</option>
                    <option value="P2" <?= $filter_prio === 'P2' ? 'selected' : '' ?>>P2</option>
                    <option value="P3" <?= $filter_prio === 'P3' ? 'selected' : '' ?>>P3</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-900 transition-colors">Filter</button>
                <?php if (!empty($search) || !empty($filter_bidang) || !empty($filter_prio) || $limit !== 10): ?>
                    <a href="master_data.php" class="bg-slate-100 text-slate-600 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-slate-200">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tombol Aksi Hapus Terpilih (Checklist) -->
        <div x-show="selected.length > 0" style="display: none;" class="w-full md:w-auto">
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus indikator yang dicentang secara permanen?');">
                <input type="hidden" name="aksi" value="hapus_banyak">
                <template x-for="id in selected">
                    <input type="hidden" name="selected_ids[]" :value="id">
                </template>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Hapus Terpilih (<span x-text="selected.length"></span>)
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-700">
                    <tr>
                        <th class="px-4 py-4 w-10 text-center">
                            <input type="checkbox" @change="toggleAll()" x-model="selectAll" class="rounded border-slate-300 text-primary focus:ring-primary">
                        </th>
                        <th class="px-4 py-3">Bidang</th>
                        <th class="px-4 py-3">Indikator / Program</th>
                        <th class="px-4 py-3 text-center">Bobot</th>
                        <th class="px-4 py-3 text-center">Target</th>
                        <th class="px-4 py-3 text-center">Prioritas</th>
                        <th class="px-4 py-3">Frekuensi</th>
                        <th class="px-4 py-3">PIC</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($listIndikator) > 0): ?>
                        <?php foreach ($listIndikator as $ind): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" value="<?= $ind['id_indikator'] ?>" x-model="selected" class="rounded border-slate-300 text-primary focus:ring-primary">
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900"><?= htmlspecialchars($ind['bidang']) ?></td>
                                <td class="px-4 py-3 text-slate-800"><?= htmlspecialchars($ind['indikator']) ?></td>
                                <td class="px-4 py-3 text-center"><?= $ind['bobot'] ?>%</td>
                                <td class="px-4 py-3 text-center"><?= $ind['jenis_target'] . ' ' . $ind['target'] . ' ' . $ind['satuan'] ?></td>
                                <td class="px-4 py-3 text-center"><span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-bold bg-slate-100 text-slate-700"><?= htmlspecialchars($ind['prioritas']) ?></span></td>
                                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($ind['frekuensi']) ?></td>
                                <td class="px-4 py-3 font-medium text-primary"><?= htmlspecialchars($ind['pic']) ?></td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <button @click="modalType = 'edit'; form = { id_indikator: '<?= $ind['id_indikator'] ?>', bidang: '<?= addslashes($ind['bidang']) ?>', indikator: '<?= addslashes($ind['indikator']) ?>', bobot: '<?= $ind['bobot'] ?>', target: '<?= $ind['target'] ?>', jenis_target: '<?= addslashes($ind['jenis_target'] ?? '=') ?>', satuan: '<?= addslashes($ind['satuan']) ?>', prioritas: '<?= addslashes($ind['prioritas']) ?>', frekuensi: '<?= addslashes($ind['frekuensi']) ?>', pic: '<?= addslashes($ind['pic']) ?>' }; modalOpen = true" class="text-blue-600 font-semibold text-xs hover:underline">Edit</button>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Hapus indikator ini?');">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_indikator" value="<?= $ind['id_indikator'] ?>">
                                        <button type="submit" class="text-red-600 font-semibold text-xs hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">Belum ada master data indikator yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between bg-white px-6 py-4 rounded-2xl shadow-sm ring-1 ring-slate-200 mb-8">
            <div class="text-xs text-slate-500">
                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalData ?> data)
            </div>
            <div class="flex items-center space-x-1">
                <?php
                // Menambahkan $limit pada string pencarian untuk mempertahankan batas per halaman di link pagination
                $queryString = "&search=" . urlencode($search) . "&bidang=" . urlencode($filter_bidang) . "&prioritas=" . urlencode($filter_prio) . "&limit=" . $limit;
                ?>
                <?php if ($page > 1): ?>
                    <a href="master_data.php?page=<?= $page - 1 ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">Sebelumnya</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="master_data.php?page=<?= $i ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="master_data.php?page=<?= $page + 1 ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">Berikutnya</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL IMPORT EXCEL -->
    <div x-show="modalImportOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalImportOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalImportOpen = false"></div>
            <div x-show="modalImportOpen" x-transition class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-2">Import Data Indikator (.xlsx)</h3>
                
                <form action="../../actions/import_indikator.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <!-- Tambahan: Input tersembunyi untuk mengatur ke mana sistem akan dialihkan setelah sukses -->
                    <input type="hidden" name="redirect" value="../pages/kepsek/master_data.php">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pilih Berkas Excel</label>
                        <input type="file" name="file_excel" accept=".xlsx, .xls" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1">
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="modalImportOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm">Unggah & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none;">
        <div class="bg-white w-full max-w-lg p-6 rounded-2xl max-h-[90vh] overflow-y-auto shadow-xl">
            <h3 class="font-bold text-lg mb-4 text-slate-900" x-text="modalType === 'tambah' ? 'Tambah Indikator Baru' : 'Edit Indikator'"></h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="aksi" :value="modalType">
                <input type="hidden" name="id_indikator" :value="form.id_indikator">
                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Bidang Struktural</label>
                    <select name="bidang" x-model="form.bidang" required class="w-full rounded-lg border-slate-300 p-2.5 text-sm">
                        <?php foreach ($listBidang as $b): ?><option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Indikator / Program</label>
                    <textarea name="indikator" x-model="form.indikator" rows="2" required class="w-full rounded-lg border-slate-300 p-2.5 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div><label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Bobot (%)</label><input type="number" step="0.01" name="bobot" x-model="form.bobot" required class="w-full rounded-lg border-slate-300 p-2.5 text-sm"></div>
                    <div><label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Jenis</label><select name="jenis_target" x-model="form.jenis_target" class="w-full rounded-lg border-slate-300 p-2.5 text-sm">
                            <option value="=">=</option>
                            <option value="≥">≥</option>
                            <option value="≤">≤</option>
                        </select></div>
                    <div><label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Target</label><input type="number" step="0.01" name="target" x-model="form.target" required class="w-full rounded-lg border-slate-300 p-2.5 text-sm"></div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div><label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Satuan</label><input type="text" name="satuan" x-model="form.satuan" required class="w-full rounded-lg border-slate-300 p-2.5 text-sm"></div>
                    <div><label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Prioritas</label><select name="prioritas" x-model="form.prioritas" class="w-full rounded-lg border-slate-300 p-2.5 text-sm">
                            <option value="P1">P1</option>
                            <option value="P2">P2</option>
                            <option value="P3">P3</option>
                        </select></div>
                    <div><label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Frekuensi</label><select name="frekuensi" x-model="form.frekuensi" class="w-full rounded-lg border-slate-300 p-2.5 text-sm">
                            <option value="Bulanan">Bulanan</option>
                            <option value="Triwulan">Triwulan</option>
                            <option value="Semester">Semester</option>
                            <option value="Tahunan">Tahunan</option>
                        </select></div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">PIC (Penanggung Jawab)</label>
                    <select name="pic" x-model="form.pic" required class="w-full rounded-lg border-slate-300 p-2.5 text-sm">
                        <?php foreach ($listPic as $p): ?><option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm bg-slate-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-primary text-white rounded-lg" x-text="modalType === 'tambah' ? 'Simpan' : 'Update'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/kepsek_footer.php'; ?>