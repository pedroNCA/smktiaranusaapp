<?php
// Memulai output buffering untuk mencegah error "Headers already sent"
ob_start();

/**
 * Halaman Kelola Master Bidang (Dengan Validasi Duplikasi, Search, Pagination, Bulk Delete, Export & Import XLSX)
 * Versi: Kepala Sekolah
 */
require_once '../../config/database.php';

// Memanggil autoload Composer untuk PhpSpreadsheet
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;

/** @var PDO $pdo */

// ==========================================
// BLOK EXPORT & TEMPLATE (Harus dipanggil SEBELUM file UI/HTML)
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == '1') {
    // Bersihkan buffer sebelumnya jika ada
    if (ob_get_length()) ob_clean();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header XLSX
    $sheet->setCellValue('A1', 'ID Bidang');
    $sheet->setCellValue('B1', 'Nama Bidang');

    // Format Header menjadi Bold
    $sheet->getStyle('A1:B1')->getFont()->setBold(true);

    // Ambil data
    $stmt = $pdo->query("SELECT id_bidang, Bidang FROM tb_bidang ORDER BY id_bidang ASC");
    $row = 2;
    while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheet->setCellValue('A' . $row, $data['id_bidang']);
        $sheet->setCellValue('B' . $row, $data['Bidang']);
        $row++;
    }

    // Mengatur auto-width kolom
    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Data_Bidang_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if (isset($_GET['template']) && $_GET['template'] == '1') {
    // Bersihkan buffer sebelumnya jika ada
    if (ob_get_length()) ob_clean();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header XLSX
    $sheet->setCellValue('A1', 'ID Bidang');
    $sheet->setCellValue('B1', 'Nama Bidang');
    $sheet->getStyle('A1:B1')->getFont()->setBold(true);

    // Contoh isi
    $sheet->setCellValue('A2', 'BD01');
    $sheet->setCellValue('B2', 'Bimbingan Konseling');
    $sheet->setCellValue('A3', 'BD02');
    $sheet->setCellValue('B3', 'Humas & Kerjasama');

    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Template_Import_Bidang.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ==========================================
// PEMANGGILAN FILE UI / TAMPILAN KEPALA SEKOLAH
// ==========================================
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';

$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// BLOK PEMROSESAN AKSI (CRUD, BULK DELETE, & IMPORT)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    // IMPORT DATA DARI XLSX
    if ($aksi === 'import') {
        if (isset($_FILES['file_import']) && $_FILES['file_import']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_import']['tmp_name'];

            try {
                $reader = new ReaderXlsx();
                // Membaca file excel yang diupload
                $spreadsheet = $reader->load($file);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();

                $sukses = 0;
                $gagal = 0;
                $stmtInsert = $pdo->prepare("INSERT INTO tb_bidang (id_bidang, Bidang) VALUES (:id, :bidang)");

                // Looping dari index 1 (baris ke-2) untuk melewati header
                for ($i = 1; $i < count($sheetData); $i++) {
                    $id_bidang = trim($sheetData[$i][0] ?? '');
                    $bidang = trim($sheetData[$i][1] ?? '');

                    if (!empty($id_bidang) && !empty($bidang)) {
                        try {
                            $stmtInsert->execute([':id' => $id_bidang, ':bidang' => $bidang]);
                            $sukses++;
                        } catch (Exception $e) {
                            // Gagal karena ID Bidang duplikat
                            $gagal++;
                        }
                    }
                }

                if ($sukses > 0) {
                    $pesan_sukses = "Import selesai! Berhasil menambahkan $sukses data bidang." . ($gagal > 0 ? " ($gagal baris dilewati karena duplikat)." : "");
                } else {
                    $pesan_error = "Tidak ada data baru yang diimport. Pastikan format file dan isinya benar.";
                }
            } catch (Exception $e) {
                $pesan_error = "Error memproses file Excel: " . $e->getMessage();
            }
        } else {
            $pesan_error = "Terjadi kesalahan saat mengunggah file XLSX.";
        }
    }
    // HAPUS BERDASARKAN CHECKLIST (BULK DELETE)
    elseif ($aksi === 'hapus_banyak') {
        $ids = $_POST['selected_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM tb_bidang WHERE id_bidang IN ($placeholders)");
                $stmt->execute($ids);
                $pesan_sukses = count($ids) . " data bidang berhasil dihapus.";
            } catch (Exception $e) {
                $pesan_error = "Gagal menghapus data terpilih: pastikan tidak ada data yang terkait.";
            }
        } else {
            $pesan_error = "Tidak ada bidang yang dipilih untuk dihapus.";
        }
    }
    // TAMBAH BIDANG
    elseif ($aksi === 'tambah') {
        $id_bidang = trim($_POST['id_bidang']);
        $bidang = trim($_POST['bidang']);

        try {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_bidang WHERE id_bidang = :id OR Bidang = :bidang");
            $stmtCheck->execute([':id' => $id_bidang, ':bidang' => $bidang]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Peringatan: ID Bidang '$id_bidang' atau Nama Bidang '$bidang' sudah terdaftar!");
            }

            $stmt = $pdo->prepare("INSERT INTO tb_bidang (id_bidang, Bidang) VALUES (:id, :bidang)");
            $stmt->execute([':id' => $id_bidang, ':bidang' => $bidang]);
            $pesan_sukses = "Data bidang baru berhasil ditambahkan.";
        } catch (Exception $e) {
            $pesan_error = $e->getMessage();
        }
    }
    // EDIT BIDANG
    elseif ($aksi === 'edit') {
        $id_bidang = trim($_POST['id_bidang']);
        $bidang = trim($_POST['bidang']);

        try {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_bidang WHERE Bidang = :bidang AND id_bidang != :id");
            $stmtCheck->execute([':bidang' => $bidang, ':id' => $id_bidang]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Peringatan: Nama Bidang '$bidang' sudah digunakan oleh bidang lain!");
            }

            $stmt = $pdo->prepare("UPDATE tb_bidang SET Bidang = :bidang WHERE id_bidang = :id");
            $stmt->execute([':bidang' => $bidang, ':id' => $id_bidang]);
            $pesan_sukses = "Data bidang berhasil diperbarui.";
        } catch (Exception $e) {
            $pesan_error = $e->getMessage();
        }
    }
    // HAPUS SATUAN
    elseif ($aksi === 'hapus') {
        $id_bidang = trim($_POST['id_bidang']);
        try {
            $stmt = $pdo->prepare("DELETE FROM tb_bidang WHERE id_bidang = :id");
            $stmt->execute([':id' => $id_bidang]);
            $pesan_sukses = "Data bidang berhasil dihapus.";
        } catch (Exception $e) {
            $pesan_error = "Gagal menghapus: pastikan tidak ada data yang terkait.";
        }
    }
}

