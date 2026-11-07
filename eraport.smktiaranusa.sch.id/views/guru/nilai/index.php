<?php
$listMapelDiampu = $listMapelDiampu ?? [];
$listKelas = $listKelas ?? [];
$listSiswa = $listSiswa ?? [];
$selectedMapelId = $selectedMapelId ?? '';
$selectedKelas = $selectedKelas ?? '';
$kkm = isset($kkm_kktp) ? (int)$kkm_kktp : 75; // Nilai default KKM
?>

<div x-data="{ 
    openModalImport: false,
    isEditing: false,
    showPopup: <?= isset($_GET['error']) || isset($_GET['success']) ? 'true' : 'false' ?>,
    popupMessage: '<?= isset($_GET['success']) ? 'Data nilai berhasil disimpan secara permanen ke database.' : (isset($_GET['error']) ? 'Terjadi kesalahan saat memproses data nilai.' : '') ?>',
    popupType: '<?= isset($_GET['success']) ? 'success' : (isset($_GET['error']) ? 'error' : '') ?>',
    
    // Manajemen LocalStorage Draft
    storageKey: 'draft_nilai_<?= $selectedMapelId ?>_<?= $selectedKelas ?>',
    drafts: {},
    
    init() {
        if (this.storageKey !== 'draft_nilai__') {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                this.drafts = JSON.parse(saved);
                if(Object.keys(this.drafts).length > 0) {
                    this.isEditing = true;
                }
            }
        }
    },
    
    updateDraft(siswaId, val) {
        this.drafts[siswaId] = val;
        localStorage.setItem(this.storageKey, JSON.stringify(this.drafts));
    },
    
    clearDraft() {
        localStorage.removeItem(this.storageKey);
    },
    
    cancelEdit() {
        this.clearDraft();
        window.location.reload();
    }
}">

    <!-- HEADER & ACTION BUTTONS -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Input Nilai Mata Pelajaran</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola dan masukkan nilai akhir peserta didik.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="/guru/nilai/template" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                Template XLSX
            </a>
            <button @click="openModalImport = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-100 rounded-lg hover:bg-emerald-200 transition">
                Import Excel
            </button>
        </div>
    </div>

    <!-- POP-UP NOTIFIKASI -->
    <div x-show="showPopup" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="showPopup = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <template x-if="popupType === 'error'">
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">!</div>
            </template>
            <template x-if="popupType === 'success'">
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">✓</div>
            </template>
            <template x-if="popupType === 'info'">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">i</div>
            </template>

            <h3 class="font-bold text-gray-900 text-lg mb-2" x-text="popupType === 'error' ? 'Terjadi Kesalahan' : (popupType === 'success' ? 'Berhasil' : 'Informasi Sistem')"></h3>
            <p class="text-sm text-gray-600 mb-6" x-text="popupMessage"></p>
            <button @click="showPopup = false; window.history.replaceState({}, document.title, window.location.pathname);" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
                Tutup
            </button>
        </div>
    </div>

    <!-- FILTER BERTINGKAT -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-col sm:flex-row gap-4 items-end">
        <form method="GET" action="/guru/nilai" class="w-full flex flex-col sm:flex-row gap-4 items-end">
            <!-- Filter Mapel -->
            <div class="w-full sm:w-1/3">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">1. Pilih Mata Pelajaran</label>
                <select name="mapel_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    <?php foreach ($listMapelDiampu as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $selectedMapelId == $m['id'] ? 'selected' : '' ?>>
                            <?= !empty($m['kode_mapel']) ? htmlspecialchars($m['kode_mapel']) : htmlspecialchars($m['nama_mapel']) ?> (<?= htmlspecialchars($m['tingkat_jurusan']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Kelas -->
            <?php if (!empty($selectedMapelId)): ?>
            <div class="w-full sm:w-1/3">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">2. Pilih Kelas</label>
                <select name="kelas" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($listKelas as $kls): ?>
                        <option value="<?= htmlspecialchars($kls) ?>" <?= $selectedKelas === $kls ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kls) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>

        <?php if (!empty($selectedKelas)): ?>
            <div class="ml-auto bg-emerald-50 border border-emerald-200 px-4 py-2 rounded-lg text-sm text-emerald-800 font-medium whitespace-nowrap">
                KKM Sekolah: <span class="font-bold text-emerald-900"><?= $kkm ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- TABEL INPUT NILAI -->
    <?php if (!empty($selectedMapelId) && !empty($selectedKelas)): ?>
        <form action="/guru/nilai/store" method="POST" @submit="clearDraft()">
            <input type="hidden" name="mapel_id" value="<?= $selectedMapelId ?>">
            <input type="hidden" name="kelas" value="<?= htmlspecialchars($selectedKelas) ?>">

            <!-- TOMBOL AKSI (ATAS) -->
            <?php if (!empty($listSiswa)): ?>
                <div class="flex justify-between items-center mb-4">
                    <div class="text-sm text-gray-500" x-show="isEditing" x-cloak>
                        <span class="inline-block w-2 h-2 bg-yellow-400 rounded-full mr-1 animate-pulse"></span> Draft lokal aktif
                    </div>
                    <div class="text-sm text-gray-500" x-show="!isEditing" x-cloak>
                        Nilai terkunci. Klik edit untuk mengubah.
                    </div>

                    <div class="flex gap-2">
                        <template x-if="!isEditing">
                            <button type="button" @click="isEditing = true; popupMessage = 'Mode edit aktif. Nilai yang Anda ketik akan tersimpan sementara (draft). Jangan lupa klik Simpan Permanen.'; popupType = 'info'; showPopup = true;" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition shadow-sm">
                                Edit Nilai
                            </button>
                        </template>

                        <template x-if="isEditing">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="cancelEdit()" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                                    Batal
                                </button>
                                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition shadow-sm">
                                    Simpan Permanen
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 w-16 text-center">No</th>
                                <th scope="col" class="px-6 py-4">NISN / NIS</th>
                                <th scope="col" class="px-6 py-4">Nama Siswa</th>
                                <th scope="col" class="px-6 py-4 text-center w-36">Nilai (0-100)</th>
                                <th scope="col" class="px-6 py-4 text-center w-40">Status KKM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listSiswa)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        Tidak ada data siswa untuk kelas ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($listSiswa as $siswa):
                                    // Jika nilai dari database belum ada, jadikan string kosong "" bukan 0
                                    $nilaiVal = isset($siswa['nilai_akhir']) ? (int)$siswa['nilai_akhir'] : "''";
                                ?>
                                    <input type="hidden" name="siswa_id[]" value="<?= $siswa['id'] ?>">
                                    <tr class="bg-white border-b hover:bg-gray-50" x-data="{ 
                                            nilai: (drafts[<?= $siswa['id'] ?>] !== undefined) ? drafts[<?= $siswa['id'] ?>] : <?= $nilaiVal ?>
                                        }">
                                        <td class="px-6 py-4 text-center font-medium text-gray-900"><?= $no++ ?></td>
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900"><?= htmlspecialchars($siswa['nisn']) ?></span>
                                            <span class="block text-xs text-gray-400">NIS: <?= htmlspecialchars($siswa['nis']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-900"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <!-- x-model diubah agar tidak otomatis mem-parsing string kosong menjadi 0 -->
                                            <input type="number" step="1" min="0" max="100" name="nilai_akhir[<?= $siswa['id'] ?>]"
                                                x-model="nilai"
                                                @input="
                                                    if(nilai !== '') {
                                                        nilai = parseInt(nilai);
                                                        if(nilai > 100) nilai = 100; 
                                                        if(nilai < 0) nilai = 0; 
                                                    }
                                                    updateDraft(<?= $siswa['id'] ?>, nilai)
                                                "
                                                :disabled="!isEditing"
                                                class="w-24 px-2 py-1.5 text-center border border-gray-300 rounded-lg text-sm font-bold focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed"
                                                :class="(nilai !== '' && nilai < <?= $kkm ?>) ? 'text-red-600 border-red-300 bg-red-50' : 'text-gray-900'">
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <template x-if="nilai !== '' && nilai !== null">
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-md" :class="nilai >= <?= $kkm ?> ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'" x-text="nilai >= <?= $kkm ?> ? 'Tuntas' : 'Belum Tuntas'"></span>
                                            </template>
                                            <template x-if="nilai === '' || nilai === null">
                                                <span class="text-xs text-gray-400 font-semibold">-</span>
                                            </template>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOMBOL AKSI (BAWAH) -->
            <?php if (!empty($listSiswa)): ?>
                <div class="flex justify-end items-center gap-3">
                    <template x-if="!isEditing">
                        <button type="button" @click="isEditing = true; popupMessage = 'Mode edit aktif.'; popupType = 'info'; showPopup = true;" class="px-6 py-2.5 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition shadow-sm">
                            Edit Nilai
                        </button>
                    </template>

                    <template x-if="isEditing">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="cancelEdit()" class="px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                                Batal
                            </button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm">
                                Simpan Permanen
                            </button>
                        </div>
                    </template>
                </div>
            <?php endif; ?>
        </form>

    <?php elseif (!empty($selectedMapelId) && empty($selectedKelas)): ?>
        <div class="bg-white border border-gray-200 rounded-xl p-12 text-center text-gray-500">
            Silakan pilih kelas terlebih dahulu melalui filter ke-2 di atas.
        </div>
    <?php else: ?>
        <div class="bg-white border border-gray-200 rounded-xl p-12 text-center text-gray-500">
            Silakan pilih mata pelajaran terlebih dahulu melalui filter di atas untuk mulai menginput nilai.
        </div>
    <?php endif; ?>

    <!-- MODAL IMPORT EXCEL -->
    <div x-show="openModalImport" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalImport = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Import Nilai dari Excel</h3>
                <button @click="openModalImport = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/guru/nilai/import" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="mapel_id" value="<?= $selectedMapelId ?>">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Pilih Berkas Excel (.xlsx)</label>
                    <input type="file" name="file_excel" accept=".xlsx" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalImport = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg">Unggah & Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>