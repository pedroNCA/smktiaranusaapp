@extends('layouts.admin')

@section('title', 'Manajemen Rincian Biaya - SPMB')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-all duration-300">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Rincian Biaya Pendaftaran</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola daftar komponen biaya pendidikan yang tampil di halaman publik.</p>
    </div>
    <button data-modal-target="addCostModal" data-modal-toggle="addCostModal" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 shadow-md hover:shadow-lg transition-all duration-200 active:scale-95">
        <svg class="w-5 h-5 mr-1.5 transition-transform duration-200 hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        Tambah Komponen Biaya
    </button>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50/75 border-b border-gray-200 tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-4 font-bold w-1/4">Komponen Biaya</th>
                    <th scope="col" class="px-6 py-4 font-bold w-1/4">Nominal (Rp)</th>
                    <th scope="col" class="px-6 py-4 font-bold w-1/3">Keterangan</th>
                    <th scope="col" class="px-6 py-4 font-bold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $totalBiaya = 0; @endphp
                @forelse ($costs as $cost)
                @php $totalBiaya += $cost->amount; @endphp
                <tr class="hover:bg-blue-50/40 transition-all duration-200 group">
                    <td class="px-6 py-4 font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $cost->name }}</td>
                    <td class="px-6 py-4 font-extrabold text-blue-600">Rp {{ number_format($cost->amount, 0, ',', '.') }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $cost->description ?? '-' }}</td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                        <button data-modal-target="editCostModal{{ $cost->id }}" data-modal-toggle="editCostModal{{ $cost->id }}" class="text-blue-600 hover:text-white bg-blue-50 hover:bg-blue-600 p-2 rounded-xl transition-all duration-200 shadow-sm active:scale-95" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <form action="{{ route('admin.costs.destroy', $cost->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus komponen biaya ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-white bg-red-50 hover:bg-red-600 p-2 rounded-xl transition-all duration-200 shadow-sm active:scale-95" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Modal Edit -->
                <div id="editCostModal{{ $cost->id }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full transition-all">
                    <div class="relative p-4 w-full max-w-md max-h-full">
                        <div class="relative bg-white rounded-3xl shadow-2xl border border-gray-100">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 rounded-t">
                                <h3 class="text-lg font-bold text-gray-900">Edit Rincian Biaya</h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-xl text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-toggle="editCostModal{{ $cost->id }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                </button>
                            </div>
                            <form action="{{ route('admin.costs.update', $cost->id) }}" method="POST" class="p-6 space-y-4">
                                @csrf @method('PUT')
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Komponen Biaya</label>
                                    <input type="text" name="name" value="{{ $cost->name }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-colors" required>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Nominal (Rupiah)</label>
                                    <input type="number" step="0.01" name="amount" value="{{ $cost->amount }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-colors" required>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Keterangan / Deskripsi</label>
                                    <textarea name="description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-colors">{{ $cost->description }}</textarea>
                                </div>
                                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-xl text-sm px-5 py-3 shadow-md transition-all active:scale-95">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500 italic">Belum ada rincian biaya yang ditambahkan.</td></tr>
                @endforelse
            </tbody>
            @if($costs->isNotEmpty())
            <!-- Baris Total Keseluruhan (Grand Total) -->
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td class="px-6 py-4 font-bold text-gray-900 uppercase text-xs tracking-wider">Total Keseluruhan Biaya</td>
                    <td class="px-6 py-4 font-extrabold text-blue-700 text-base">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
                    <td colspan="2" class="px-6 py-4 text-right text-xs text-gray-500 italic">Akumulasi seluruh komponen di atas</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div id="addCostModal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full transition-all">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-3xl shadow-2xl border border-gray-100">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 rounded-t">
                <h3 class="text-lg font-bold text-gray-900">Tambah Komponen Biaya</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-xl text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-toggle="addCostModal">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                </button>
            </div>
            <form action="{{ route('admin.costs.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Komponen Biaya</label>
                    <input type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-colors" placeholder="Contoh: Dana Pengembangan / Seragam" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nominal (Rupiah)</label>
                    <input type="number" step="0.01" name="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-colors" placeholder="Contoh: 1500000" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Keterangan / Deskripsi</label>
                    <textarea name="description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-colors" placeholder="Penjelasan opsional..."></textarea>
                </div>
                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-xl text-sm px-5 py-3 shadow-md transition-all active:scale-95">Simpan Biaya</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success')) 
        Swal.fire({ 
            icon: 'success', 
            title: 'Berhasil', 
            text: '{{ session('success') }}', 
            buttonsStyling: false, 
            customClass: { 
                popup: 'rounded-3xl shadow-xl', 
                confirmButton: 'bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-6 rounded-xl transition-colors' 
            } 
        }); 
    @endif
</script>
@endsection