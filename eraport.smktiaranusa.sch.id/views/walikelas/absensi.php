<div x-data="{
    search: '',
    perPage: 10,
    currentPage: 1,
    get rows() {
        return Array.from(document.querySelectorAll('.absensi-row'));
    },
    get filteredRows() {
        return this.rows.filter(row => {
            let name = row.getAttribute('data-name').toLowerCase();
            let matchSearch = this.search === '' || name.includes(this.search.toLowerCase());
            return matchSearch;
        });
    },
    get totalPages() {
        return Math.ceil(this.filteredRows.length / this.perPage) || 1;
    },
    init() {
        this.$watch('search', () => { this.currentPage = 1; this.updatePagination(); });
        this.$watch('perPage', () => { this.currentPage = 1; this.updatePagination(); });
        this.$watch('currentPage', () => { this.updatePagination(); });
        this.updatePagination();
    },
    updatePagination() {
        let filtered = this.filteredRows;
        let start = (this.currentPage - 1) * this.perPage;
        let end = start + parseInt(this.perPage);

        this.rows.forEach(row => { row.style.display = 'none'; });
        filtered.slice(start, end).forEach(row => { row.style.display = ''; });
    }
}" class="space-y-6">

    <!-- Header & Tombol Aksi Atas -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Input & Rekapitulasi Absensi</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola data kehadiran siswa untuk kelas binaan <?= htmlspecialchars($kelasBinaan ?? '') ?>.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="/walikelas/absensi/template" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl transition-all shadow-sm">
                Download Template Excel
            </a>
            <button data-modal-target="import-modal" data-modal-toggle="import-modal" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm">
                Import Excel
            </button>
            <?php if (!empty($listSiswa)): ?>
                <!-- Tombol Simpan di Bagian Atas -->
                <button type="submit" form="form-absensi" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Perubahan
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifikasi Alert -->
    <?php if (isset($_GET['success'])): ?>
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl" role="alert">
            Data absensi berhasil diperbarui.
        </div>
    <?php endif; ?>

    <!-- Kontrol Filter (Search & Per Page) -->
    <?php if (!empty($listSiswa)): ?>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Search -->
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" x-model="search" placeholder="Cari nama siswa..." class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 border border-gray-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Show Entries -->
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <span class="text-sm text-gray-600">Tampilkan:</span>
                <select x-model="perPage" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 p-2">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-600">data</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabel Absensi Siswa -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <form id="form-absensi" action="/walikelas/absensi/store" method="POST">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3 px-4 text-center border border-gray-300 w-16">No</th>
                            <th class="py-3 px-6 border border-gray-300">Nama Siswa</th>
                            <th class="py-3 px-4 text-center border border-gray-300 w-32">Sakit (S)</th>
                            <th class="py-3 px-4 text-center border border-gray-300 w-32">Izin (I)</th>
                            <th class="py-3 px-4 text-center border border-gray-300 w-36">Alpa (A)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                        <?php if (!empty($listSiswa)): ?>
                            <?php $no = 1;
                            foreach ($listSiswa as $s): ?>
                                <tr class="hover:bg-gray-50 absensi-row" data-name="<?= htmlspecialchars($s['nama_siswa']) ?>">
                                    <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                                    <td class="py-3 px-4 text-center border border-gray-300 font-medium text-gray-500"><?= $no++ ?></td>
                                    <td class="py-3 px-6 border border-gray-300 font-semibold text-gray-900"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                                    <td class="py-3 px-4 text-center border border-gray-300">
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="sakit[]" value="<?= $s['sakit'] ?? 0 ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-20 text-center bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm focus:ring-emerald-500 focus:border-emerald-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    </td>
                                    <td class="py-3 px-4 text-center border border-gray-300">
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="izin[]" value="<?= $s['izin'] ?? 0 ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-20 text-center bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm focus:ring-emerald-500 focus:border-emerald-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    </td>
                                    <td class="py-3 px-4 text-center border border-gray-300">
                                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="tanpa_keterangan[]" value="<?= $s['tanpa_keterangan'] ?? 0 ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-20 text-center bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm focus:ring-emerald-500 focus:border-emerald-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500 border border-gray-300">Belum ada data siswa di kelas binaan ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bagian Pagination & Info Data -->
            <?php if (!empty($listSiswa)): ?>
                <div class="p-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Info Keterangan -->
                    <div class="text-sm text-gray-600">
                        Menampilkan <span class="font-medium" x-text="filteredRows.length > 0 ? ((currentPage - 1) * perPage) + 1 : 0"></span>
                        sampai <span class="font-medium" x-text="Math.min(currentPage * perPage, filteredRows.length)"></span>
                        dari <span class="font-medium" x-text="filteredRows.length"></span> data siswa
                    </div>

                    <!-- Tombol Navigasi Pagination -->
                    <div class="flex items-center space-x-2">
                        <button type="button"
                            @click="if(currentPage > 1) currentPage--"
                            :disabled="currentPage === 1"
                            class="px-3 py-1.5 text-sm font-semibold bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>

                        <div class="text-sm font-medium text-gray-700 px-2">
                            Hal <span x-text="currentPage"></span> dari <span x-text="totalPages"></span>
                        </div>

                        <button type="button"
                            @click="if(currentPage < totalPages) currentPage++"
                            :disabled="currentPage >= totalPages"
                            class="px-3 py-1.5 text-sm font-semibold bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>

                    <!-- Tombol Simpan Bawah -->
                    <div>
                        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                            Simpan Perubahan Absensi
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Modal Import Excel -->
<div id="import-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-lg border border-gray-200">
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Import Data Absensi Excel</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-100 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="import-modal">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="/walikelas/absensi/import" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Pilih Berkas Excel (.xlsx / .csv)</label>
                    <input type="file" name="file_excel" accept=".xlsx, .csv" required class="block w-full text-sm text-gray-900 border border-gray-300 rounded-xl cursor-pointer bg-gray-50 focus:outline-none p-2.5">
                </div>
                <div class="flex justify-end space-x-3 pt-3">
                    <button type="button" data-modal-hide="import-modal" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700">Unggah & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>