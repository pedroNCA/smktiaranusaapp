<?php
$list_mapel = $list_mapel ?? [];
$listGuruOption = $listGuruOption ?? [];
$listTingkatOption = $listTingkatOption ?? [];
$limit = isset($limit) ? (int)$limit : 10;
$offset = isset($offset) ? (int)$offset : 0;
$page = isset($page) ? (int)$page : 1;
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$search = $_GET['search'] ?? '';
$tingkatFilter = $_GET['tingkat'] ?? '';
?>

<div x-data="{ 
    openModalTambah: false, 
    openModalEdit: false, 
    openModalHapus: false,
    openModalImport: false,
    showPopup: <?= isset($_GET['error']) || isset($_GET['success']) ? 'true' : 'false' ?>,
    editMapel: { id: '', kode_mapel: '', kelompok_mapel: '', kelompok: 'A', is_sub: 0, urutan: 99, tingkat_jurusan: '', nama_mapel: '', guru_id: '' },
    hapusId: '',
    selectAll: false,
    selectedItems: []
}">

    <!-- HEADER & ACTION BUTTONS -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Data Mata Pelajaran</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar mata pelajaran, kelompok, urutan cetak, dan guru pengampu.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin/mapel/template" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                Template XLSX
            </a>
            <button @click="openModalImport = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-100 rounded-lg hover:bg-emerald-200 transition">
                Import Excel
            </button>
            <button @click="openModalTambah = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition">
                Tambah Mapel
            </button>
        </div>
    </div>

    <!-- POP-UP NOTIFIKASI -->
    <div x-show="showPopup" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="showPopup = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <?php if (isset($_GET['error'])): ?>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">!</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Terjadi Kesalahan</h3>
                <?php if ($_GET['error'] == 'mapel_exist'): ?>
                    <p class="text-sm text-gray-500 mb-6">Mata Pelajaran tersebut sudah ada di Tingkat/Angkatan yang sama.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mb-6">Terjadi kesalahan pada sistem atau format berkas import tidak valid.</p>
                <?php endif; ?>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">✓</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Berhasil</h3>
                <p class="text-sm text-gray-500 mb-6">Data Mata Pelajaran berhasil diperbarui.</p>
            <?php endif; ?>
            <button @click="showPopup = false; window.history.replaceState({}, document.title, window.location.pathname);" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
                Tutup
            </button>
        </div>
    </div>

    <!-- PENCARIAN & FILTER -->
    <form method="GET" action="/admin/mapel" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari mapel, kode, guru..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div class="relative w-full sm:w-48">
                <select name="tingkat" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Tingkat</option>
                    <?php foreach ($listTingkatOption as $tk): ?>
                        <option value="<?= htmlspecialchars($tk) ?>" <?= $tingkatFilter == $tk ? 'selected' : '' ?>><?= htmlspecialchars($tk) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="limit" value="<?= $limit ?>">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-800 rounded-lg hover:bg-slate-700">Filter</button>
        </div>

        <div class="flex items-center space-x-2 text-sm text-gray-600 w-full md:w-auto justify-end">
            <span>Tampilkan:</span>
            <select name="limit" onchange="this.form.submit()" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            </select>
            <span>baris</span>
        </div>
    </form>

    <!-- TABEL -->
    <form action="/admin/mapel/delete-selected" method="POST">
        <div class="mb-3 flex items-center justify-between">
            <button type="submit" x-bind:disabled="selectedItems.length === 0" :class="selectedItems.length > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-300 cursor-not-allowed'" class="px-4 py-2 text-xs font-semibold text-white rounded-lg transition shadow-sm">
                Hapus Terpilih (<span x-text="selectedItems.length"></span>)
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-4 w-10 text-center">
                                <input type="checkbox" @click="selectAll = !selectAll; selectedItems = selectAll ? [<?php foreach (($list_mapel ?? []) as $m) echo "'{$m['id']}',"; ?>] : []" class="w-4 h-4 text-emerald-600 border-gray-300 rounded">
                            </th>
                            <th scope="col" class="px-6 py-4 w-16">No</th>
                            <th scope="col" class="px-6 py-4">Kode</th>
                            <th scope="col" class="px-6 py-4">Tipe & Urutan</th>
                            <th scope="col" class="px-6 py-4">Nama Mata Pelajaran</th>
                            <th scope="col" class="px-6 py-4">Tingkat/Angkatan</th>
                            <th scope="col" class="px-6 py-4">Guru Pengampu</th>
                            <th scope="col" class="px-6 py-4 text-center w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list_mapel)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada data mata pelajaran. Silakan tambahkan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1;
                            foreach ($list_mapel as $mapel): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $mapel['id'] ?>" x-model="selectedItems" class="w-4 h-4 text-emerald-600 border-gray-300 rounded">
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?= $no++ ?></td>
                                    <td class="px-6 py-4 font-medium text-emerald-700">
                                        <?= !empty($mapel['kode_mapel']) ? htmlspecialchars($mapel['kode_mapel']) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2 py-0.5 rounded border border-gray-300 mb-1 inline-block">
                                            <?= $mapel['kelompok'] === 'A' ? 'A (Umum)' : 'B (Kejuruan)' ?>
                                            - No. <?= htmlspecialchars($mapel['urutan'] ?? '99') ?>
                                        </span>
                                        <div class="text-xs font-semibold <?= $mapel['is_sub'] == 1 ? 'text-emerald-600' : 'text-gray-500' ?>">
                                            <?= $mapel['is_sub'] == 1 ? '↳ Sub Mapel (a,b,c)' : 'Mata Pelajaran Utama' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                        <div class="text-xs text-gray-400 font-normal mt-0.5"><?= htmlspecialchars($mapel['kelompok_mapel']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($mapel['tingkat_jurusan'])): ?>
                                            <span class="bg-blue-50 text-blue-700 text-xs px-2.5 py-1 rounded-md font-semibold">
                                                <?= htmlspecialchars($mapel['tingkat_jurusan']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Angkatan Kosong
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($mapel['nama_guru'])): ?>
                                            <span class="text-gray-900"><?= htmlspecialchars($mapel['nama_guru']) ?></span>
                                        <?php else: ?>
                                            <span class="text-xs font-medium text-amber-600">Belum Ada Guru</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="inline-flex items-center space-x-2">
                                            <button type="button" @click="
                                                editMapel = { 
                                                    id: '<?= $mapel['id'] ?>', 
                                                    kode_mapel: '<?= addslashes($mapel['kode_mapel'] ?? '') ?>',
                                                    kelompok_mapel: '<?= addslashes($mapel['kelompok_mapel']) ?>', 
                                                    kelompok: '<?= $mapel['kelompok'] ?? 'A' ?>', 
                                                    is_sub: <?= $mapel['is_sub'] ?? 0 ?>, 
                                                    urutan: <?= $mapel['urutan'] ?? 99 ?>, 
                                                    tingkat_jurusan: '<?= $mapel['tingkat_jurusan'] ?>', 
                                                    nama_mapel: '<?= addslashes($mapel['nama_mapel']) ?>', 
                                                    guru_id: '<?= $mapel['guru_id'] ?>' 
                                                }; openModalEdit = true;"
                                                class="px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">Edit</button>
                                            <button type="button" @click="hapusId = '<?= $mapel['id'] ?>'; openModalHapus = true;" class="px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100">Hapus</button>
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
                    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&tingkat=<?= urlencode($tingkatFilter) ?>" class="px-3 py-1.5 text-sm font-semibold bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 shadow-sm">Previous</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-sm font-semibold bg-gray-100 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">Previous</span>
                <?php endif; ?>

                <!-- Nomor Halaman -->
                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="?page=1&limit=' . $limit . '&search=' . urlencode($search) . '&tingkat=' . urlencode($tingkatFilter) . '" class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="px-2 text-gray-400">...</span>';
                    }
                }

                for ($p = $startPage; $p <= $endPage; $p++): 
                ?>
                    <a href="?page=<?= $p ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&tingkat=<?= urlencode($tingkatFilter) ?>" class="px-3.5 py-1.5 text-sm font-semibold rounded-lg <?= $p == $page ? 'bg-slate-900 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' ?>"><?= $p ?></a>
                <?php 
                endfor;

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="px-2 text-gray-400">...</span>';
                    }
                    echo '<a href="?page=' . $totalPages . '&limit=' . $limit . '&search=' . urlencode($search) . '&tingkat=' . urlencode($tingkatFilter) . '" class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                }
                ?>

                <!-- Next Button -->
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&tingkat=<?= urlencode($tingkatFilter) ?>" class="px-3 py-1.5 text-sm font-semibold bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 shadow-sm">Next</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-sm font-semibold bg-gray-100 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">Next</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL TAMBAH MAPEL -->
    <div x-show="openModalTambah" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalTambah = false" class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Tambah Mapel</h3>
                <button @click="openModalTambah = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/mapel/store" method="POST" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Kode Mapel</label>
                        <input type="text" name="kode_mapel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Contoh: PAI-X">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Contoh: Pendidikan Agama Islam">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Tipe Kelompok</label>
                        <select name="kelompok" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white">
                            <option value="A">A - Umum</option>
                            <option value="B">B - Kejuruan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Urutan (No)</label>
                        <input type="number" name="urutan" value="99" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Sub-Mapel?</label>
                        <select name="is_sub" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white">
                            <option value="0">Tidak</option>
                            <option value="1">Ya (a,b,c)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tingkat/Jurusan</label>
                        <select name="tingkat_jurusan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">-- Pilih Tingkat --</option>
                            <?php foreach ($listTingkatOption as $tk): ?>
                                <option value="<?= htmlspecialchars($tk) ?>"><?= htmlspecialchars($tk) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Keterangan (Opsional)</label>
                        <input type="text" name="kelompok_mapel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Muatan Nasional..">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Guru Pengampu</label>
                    <select name="guru_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">-- Kosongkan Jika Belum Ada --</option>
                        <?php foreach ($listGuruOption as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_guru']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalTambah = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT MAPEL -->
    <div x-show="openModalEdit" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalEdit = false" class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Edit Mapel</h3>
                <button @click="openModalEdit = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/mapel/update" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" x-model="editMapel.id">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Kode Mapel</label>
                        <input type="text" name="kode_mapel" x-model="editMapel.kode_mapel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" x-model="editMapel.nama_mapel" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Tipe Kelompok</label>
                        <select name="kelompok" x-model="editMapel.kelompok" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white">
                            <option value="A">A - Umum</option>
                            <option value="B">B - Kejuruan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Urutan (No)</label>
                        <input type="number" name="urutan" x-model="editMapel.urutan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Sub-Mapel?</label>
                        <select name="is_sub" x-model="editMapel.is_sub" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white">
                            <option value="0">Tidak</option>
                            <option value="1">Ya (a,b,c)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tingkat/Jurusan</label>
                        <select name="tingkat_jurusan" x-model="editMapel.tingkat_jurusan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">-- Pilih Tingkat --</option>
                            <?php foreach ($listTingkatOption as $tk): ?>
                                <option value="<?= htmlspecialchars($tk) ?>"><?= htmlspecialchars($tk) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Keterangan (Opsional)</label>
                        <input type="text" name="kelompok_mapel" x-model="editMapel.kelompok_mapel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Guru Pengampu</label>
                    <select name="guru_id" x-model="editMapel.guru_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">-- Kosongkan Jika Belum Ada --</option>
                        <?php foreach ($listGuruOption as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_guru']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
            <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus data mata pelajaran ini?</p>
            <form action="/admin/mapel/delete" method="POST" class="flex space-x-3">
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
                <h3 class="font-bold text-gray-900">Import Mata Pelajaran</h3>
                <button @click="openModalImport = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/mapel/import" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Pilih Berkas Excel (.xlsx)</label>
                    <input type="file" name="file_excel" accept=".xlsx" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalImport = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg">Unggah</button>
                </div>
            </form>
        </div>
    </div>
</div>