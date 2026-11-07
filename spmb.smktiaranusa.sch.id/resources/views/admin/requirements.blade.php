@extends('layouts.admin')

@section('title', 'Manajemen Persyaratan SPMB')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Persyaratan SPMB</h1>
        <p class="text-gray-500 text-sm mt-1">Atur syarat dokumen dan ketentuan untuk jalur Reguler maupun Beasiswa.</p>
    </div>
    <button data-modal-target="addReqModal" data-modal-toggle="addReqModal" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 shadow-sm transition-colors">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        Tambah Syarat
    </button>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold w-1/4">Judul Persyaratan</th>
                    <th scope="col" class="px-6 py-4 font-semibold w-1/6">Jalur / Tipe</th>
                    <th scope="col" class="px-6 py-4 font-semibold w-5/12">Deskripsi Detail</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requirements as $req)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $req->title }}</td>
                    <td class="px-6 py-4">
                        @if($req->type == 'Beasiswa')
                            <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-1 rounded border border-purple-400">Beasiswa</span>
                        @elseif($req->type == 'Reguler')
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded border border-blue-400">Reguler</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded border border-gray-400">Umum</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">{{ $req->description }}</td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                        <button data-modal-target="editReqModal{{ $req->id }}" data-modal-toggle="editReqModal{{ $req->id }}" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <form action="{{ route('admin.requirements.destroy', $req->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus persyaratan ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Modal Edit -->
                <div id="editReqModal{{ $req->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                    <div class="relative p-4 w-full max-w-lg max-h-full">
                        <div class="relative bg-white rounded-2xl shadow">
                            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                                <h3 class="text-lg font-bold text-gray-900">Edit Persyaratan</h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="editReqModal{{ $req->id }}">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                </button>
                            </div>
                            <form action="{{ route('admin.requirements.update', $req->id) }}" method="POST" class="p-4 md:p-5 space-y-4">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="col-span-2">
                                        <label class="block mb-2 text-sm font-medium text-gray-900">Judul Syarat</label>
                                        <input type="text" name="title" value="{{ $req->title }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                    </div>
                                    <div class="col-span-1">
                                        <label class="block mb-2 text-sm font-medium text-gray-900">Jalur / Tipe</label>
                                        <select name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                            <option value="Umum" {{ $req->type == 'Umum' ? 'selected' : '' }}>Umum</option>
                                            <option value="Reguler" {{ $req->type == 'Reguler' ? 'selected' : '' }}>Reguler</option>
                                            <option value="Beasiswa" {{ $req->type == 'Beasiswa' ? 'selected' : '' }}>Beasiswa</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Deskripsi Lengkap</label>
                                    <textarea name="description" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>{{ $req->description }}</textarea>
                                </div>
                                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-bold rounded-lg text-sm px-5 py-2.5 text-center">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada data persyaratan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div id="addReqModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-bold text-gray-900">Tambah Persyaratan Baru</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="addReqModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                </button>
            </div>
            <form action="{{ route('admin.requirements.store') }}" method="POST" class="p-4 md:p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Judul Syarat</label>
                        <input type="text" name="title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Contoh: Fotokopi Ijazah" required>
                    </div>
                    <div class="col-span-1">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Jalur / Tipe</label>
                        <select name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <!--<option value="Umum" selected>Umum</option>-->
                            <option value="Reguler">Reguler</option>
                            <option value="Beasiswa">Beasiswa</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Deskripsi Lengkap</label>
                    <textarea name="description" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Penjelasan kelengkapan dokumen..." required></textarea>
                </div>
                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-bold rounded-lg text-sm px-5 py-2.5 text-center">Simpan Persyaratan</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', buttonsStyling: false, customClass: { popup: 'rounded-3xl', confirmButton: 'bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-6 rounded-xl' }});
    @endif
    @if($errors->any())
        Swal.fire({ icon: 'error', title: 'Gagal', text: '{{ $errors->first() }}', buttonsStyling: false, customClass: { popup: 'rounded-3xl', confirmButton: 'bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-6 rounded-xl' }});
    @endif
</script>
@endsection