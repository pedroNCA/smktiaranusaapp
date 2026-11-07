@extends('layouts.admin')
@section('title', 'Kelola Soal TPA')

@section('content')
<!-- Alpine Data: Modal states, Edit data, and Checkbox state for Bulk Delete -->
<div x-data="{ 
    showAddModal: false, 
    showImportModal: false, 
    showEditModal: false, 
    editData: {}, 
    selectedQuestions: [],
    selectAll: false,
    toggleAll() {
        if (this.selectAll) {
            this.selectedQuestions = {{ $questions->pluck('id')->toJson() }};
        } else {
            this.selectedQuestions = [];
        }
    }
}">
    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Kelola Soal TPA</h2>
            <p class="text-sm text-gray-500">Manajemen soal Matematika, Bahasa Indonesia, dan Bahasa Inggris</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <!-- Tombol Hapus Massal (Muncul jika ada ceklis) -->
            <form x-show="selectedQuestions.length > 0" x-cloak action="{{ route('admin.tpa.soal.bulk-delete') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus semua soal yang dipilih?');">
                @csrf @method('DELETE')
                <template x-for="id in selectedQuestions" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-red-700 flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus Terpilih (<span x-text="selectedQuestions.length"></span>)
                </button>
            </form>

            <!-- Tombol Import Excel (Diperbaiki warnanya agar jelas) -->
            <button @click="showImportModal = true" type="button" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Import Excel
            </button>

            <!-- Tombol Tambah Soal -->
            <button @click="showAddModal = true; editData={}" type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Soal
            </button>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
            <select name="mapel" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-blue-500">
                <option value="">Semua Mata Pelajaran</option>
                <option value="Matematika" {{ $mapel == 'Matematika' ? 'selected' : '' }}>Matematika</option>
                <option value="Bahasa Indonesia" {{ $mapel == 'Bahasa Indonesia' ? 'selected' : '' }}>Bahasa Indonesia</option>
                <option value="Bahasa Inggris" {{ $mapel == 'Bahasa Inggris' ? 'selected' : '' }}>Bahasa Inggris</option>
            </select>
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari pertanyaan..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-blue-500 flex-1">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-gray-900">Filter</button>
        </form>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-100 text-green-700 p-3 rounded-lg text-sm border border-green-200">
        {{ session('success') }}
    </div>
@endif

