@extends('layouts.admin')

@section('title', 'Manajemen FAQ SPMB')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">FAQ SPMB</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola daftar pertanyaan yang sering diajukan untuk halaman publik.</p>
    </div>
    <button data-modal-target="addFaqModal" data-modal-toggle="addFaqModal" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 shadow-sm transition-colors">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        Tambah FAQ
    </button>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold w-1/3">Pertanyaan</th>
                    <th scope="col" class="px-6 py-4 font-semibold w-1/6">Kategori</th>
                    <th scope="col" class="px-6 py-4 font-semibold w-1/3">Jawaban</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($faqs as $faq)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $faq->question }}</td>
                    <td class="px-6 py-4">
                        @if($faq->category == 'Orang Tua')
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded border border-blue-400">Orang Tua</span>
                        @elseif($faq->category == 'Alumni')
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-1 rounded border border-green-400">Alumni</span>
                        @elseif($faq->category == 'Calon Siswa')
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-1 rounded border border-yellow-400">Calon Siswa</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded border border-gray-400">Umum</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">{{ Str::limit($faq->answer, 80) }}</td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                        <button data-modal-target="editFaqModal{{ $faq->id }}" data-modal-toggle="editFaqModal{{ $faq->id }}" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <form action="{{ route('admin.faqs.destroy', $faq->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus FAQ ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Modal Edit FAQ -->
                <div id="editFaqModal{{ $faq->id }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                    <div class="relative p-4 w-full max-w-lg max-h-full">
                        <div class="relative bg-white rounded-2xl shadow">
                            <div class="flex items-center justify-between p-4 border-b rounded-t">
                                <h3 class="text-lg font-bold text-gray-900">Edit FAQ</h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 rounded-lg text-sm w-8 h-8 ms-auto" data-modal-toggle="editFaqModal{{ $faq->id }}">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                </button>
                            </div>
                            <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST" class="p-4 space-y-4">
                                @csrf @method('PUT')
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Pertanyaan</label>
                                    <input type="text" name="question" value="{{ $faq->question }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Kategori Target</label>
                                    <select name="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                        <option value="Umum" {{ $faq->category == 'Umum' ? 'selected' : '' }}>Umum</option>
                                        <option value="Calon Siswa" {{ $faq->category == 'Calon Siswa' ? 'selected' : '' }}>Calon Siswa</option>
                                        <option value="Orang Tua" {{ $faq->category == 'Orang Tua' ? 'selected' : '' }}>Orang Tua</option>
                                        <option value="Alumni" {{ $faq->category == 'Alumni' ? 'selected' : '' }}>Alumni</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Jawaban</label>
                                    <textarea name="answer" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>{{ $faq->answer }}</textarea>
                                </div>
                                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-5 py-2.5">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada data FAQ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah FAQ -->
<div id="addFaqModal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow">
            <div class="flex items-center justify-between p-4 border-b rounded-t">
                <h3 class="text-lg font-bold text-gray-900">Tambah FAQ Baru</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 rounded-lg text-sm w-8 h-8 ms-auto" data-modal-toggle="addFaqModal">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                </button>
            </div>
            <form action="{{ route('admin.faqs.store') }}" method="POST" class="p-4 space-y-4">
                @csrf
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Pertanyaan</label>
                    <input type="text" name="question" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Contoh: Kapan pendaftaran ditutup?" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kategori Target</label>
                    <select name="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        <option value="Umum" selected>Umum</option>
                        <option value="Calon Siswa">Calon Siswa</option>
                        <option value="Orang Tua">Orang Tua</option>
                        <option value="Alumni">Alumni</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Jawaban</label>
                    <textarea name="answer" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required></textarea>
                </div>
                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-5 py-2.5">Simpan FAQ</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success')) Swal.fire({ icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', buttonsStyling: false, customClass: { popup: 'rounded-3xl', confirmButton: 'bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-6 rounded-xl' }}); @endif
</script>
@endsection