// ==========================================
// SEARCH & PAGINATION SETUP
// ==========================================
$search = trim($_GET['search'] ?? '');
// Limit sekarang dinamis berdasarkan input user (default 10)
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

$whereSql = "";
$params = [];

if (!empty($search)) {
    $whereSql = "WHERE id_bidang LIKE :search OR Bidang LIKE :search";
    $params[':search'] = "%$search%";
}

try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_bidang $whereSql");
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    $stmtData = $pdo->prepare("SELECT * FROM tb_bidang $whereSql ORDER BY id_bidang ASC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $val) {
        $stmtData->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtData->execute();
    $listBidang = $stmtData->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listBidang = [];
    $totalPages = 1;
}
?>

<div x-data="{ 
    modalOpen: false,
    importModal: false, 
    modalType: 'tambah', 
    form: { id_bidang: '', bidang: '' },
    selectAll: false,
    selected: [],
    toggleAll() {
        if (this.selectAll) {
            this.selected = [<?php foreach ($listBidang as $b) {
                                    echo "'" . $b['id_bidang'] . "',";
                                } ?>];
        } else {
            this.selected = [];
        }
    }
}">

    <!-- Header Halaman -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-display font-bold text-slate-900">Master Data Bidang</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola daftar bidang struktural penjaminan mutu sekolah.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Download Template Excel -->
            <a href="?template=1" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Template XLSX
            </a>

            <!-- Tombol Import -->
            <button @click="importModal = true" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import XLSX
            </button>

            <!-- Tombol Export -->
            <a href="?export=1" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export XLSX
            </a>

            <!-- Tombol Tambah -->
            <button @click="modalType = 'tambah'; form = { id_bidang: '', bidang: '' }; modalOpen = true" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Baru
            </button>
        </div>
    </div>

    <!-- Notifikasi Pop-up Sukses / Error -->
    <?php if (!empty($pesan_sukses)): ?>
        <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?= $pesan_sukses ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?= $pesan_error ?></span>
        </div>
    <?php endif; ?>

    <!-- Bagian Filter Limit & Search, Tombol Hapus Massal -->
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

            <div class="relative flex-1 min-w-[240px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID atau nama bidang..." class="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-primary focus:border-primary">
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-900 transition-colors">Cari</button>
                <?php if (!empty($search) || $limit !== 10): ?>
                    <a href="master_bidang.php" class="bg-slate-100 text-slate-600 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-slate-200">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tombol Hapus Terpilih (Checklist) -->
        <div x-show="selected.length > 0" style="display: none;" class="w-full xl:w-auto">
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bidang yang dicentang secara permanen?');">
                <input type="hidden" name="aksi" value="hapus_banyak">
                <template x-for="id in selected">
                    <input type="hidden" name="selected_ids[]" :value="id">
                </template>
                <button type="submit" class="w-full xl:w-auto inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Hapus Terpilih (<span x-text="selected.length"></span>)
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Data Bidang -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-4 w-10 text-center">
                            <input type="checkbox" @change="toggleAll()" x-model="selectAll" class="rounded border-slate-300 text-primary focus:ring-primary">
                        </th>
                        <th class="px-6 py-4 w-32">ID Bidang</th>
                        <th class="px-6 py-4">Nama Bidang Struktural</th>
                        <th class="px-6 py-4 text-right w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($listBidang) > 0): ?>
                        <?php foreach ($listBidang as $b): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" value="<?= htmlspecialchars($b['id_bidang']) ?>" x-model="selected" class="rounded border-slate-300 text-primary focus:ring-primary">
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500"><?= htmlspecialchars($b['id_bidang']) ?></td>
                                <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($b['Bidang']) ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="modalType = 'edit'; form = { id_bidang: '<?= htmlspecialchars($b['id_bidang']) ?>', bidang: '<?= addslashes($b['Bidang']) ?>' }; modalOpen = true" class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-semibold transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bidang ini?');">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_bidang" value="<?= htmlspecialchars($b['id_bidang']) ?>">
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada master data bidang.</td>
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
                // Menambahkan limit agar saat pindah halaman setting limitnya tidak hilang
                $queryString = "&search=" . urlencode($search) . "&limit=" . $limit; 
                ?>
                <?php if ($page > 1): ?>
                    <a href="master_bidang.php?page=<?= $page - 1 ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">Sebelumnya</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="master_bidang.php?page=<?= $i ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="master_bidang.php?page=<?= $page + 1 ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">Berikutnya</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==========================================
         MODAL TAMBAH / EDIT BIDANG
         ========================================== -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalOpen = false"></div>
            <div x-show="modalOpen" x-transition class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-4" x-text="modalType === 'tambah' ? 'Tambah Bidang Baru' : 'Edit Bidang'"></h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="aksi" :value="modalType">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">ID Bidang (Kode Unik)</label>
                        <input type="text" name="id_bidang" x-model="form.id_bidang" :readonly="modalType === 'edit'" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" :class="modalType === 'edit' ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''" placeholder="Contoh: BD11">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Bidang Struktural</label>
                        <input type="text" name="bidang" x-model="form.bidang" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="Contoh: Humas & Kerjasama">
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-800 shadow-sm" x-text="modalType === 'tambah' ? 'Simpan Bidang' : 'Update Bidang'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL IMPORT XLSX
         ========================================== -->
    <div x-show="importModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="importModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="importModal = false"></div>
            <div x-show="importModal" x-transition class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-display font-bold text-slate-900">Import Data Bidang</h3>
                    <button @click="importModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="bg-blue-50 text-blue-800 text-xs p-3 rounded-xl mb-4 border border-blue-100">
                    <p class="font-semibold mb-1">Panduan Import:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>Gunakan file format <strong>.xlsx</strong> (Excel)</li>
                        <li>Pastikan urutan kolom adalah: <strong>ID Bidang</strong>, <strong>Nama Bidang</strong>.</li>
                        <li>Data dengan ID yang sudah ada akan dilewati (tidak ditimpa).</li>
                    </ul>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="aksi" value="import">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pilih File Excel</label>
                        <input type="file" name="file_import" accept=".xlsx" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-300 rounded-lg p-1">
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="importModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/kepsek_footer.php'; ?>