@if(session('warning'))
    <div class="mb-4 bg-yellow-100 text-yellow-700 p-3 rounded-lg text-sm border border-yellow-200">
        {{ session('warning') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg text-sm border border-red-200">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg text-sm border border-red-200">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

    <!-- Tabel Soal -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 text-xs uppercase">
                        <th class="p-4 border-b w-12 text-center">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-4 h-4 text-blue-600 rounded">
                        </th>
                        <th class="p-4 border-b w-16 text-center">No</th>
                        <th class="p-4 border-b">Pertanyaan & Pilihan</th>
                        <th class="p-4 border-b text-center">Kunci</th>
                        <th class="p-4 border-b text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $currentMapel = ''; @endphp

                    @forelse($questions as $index => $q)
                        <!-- Pengelompokan Berdasarkan Mapel -->
                        @if($currentMapel != $q->mapel)
                            <tr class="bg-blue-50/50">
                                <td colspan="5" class="p-3 border-b text-blue-800 font-bold uppercase text-xs tracking-wider">
                                    Mata Pelajaran: {{ $q->mapel }}
                                </td>
                            </tr>
                            @php $currentMapel = $q->mapel; @endphp
                        @endif

                        <tr class="hover:bg-gray-50 border-b">
                            <td class="p-4 text-center">
                                <input type="checkbox" x-model="selectedQuestions" value="{{ $q->id }}" class="w-4 h-4 text-blue-600 rounded">
                            </td>
                            <td class="p-4 text-center">{{ $questions->firstItem() + $index }}</td>
                            <td class="p-4 text-gray-800">
                                <!-- Tampilkan Gambar Jika Ada -->
                                @if($q->image)
                                    <div class="mb-3">
                                        <img src="{{ asset('storage/' . $q->image) }}" class="h-20 object-contain border border-gray-200 rounded p-1" alt="Gambar Soal">
                                    </div>
                                @endif
                                
                                <div class="font-medium text-base mb-2">{!! nl2br(e($q->question)) !!}</div>
                                
                                <!-- Opsi Jawaban (Hanya Tampil Jika Diisi) -->
                                <div class="text-xs text-gray-600 flex flex-wrap gap-2">
                                    @if($q->option_a) <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200"><strong class="text-gray-900">A.</strong> {{ $q->option_a }}</span> @endif
                                    @if($q->option_b) <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200"><strong class="text-gray-900">B.</strong> {{ $q->option_b }}</span> @endif
                                    @if($q->option_c) <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200"><strong class="text-gray-900">C.</strong> {{ $q->option_c }}</span> @endif
                                    @if($q->option_d) <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200"><strong class="text-gray-900">D.</strong> {{ $q->option_d }}</span> @endif
                                    @if($q->option_e) <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200"><strong class="text-gray-900">E.</strong> {{ $q->option_e }}</span> @endif
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-green-100 text-green-800 font-bold px-3 py-1 rounded-full text-xs">{{ $q->correct_answer }}</span>
                            </td>
                            <td class="p-4 text-center flex justify-center gap-2">
                                <button @click="showEditModal = true; editData = {{ json_encode($q) }}" type="button" class="text-blue-600 hover:bg-blue-100 p-1.5 rounded transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <form action="{{ route('admin.tpa.soal.destroy', $q->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus soal ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:bg-red-100 p-1.5 rounded transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada soal TPA yang ditambahkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">{{ $questions->links() }}</div>
    </div>

    <!-- Modal Tambah Soal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAddModal" @click="showAddModal = false" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <!-- Tambah enctype untuk gambar -->
                <form action="{{ route('admin.tpa.soal.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 border-b pb-2">Tambah Soal Baru</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran <span class="text-red-500">*</span></label>
                                <select name="mapel" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                                    <option value="Matematika">Matematika</option>
                                    <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                                    <option value="Bahasa Inggris">Bahasa Inggris</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Pendukung (Opsional)</label>
                                <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded-lg text-sm p-1.5 bg-gray-50">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan <span class="text-red-500">*</span></label>
                            <textarea name="question" required rows="3" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan A <span class="text-red-500">*</span></label>
                                <input type="text" name="option_a" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan B <span class="text-red-500">*</span></label>
                                <input type="text" name="option_b" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan C</label>
                                <input type="text" name="option_c" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan D</label>
                                <input type="text" name="option_d" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan E (Opsional)</label>
                                <input type="text" name="option_e" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kunci Jawaban Benar <span class="text-red-500">*</span></label>
                            <select name="correct_answer" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                                <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option><option value="E">E</option>
                            </select>
                        </div>
                    </div>
                    <div class="bg-gray-100 px-6 py-4 flex justify-end gap-3 rounded-b-xl border-t border-gray-200">
                        <button type="button" @click="showAddModal = false" class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 rounded-lg text-sm font-bold text-white hover:bg-blue-700 transition">Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Soal (Sempurna dengan Alpine.js bindings) -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditModal" @click="showEditModal = false" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <!-- Action dinamis berdasarkan ID dari editData -->
                <form :action="`{{ url('admin/observasi/soal') }}/${editData.id}`" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 border-b pb-2">Edit Soal</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran <span class="text-red-500">*</span></label>
                                <select name="mapel" x-model="editData.mapel" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                                    <option value="Matematika">Matematika</option>
                                    <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                                    <option value="Bahasa Inggris">Bahasa Inggris</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Update Gambar (Opsional)</label>
                                <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded-lg text-sm p-1.5 bg-gray-50">
                                <p class="text-[10px] text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah gambar.</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan <span class="text-red-500">*</span></label>
                            <textarea name="question" x-model="editData.question" required rows="3" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan A <span class="text-red-500">*</span></label>
                                <input type="text" name="option_a" x-model="editData.option_a" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan B <span class="text-red-500">*</span></label>
                                <input type="text" name="option_b" x-model="editData.option_b" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan C</label>
                                <input type="text" name="option_c" x-model="editData.option_c" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan D</label>
                                <input type="text" name="option_d" x-model="editData.option_d" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilihan E (Opsional)</label>
                                <input type="text" name="option_e" x-model="editData.option_e" class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kunci Jawaban Benar <span class="text-red-500">*</span></label>
                            <select name="correct_answer" x-model="editData.correct_answer" required class="w-full border-gray-300 rounded-lg text-sm bg-gray-50">
                                <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option><option value="E">E</option>
                            </select>
                        </div>
                    </div>
                    <div class="bg-gray-100 px-6 py-4 flex justify-end gap-3 rounded-b-xl border-t border-gray-200">
                        <button type="button" @click="showEditModal = false" class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 rounded-lg text-sm font-bold text-white hover:bg-blue-700 transition">Update Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Pop-up Import-->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop Gelap -->
            <div x-show="showImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showImportModal = false" class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Konten Pop-up -->
            <div x-show="showImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <form action="{{ route('admin.tpa.soal.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex justify-between items-center mb-4 border-b pb-3">
                            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Import Soal dari CSV
                            </h3>
                            <button type="button" @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mb-5">
    Unggah file template Excel (.xlsx) untuk memasukkan data bank soal secara massal ke sistem.
</p>
                        
                        <!-- Kotak Unduh Template -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 flex flex-col items-center text-center">
                            <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <h4 class="font-bold text-blue-900 text-sm">Belum punya format file?</h4>
                            <p class="text-xs text-blue-700 mb-3 mt-1">Unduh template Excel (.xlsx) standar terlebih dahulu agar proses import berhasil.</p>
                            <a href="{{ route('admin.tpa.soal.template') }}" class="inline-block bg-white text-blue-700 font-bold border border-blue-300 rounded-lg px-4 py-2 text-xs hover:bg-blue-50 shadow-sm transition">
                                Unduh Template .xlsx
                            </a>
                        </div>

                        <div class="mb-2">
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        Pilih File Excel <span class="text-red-500">*</span>
    </label>

    <input
        type="file"
        name="file_excel"
        accept=".xlsx"
        required
        class="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-gray-50 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
    >

    <p class="text-xs text-gray-500 mt-1">
        Format: .xlsx | Maksimal 5 MB
    </p>
</div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                        <button type="button" @click="showImportModal = false" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-sm font-bold text-white shadow-sm transition flex items-center gap-2">
                            Proses Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection