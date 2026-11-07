<?php
// pages/bk/master_siswa.php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$page_title = 'Master Data Siswa';

// --- LOGIKA FILTER, SEARCH, & PAGINATION ---
$search = sanitizeInput($_GET['search'] ?? '');
$filter_kelas = sanitizeInput($_GET['kelas'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$whereClause = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClause[] = "nama LIKE ?";
    $params[] = "%$search%";
}
if (!empty($filter_kelas)) {
    $whereClause[] = "kelas = ?";
    $params[] = $filter_kelas;
}
$whereSQL = implode(" AND ", $whereClause);

try {
    // Ambil list kelas unik
    $stmtKelas = $pdo->query("SELECT DISTINCT kelas FROM master_siswa ORDER BY kelas ASC");
    $list_kelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);

    // Hitung Total Data
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM master_siswa WHERE $whereSQL");
    $stmtCount->execute($params);
    $total_data = $stmtCount->fetchColumn();
    $total_pages = ceil($total_data / $limit);

    // Ambil Data
    $stmtData = $pdo->prepare("SELECT * FROM master_siswa WHERE $whereSQL ORDER BY kelas ASC, nama ASC LIMIT $limit OFFSET $offset");
    $stmtData->execute($params);
    $data_siswa = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // Array ID untuk Select All via Alpine.js
    $all_ids = array_column($data_siswa, 'id');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/../../includes/bk_header.php';
require_once __DIR__ . '/../../includes/bk_sidebar.php';
require_once __DIR__ . '/../../includes/bk_navbar.php';
?>

<div class="max-w-7xl mx-auto space-y-6 pb-12" x-data="{ 
    modalTambah: false, 
    modalEdit: false, 
    modalImport: false,
    editData: { id: '', nis: '', nama: '', kelas: '', jenis_kelamin: '' },
    selected: [],
    allSelected: false,
    toggleAll() {
        this.allSelected = !this.allSelected;
        this.selected = this.allSelected ? <?= json_encode($all_ids) ?> : [];
    }
}">

    <!-- Notifikasi -->
    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'added' => ['success', 'Data siswa berhasil ditambahkan.'],
            'updated' => ['success', 'Data siswa berhasil diperbarui.'],
            'deleted' => ['success', 'Data berhasil dihapus.'],
            'imported' => ['success', 'Data Excel berhasil diimpor.'],
            'error' => ['error', 'Terjadi kesalahan sistem atau format salah.']
        ];
        $tipe = $msgs[$_GET['msg']][0] ?? 'success';
        $pesan = $msgs[$_GET['msg']][1] ?? '';
        if ($pesan):
        ?>
            <div class="p-4 rounded-lg shadow-sm border <?= $tipe === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                <p class="font-bold text-sm"><?= $pesan ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row justify-between gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex gap-2 flex-wrap">
            <button @click="modalTambah = true" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Siswa
            </button>

            <button @click="modalImport = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Import Excel
            </button>

            <!-- PERBAIKAN: Tombol Download Template XLSX -->
            <a href="../../proses/import_master_siswa.php?action=template" class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-4 py-2 rounded-lg font-semibold text-sm transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Template XLSX
            </a>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <form method="GET" action="master_siswa.php" class="flex flex-col md:flex-row gap-4 justify-between md:items-end">
            <div class="flex gap-4 w-full md:w-auto">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tampilkan</label>
                    <select name="limit" onchange="this.form.submit()" class="border-gray-300 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter Kelas</label>
                    <select name="kelas" onchange="this.form.submit()" class="border-gray-300 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2 min-w-[120px]">
                        <option value="">Semua</option>
                        <?php foreach ($list_kelas as $kls): ?>
                            <option value="<?= htmlspecialchars($kls) ?>" <?= $filter_kelas === $kls ? 'selected' : '' ?>><?= htmlspecialchars($kls) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="w-full md:w-72">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cari Nama</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="block w-full p-2 text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Ketik nama...">
                <button type="submit" class="hidden"></button>
            </div>
        </form>
    </div>

    <!-- Tabel Data & Bulk Delete Form -->
    <form method="POST" action="../../proses/aksi_master_siswa.php" id="bulkDeleteForm">
        <input type="hidden" name="action" value="bulk_delete">

        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-4">

            <!-- Bulk Action Bar -->
            <div x-show="selected.length > 0" x-transition style="display: none;" class="bg-red-50 p-3 border-b border-red-100 flex justify-between items-center">
                <span class="text-sm font-semibold text-red-800"><span x-text="selected.length"></span> baris dipilih</span>
                <button type="button" @click="if(confirm('Hapus '+selected.length+' data siswa secara permanen?')) document.getElementById('bulkDeleteForm').submit();" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-bold shadow-sm">
                    Hapus Terpilih
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left w-12">
                                <input type="checkbox" @click="toggleAll()" :checked="allSelected" class="w-4 h-4 text-primary bg-white border-gray-300 rounded focus:ring-primary">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">NIS</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Nama Lengkap</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Kelas</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">L/P</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($total_data > 0): ?>
                            <?php $no = $offset + 1;
                            foreach ($data_siswa as $siswa): ?>
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="ids[]" value="<?= $siswa['id'] ?>" x-model="selected" class="w-4 h-4 text-primary bg-white border-gray-300 rounded focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= $no++ ?></td>
                                    <td class="px-6 py-4 font-mono text-sm text-gray-700"><?= htmlspecialchars(decryptData($siswa['nis'])) ?></td>
                                    <td class="px-6 py-4 font-semibold text-sm text-gray-900"><?= htmlspecialchars($siswa['nama']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-700"><span class="bg-gray-100 px-2 py-1 rounded text-xs font-semibold"><?= htmlspecialchars($siswa['kelas']) ?></span></td>
                                    <td class="px-6 py-4 text-sm text-gray-700"><?= $siswa['jenis_kelamin'] === 'Laki-laki' ? 'L' : 'P' ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <!-- Tombol Edit (Set Data ke Alpine) -->
                                            <button type="button" @click="
                                                editData.id = '<?= $siswa['id'] ?>';
                                                editData.nis = '<?= htmlspecialchars(decryptData($siswa['nis']), ENT_QUOTES) ?>';
                                                editData.nama = '<?= htmlspecialchars($siswa['nama'], ENT_QUOTES) ?>';
                                                editData.kelas = '<?= htmlspecialchars($siswa['kelas'], ENT_QUOTES) ?>';
                                                editData.jenis_kelamin = '<?= $siswa['jenis_kelamin'] ?>';
                                                modalEdit = true;
                                            " class="text-xs font-semibold bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">Edit</button>

                                            <!-- Tombol Hapus Satuan -->
                                            <a href="../../proses/aksi_master_siswa.php?action=delete&id=<?= $siswa['id'] ?>" onclick="return confirm('Hapus data ini?');" class="text-xs font-semibold bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 font-semibold">Data tidak ditemukan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm text-gray-600">Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total: <?= $total_data ?>)</span>
            <div class="flex gap-1">
                <?php
                $qParams = $_GET;
                if ($page > 1): $qParams['page'] = $page - 1; ?>
                    <a href="?<?= http_build_query($qParams) ?>" class="px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50">&laquo; Prev</a>
                <?php endif; ?>
                <?php if ($page < $total_pages): $qParams['page'] = $page + 1; ?>
                    <a href="?<?= http_build_query($qParams) ?>" class="px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= MODAL AREA ================= -->

    <!-- PERBAIKAN MODAL: Menggunakan bg-black/50 backdrop-blur-sm -->

    <!-- Modal Tambah -->
    <div x-show="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
        <div @click.away="modalTambah = false" class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold mb-4">Tambah Siswa Baru</h3>
            <form action="../../proses/aksi_master_siswa.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div><label class="text-sm font-semibold">NIS</label><input type="text" name="nis" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1"></div>
                <div><label class="text-sm font-semibold">Nama Lengkap</label><input type="text" name="nama" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1"></div>
                <div><label class="text-sm font-semibold">Kelas</label><input type="text" name="kelas" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1" placeholder="Cth: X RPL 1"></div>
                <div><label class="text-sm font-semibold">Jenis Kelamin</label>
                    <select name="jenis_kelamin" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1">
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="modalTambah = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white font-bold rounded-lg text-sm hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div x-show="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
        <div @click.away="modalEdit = false" class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold mb-4">Edit Data Siswa</h3>
            <form action="../../proses/aksi_master_siswa.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" x-model="editData.id">
                <div><label class="text-sm font-semibold">NIS</label><input type="text" name="nis" x-model="editData.nis" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1"></div>
                <div><label class="text-sm font-semibold">Nama Lengkap</label><input type="text" name="nama" x-model="editData.nama" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1"></div>
                <div><label class="text-sm font-semibold">Kelas</label><input type="text" name="kelas" x-model="editData.kelas" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1"></div>
                <div><label class="text-sm font-semibold">Jenis Kelamin</label>
                    <select name="jenis_kelamin" x-model="editData.jenis_kelamin" required class="w-full border-gray-300 rounded-lg p-2 text-sm mt-1">
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="modalEdit = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white font-bold rounded-lg text-sm hover:bg-blue-700">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import -->
    <div x-show="modalImport" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
        <div @click.away="modalImport = false" class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold mb-2">Import Data Excel</h3>
            <p class="text-xs text-gray-500 mb-4">Pastikan format kolom sesuai dengan template xlsx.</p>
            <form action="../../proses/import_master_siswa.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="import">
                <div>
                    <input type="file" name="file_excel" accept=".xlsx, .xls" required class="w-full border border-gray-300 p-2 rounded-lg text-sm bg-gray-50">
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="modalImport = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg text-sm hover:bg-green-700">Upload & Proses</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>