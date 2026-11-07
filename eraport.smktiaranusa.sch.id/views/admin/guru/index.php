<div x-data="{ 
    openModalTambah: false, 
    openModalEdit: false, 
    openModalHapus: false,
    openModalBulkHapus: false,
    openModalImport: false,
    showPopup: <?= isset($_GET['error']) || isset($_GET['success']) ? 'true' : 'false' ?>,
    editGuru: { id: '', nip: '', nama_guru: '', username: '' },
    hapusId: '',
    showPassTambah: false,
    showPassEdit: false,

    // Fitur Tambahan (Search, Pagination, Bulk Select)
    search: '',
    perPage: '10',
    currentPage: 1,
    selectedGurus: [],

    get guruRows() {
        // Mengambil semua baris data dari DOM tabel asli PHP
        return Array.from(document.querySelectorAll('.guru-row')).map(row => {
            return {
                id: row.getAttribute('data-id'),
                nip: row.getAttribute('data-nip'),
                nama: row.getAttribute('data-nama'),
                username: row.getAttribute('data-username'),
                html: row.innerHTML
            };
        });
    },

    get filteredGurus() {
        if (!this.search) return this.guruRows;
        let query = this.search.toLowerCase();
        return this.guruRows.filter(g => 
            g.nip.toLowerCase().includes(query) || 
            g.nama.toLowerCase().includes(query) || 
            g.username.toLowerCase().includes(query)
        );
    },

    get totalPages() {
        if (this.perPage === 'all') return 1;
        return Math.ceil(this.filteredGurus.length / parseInt(this.perPage)) || 1;
    },

    get paginatedGurus() {
        if (this.perPage === 'all') return this.filteredGurus;
        let start = (this.currentPage - 1) * parseInt(this.perPage);
        let end = start + parseInt(this.perPage);
        return this.filteredGurus.slice(start, end);
    },

    toggleSelectAll(e) {
        if (e.target.checked) {
            this.selectedGurus = this.paginatedGurus.map(g => g.id);
        } else {
            this.selectedGurus = [];
        }
    },

    get isAllSelected() {
        if (this.paginatedGurus.length === 0) return false;
        return this.paginatedGurus.every(g => this.selectedGurus.includes(g.id));
    }
}" x-init="$watch('search', () => currentPage = 1); $watch('perPage', () => currentPage = 1);">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Data Guru</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola data tenaga pendidik SMK Tiara Nusa.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Hapus Terpilih (Muncul jika ada yang dicentang) -->
            <button x-show="selectedGurus.length > 0" @click="openModalBulkHapus = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus Terpilih (<span x-text="selectedGurus.length"></span>)
            </button>

            <!-- Tombol Download Template -->
            <a href="/admin/guru/template" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Template XLSX
            </a>

            <!-- Tombol Trigger Modal Import -->
            <button @click="openModalImport = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-100 border border-transparent rounded-lg hover:bg-emerald-200 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Import Excel
            </button>

            <!-- Tombol Trigger Modal Tambah Manual -->
            <button @click="openModalTambah = true" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-slate-900 border border-transparent rounded-lg hover:bg-slate-800 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Guru
            </button>
        </div>
    </div>

    <!-- POP-UP NOTIFIKASI MODAL (GAGAL / SUKSES) -->
    <div x-show="showPopup" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="showPopup = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <?php if (isset($_GET['error']) && $_GET['error'] == 'username_exist'): ?>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">!</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Gagal Menyimpan</h3>
                <p class="text-sm text-gray-500 mb-6">Username tersebut sudah digunakan oleh akun lain. Silakan gunakan username yang berbeda.</p>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">✓</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Berhasil</h3>
                <p class="text-sm text-gray-500 mb-6">Data guru berhasil diperbarui dalam sistem.</p>
            <?php endif; ?>
            <button @click="showPopup = false; window.history.replaceState({}, document.title, window.location.pathname);" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
                Tutup
            </button>
        </div>
    </div>

    <!-- MODAL TAMBAH MANUAL -->
    <div x-show="openModalTambah" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalTambah = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Tambah Data Guru Manual</h3>
                <button @click="openModalTambah = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/guru/store" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">NIP (Opsional)</label>
                    <input type="text" name="nip" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Kosongkan jika tidak ada">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nama Lengkap & Gelar</label>
                    <input type="text" name="nama_guru" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: Budi Santoso, S.Pd.">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Username untuk login">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Password</label>
                    <div class="relative">
                        <input :type="showPassTambah ? 'text' : 'password'" name="password" required class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Password akun">
                        <button type="button" @click="showPassTambah = !showPassTambah" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg x-show="!showPassTambah" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showPassTambah" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalTambah = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT GURU -->
    <div x-show="openModalEdit" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalEdit = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Edit Data Guru</h3>
                <button @click="openModalEdit = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/guru/update" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" x-model="editGuru.id">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">NIP (Opsional)</label>
                    <input type="text" name="nip" x-model="editGuru.nip" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nama Lengkap & Gelar</label>
                    <input type="text" name="nama_guru" x-model="editGuru.nama_guru" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Username</label>
                    <input type="text" name="username" x-model="editGuru.username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Password Baru (Opsional)</label>
                    <div class="relative">
                        <input :type="showPassEdit ? 'text' : 'password'" name="password" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <button type="button" @click="showPassEdit = !showPassEdit" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg x-show="!showPassEdit" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showPassEdit" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalEdit = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS SATUAN -->
    <div x-show="openModalHapus" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalHapus = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-lg">!</div>
            <h3 class="font-bold text-gray-900 text-lg mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus data guru ini? Data akun terkait juga akan terhapus.</p>
            <form action="/admin/guru/delete" method="POST" class="flex space-x-3">
                <input type="hidden" name="id" x-model="hapusId">
                <button type="button" @click="openModalHapus = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Batal</button>
                <button type="submit" class="w-1/2 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">Ya, Hapus</button>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS BANYAK (BULK DELETE) -->
    <div x-show="openModalBulkHapus" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalBulkHapus = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-lg">!</div>
            <h3 class="font-bold text-gray-900 text-lg mb-2">Hapus Guru Terpilih</h3>
            <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus <span class="font-bold text-gray-800" x-text="selectedGurus.length"></span> data guru yang dicentang?</p>
            <form action="/admin/guru/bulk-delete" method="POST" class="flex space-x-3">
                <!-- Kirim data ID sebagai JSON string atau hidden input array -->
                <template x-for="id in selectedGurus">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="button" @click="openModalBulkHapus = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Batal</button>
                <button type="submit" class="w-1/2 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">Ya, Hapus</button>
            </form>
        </div>
    </div>

    <!-- MODAL IMPORT EXCEL -->
    <div x-show="openModalImport" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModalImport = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Import Data Guru dari Excel</h3>
                <button @click="openModalImport = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form action="/admin/guru/import" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Pilih Berkas Excel (.xlsx)</label>
                    <input type="file" name="file_excel" accept=".xlsx" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModalImport = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Unggah & Proses</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL UTAMA DENGAN SEARCH & ENTRIES -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mt-6">
        
        <!-- Filter Bar: Search & Per Page -->
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <span class="text-sm text-gray-500">Tampilkan</span>
                <select x-model="perPage" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 p-1.5">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Semua</option>
                </select>
                <span class="text-sm text-gray-500">data</span>
            </div>

            <div class="w-full sm:w-72">
                <input type="text" x-model="search" placeholder="Cari NIP, Nama, atau Username..." class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2">
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="p-4 w-4">
                            <div class="flex items-center">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="isAllSelected" class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500">
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-4">No</th>
                        <th scope="col" class="px-6 py-4">NIP</th>
                        <th scope="col" class="px-6 py-4">Nama Guru</th>
                        <th scope="col" class="px-6 py-4">Username</th>
                        <th scope="col" class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data Tersembunyi (Sumber Asli PHP untuk dibaca Alpine.js) -->
                    <div class="hidden">
                        <?php if (!empty($gurus)): ?>
                            <?php $no = 1; foreach ($gurus as $guru): ?>
                                <tr class="guru-row"
                                    data-id="<?= $guru['id'] ?>"
                                    data-nip="<?= htmlspecialchars($guru['nip'] ?? '-') ?>"
                                    data-nama="<?= htmlspecialchars($guru['nama_guru']) ?>"
                                    data-username="<?= htmlspecialchars($guru['username']) ?>">
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Tampilan Kosong jika tidak ada data -->
                    <template x-if="guruRows.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Belum ada data guru.
                            </td>
                        </tr>
                    </template>

                    <template x-if="guruRows.length > 0 && filteredGurus.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Data guru yang dicari tidak ditemukan.
                            </td>
                        </tr>
                    </template>

                    <!-- Looping Data Menggunakan Alpine.js -->
                    <template x-for="(guru, index) in paginatedGurus" :key="guru.id">
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="p-4 w-4">
                                <div class="flex items-center">
                                    <input type="checkbox" :value="guru.id" x-model="selectedGurus" class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500">
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900" x-text="(perPage === 'all') ? (index + 1) : ((currentPage - 1) * parseInt(perPage) + index + 1)"></td>
                            <td class="px-6 py-4" x-text="guru.nip"></td>
                            <td class="px-6 py-4 font-medium text-gray-900" x-text="guru.nama"></td>
                            <td class="px-6 py-4" x-text="guru.username"></td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center space-x-2">
                                    <!-- Tombol Edit -->
                                    <button @click="editGuru = { id: guru.id, nip: guru.nip === '-' ? '' : guru.nip, nama_guru: guru.nama, username: guru.username }; openModalEdit = true;"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition shadow-sm">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Edit
                                    </button>

                                    <!-- Tombol Hapus Satuan -->
                                    <button @click="hapusId = guru.id; openModalHapus = true;"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition shadow-sm">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer Pagination Info -->
        <div class="p-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white" x-show="perPage !== 'all' && totalPages > 1">
            <div class="text-sm text-gray-500">
                Menampilkan halaman <span class="font-semibold text-gray-800" x-text="currentPage"></span> dari <span class="font-semibold text-gray-800" x-text="totalPages"></span> (<span x-text="filteredGurus.length"></span> total data)
            </div>
            
            <div class="inline-flex space-x-1">
                <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 text-sm font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Sebelumnya
                </button>
                <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 text-sm font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Selanjutnya
                </button>
            </div>
        </div>

    </div>
</div>