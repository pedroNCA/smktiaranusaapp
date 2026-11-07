<?php
$listKelas = $listKelas ?? [];
$limit = isset($limit) ? (int) $limit : 10;
$offset = isset($offset) ? (int) $offset : 0;
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$search = $_GET['search'] ?? '';
$kelasFilter = $_GET['kelas'] ?? '';
?>

<div x-data="{ 
    openModalTambah: false, 
    openModalEdit: false, 
    openModalHapus: false,
    openModalImport: false,
    showPopup: <?= isset($_GET['error']) || isset($_GET['success']) ? 'true' : 'false' ?>,
    editSiswa: { id: '', nis: '', nisn: '', nama_siswa: '', kelas: '', fase: ''},
    hapusId: '',
    selectAll: false,
    selectedItems: []
}">

    <!-- HEADER & ACTION BUTTONS -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Data Siswa</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola data peserta didik dan penempatan kelas SMK Tiara Nusa.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin/siswa/template" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                Template XLSX
            </a>
            <button @click="openModalImport = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-100 rounded-lg hover:bg-emerald-200 transition">
                Import Excel
            </button>
            <button @click="openModalTambah = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition">
                Tambah Siswa
            </button>
        </div>
    </div>

    <!-- POP-UP NOTIFIKASI -->
    <div x-show="showPopup" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="showPopup = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">

            <!-- Handle Specific Errors -->
            <?php if (isset($_GET['error'])): ?>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold">!</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Terjadi Kesalahan</h3>

                <?php if ($_GET['error'] == 'nis_exist'): ?>
                    <p class="text-sm text-gray-500 mb-6">NIS tersebut sudah digunakan oleh siswa lain. Silakan periksa kembali.</p>
                <?php elseif ($_GET['error'] == 'nisn_exist'): ?>
                    <p class="text-sm text-gray-500 mb-6">NISN tersebut sudah digunakan oleh siswa lain. Pastikan data unik.</p>
                <?php elseif ($_GET['error'] == 'import'): ?>
                    <p class="text-sm text-gray-500 mb-6">Gagal memproses berkas Excel. Pastikan format kolom sesuai dengan template.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mb-6">Terjadi kesalahan sistem saat menyimpan data.</p>
                <?php endif; ?>

                <!-- Handle Success -->
            <?php elseif (isset($_GET['success'])): ?>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold">✓</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Berhasil</h3>
                <p class="text-sm text-gray-500 mb-6">Data siswa berhasil diperbarui.</p>
            <?php endif; ?>

            <button @click="showPopup = false; window.history.replaceState({}, document.title, window.location.pathname);" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
                Tutup
            </button>
        </div>
    </div>

    <!-- FILTER, SEARCH, & LIMIT BAR -->
    <form method="GET" action="/admin/siswa" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <!-- Search -->
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, NIS, NISN..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <!-- Filter Kelas -->
            <select name="kelas" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Semua Kelas</option>
                <?php foreach ($listKelas as $k): ?>
                    <option value="<?= $k ?>" <?= ($kelasFilter == $k) ? 'selected' : '' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="limit" value="<?= $limit ?>">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-800 rounded-lg hover:bg-slate-700">Filter</button>
        </div>

        <!-- Limit Data Tampil -->
        <div class="flex items-center space-x-2 text-sm text-gray-600 w-full md:w-auto justify-end">
            <span>Tampilkan:</span>
            <select name="limit" onchange="this.form.submit()" class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            </select>
            <span>baris</span>
        </div>
    </form>

    <!-- FORM BULK DELETE -->
    <form action="/admin/siswa/delete-selected" method="POST" id="bulkDeleteForm">
        <!-- Aksi Ceklis Terpilih -->
        <div class="mb-3 flex items-center justify-between">
            <button type="submit" x-bind:disabled="selectedItems.length === 0" :class="selectedItems.length > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-300 cursor-not-allowed'" class="px-4 py-2 text-xs font-semibold text-white rounded-lg transition shadow-sm">
                Hapus Data Terpilih (<span x-text="selectedItems.length"></span>)
            </button>
        </div>

        <!-- TABEL DATA SISWA -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-4 w-10 text-center">
                                <input type="checkbox" @click="selectAll = !selectAll; selectedItems = selectAll ? [<?php foreach (($siswas ?? []) as $s) echo "'{$s['id']}',"; ?>] : []" class="w-4 h-4 text-emerald-600 border-gray-300 rounded">
                            </th>
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">NIS / NISN</th>
                            <th class="px-6 py-4">Nama Lengkap</th>
                            <th class="px-6 py-4">Kelas & Fase</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($siswas)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada data siswa ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1;
                            foreach ($siswas as $siswa): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $siswa['id'] ?>" x-model="selectedItems" class="w-4 h-4 text-emerald-600 border-gray-300 rounded">
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?= $no++ ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($siswa['nis']) ?></div>
                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($siswa['nisn']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 rounded-md"><?= htmlspecialchars($siswa['kelas']) ?></span>
                                        <span class="text-xs text-gray-500 ml-1">Fase <?= htmlspecialchars($siswa['fase']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="inline-flex items-center space-x-2">
                                            <button type="button" @click="editSiswa = { id: '<?= $siswa['id'] ?>', nis: '<?= $siswa['nis'] ?>', nisn: '<?= $siswa['nisn'] ?>', nama_siswa: '<?= addslashes($siswa['nama_siswa']) ?>', kelas: '<?= $siswa['kelas'] ?>', fase: '<?= $siswa['fase'] ?>' }; openModalEdit = true;" class="px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">Edit</button>
                                            <button type="button" @click="hapusId = '<?= $siswa['id'] ?>'; openModalHapus = true;" class="px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
            <span class="text-sm text-gray-500">Halaman <span class="font-semibold text-gray-700"><?= $page ?></span> dari <span class="font-semibold text-gray-700"><?= $totalPages ?></span></span>
            
            <div class="inline-flex items-center space-x-1">
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelasFilter) ?>" class="px-3 py-1.5 text-sm font-semibold bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 shadow-sm">Previous</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-sm font-semibold bg-gray-100 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">Previous</span>
                <?php endif; ?>

                <!-- Nomor Halaman -->
                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="?page=1&limit=' . $limit . '&search=' . urlencode($search) . '&kelas=' . urlencode($kelasFilter) . '" class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="px-2 text-gray-400">...</span>';
                    }
                }

                for ($p = $startPage; $p <= $endPage; $p++): 
                ?>
                    <a href="?page=<?= $p ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelasFilter) ?>" class="px-3.5 py-1.5 text-sm font-semibold rounded-lg <?= $p == $page ? 'bg-slate-900 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' ?>"><?= $p ?></a>
                <?php 
                endfor;

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="px-2 text-gray-400">...</span>';
                    }
                    echo '<a href="?page=' . $totalPages . '&limit=' . $limit . '&search=' . urlencode($search) . '&kelas=' . urlencode($kelasFilter) . '" class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                }
                ?>

                <!-- Next Button -->
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelasFilter) ?>" class="px-3 py-1.5 text-sm font-semibold bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 shadow-sm">Next</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-sm font-semibold bg-gray-100 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">Next</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL TAMBAH SISWA -->
    <div x-show="openModalTambah" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalTambah = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Tambah Data Siswa</h3>
                <button @click="openModalTambah = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/siswa/store" method="POST" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">NIS</label>
                        <input type="text" name="nis" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">NISN</label>
                        <input type="text" name="nisn" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_siswa" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Kelas</label>
                        <input type="text" name="kelas" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="X TKJ 1">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fase</label>
                        <input type="text" name="fase" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="E">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalTambah = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT SISWA -->
    <div x-show="openModalEdit" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalEdit = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Edit Data Siswa</h3>
                <button @click="openModalEdit = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/siswa/update" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" x-model="editSiswa.id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">NIS</label>
                        <input type="text" name="nis" x-model="editSiswa.nis" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">NISN</label>
                        <input type="text" name="nisn" x-model="editSiswa.nisn" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_siswa" x-model="editSiswa.nama_siswa" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Kelas</label>
                        <input type="text" name="kelas" x-model="editSiswa.kelas" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fase</label>
                        <input type="text" name="fase" x-model="editSiswa.fase" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalEdit = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HAPUS SATUAN -->
    <div x-show="openModalHapus" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalHapus = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <h3 class="font-bold text-gray-900 text-lg mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus data siswa ini?</p>
            <form action="/admin/siswa/delete" method="POST" class="flex space-x-3">
                <input type="hidden" name="id" x-model="hapusId">
                <button type="button" @click="openModalHapus = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold">Batal</button>
                <button type="submit" class="w-1/2 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold">Ya, Hapus</button>
            </form>
        </div>
    </div>

    <!-- MODAL IMPORT EXCEL -->
    <div x-show="openModalImport" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalImport = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Import Data Siswa</h3>
                <button @click="openModalImport = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/siswa/import" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Pilih Berkas Excel (.xlsx)</label>
                    <input type="file" name="file_excel" accept=".xlsx" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalImport = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Unggah</button>
                </div>
            </form>
        </div>
    </div>
</div>