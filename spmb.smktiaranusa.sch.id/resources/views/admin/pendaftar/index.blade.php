@extends('layouts.admin')

@section('title', 'Data Pendaftar - SPMB Admin')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Seluruh Pendaftar</h1>
        <p class="text-gray-500 text-sm mt-1">Daftar siswa yang telah mengisi dan mengirimkan formulir pendaftaran SPMB.</p>
    </div>
</div>

{{-- Baris Filter & Pencarian --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.pendaftar.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
        
        {{-- Search Input --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Pencarian</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ID SPMB / Nama..." class="w-full text-sm border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
        </div>

        {{-- Filter Status --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status</label>
            <select name="status" class="w-full text-sm border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
                <option value="">Semua Status</option>
                <option value="SUBMITTED" {{ request('status') == 'SUBMITTED' ? 'selected' : '' }}>SUBMITTED</option>
                <option value="WAITING_PAYMENT" {{ request('status') == 'WAITING_PAYMENT' ? 'selected' : '' }}>WAITING PAYMENT</option>
                <option value="PAYMENT_REVIEW" {{ request('status') == 'PAYMENT_REVIEW' ? 'selected' : '' }}>PAYMENT REVIEW</option>
                <option value="VERIFIED" {{ request('status') == 'VERIFIED' ? 'selected' : '' }}>VERIFIED</option>
                <option value="ACCEPTED" {{ request('status') == 'ACCEPTED' ? 'selected' : '' }}>ACCEPTED</option>
            </select>
        </div>

        {{-- Tampilkan per halaman --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tampilkan</label>
            <select name="per_page" class="w-full text-sm border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border">
                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 data</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 data</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 data</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 data</option>
            </select>
        </div>

        {{-- Tombol Aksi Filter --}}
        <div class="flex items-end gap-2 pt-5">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-sm w-full">
                Filter
            </button>
            <a href="{{ route('admin.pendaftar.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors text-center">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50/75 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-bold">ID SPMB</th>
                    <th scope="col" class="px-6 py-4 font-bold">Nama Lengkap</th>
                    <th scope="col" class="px-6 py-4 font-bold">Asal Sekolah</th>
                    <th scope="col" class="px-6 py-4 font-bold">Pilihan Jurusan</th>
                    <th scope="col" class="px-6 py-4 font-bold">Status Pendaftaran</th>
                    <th scope="col" class="px-6 py-4 font-bold">No. WhatsApp</th>
                    <th scope="col" class="px-6 py-4 font-bold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pendaftar as $item)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $item->spmb_id }}</td>
                    <td class="px-6 py-4 font-semibold text-gray-800">{{ $item->student->user->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $item->student->school_origin ?? '-' }}</td>
                    
                    <td class="px-6 py-4">
                        @if($item->major)
                            <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-1 rounded border border-gray-300">
                                {{ $item->major }}
                            </span>
                        @else
                            <span class="text-gray-400 italic">Belum memilih</span>
                        @endif
                    </td>
                    
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'SUBMITTED' => 'bg-blue-100 text-blue-800 border-blue-300',
                                'WAITING_PAYMENT' => 'bg-amber-100 text-amber-800 border-amber-300',
                                'PAYMENT_REVIEW' => 'bg-orange-100 text-orange-800 border-orange-300',
                                'VERIFIED' => 'bg-green-100 text-green-800 border-green-300',
                                'ACCEPTED' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            ];
                            $colorClass = $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                        @endphp
                        <span class="{{ $colorClass }} text-xs font-bold px-3 py-1 rounded-full border">
                            {{ str_replace('_', ' ', $item->status) }}
                        </span>
                    </td>
                    
                    <td class="px-6 py-4">
                        @php
                            $rawWa = $item->user->whatsapp ?? ($item->student->user->whatsapp ?? null);
                            $wa = $rawWa;
                            if($wa && substr($wa, 0, 1) == '0') {
                                $wa = '62' . substr($wa, 1);
                            }
                        @endphp
                        @if($rawWa)
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-gray-700">{{ $rawWa }}</span>
                                <a href="https://wa.me/{{ $wa }}" target="_blank" class="inline-flex items-center justify-center px-2 py-1 text-[11px] font-semibold text-green-700 bg-green-100 rounded hover:bg-green-200 transition-colors w-fit">
                                    <i class="fab fa-whatsapp mr-1"></i> Chat WA
                                </a>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Tidak ada</span>
                        @endif
                    </td>
                    
                    {{-- Kolom Aksi --}}
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.pendaftar.detail', $item->id) }}" class="inline-flex items-center px-3.5 py-2 text-xs font-bold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                Detail
                            </a>
                            
                            {{-- Tombol Edit --}}
                            <a href="{{ route('admin.pendaftar.edit', $item->id) }}" class="inline-flex items-center px-3.5 py-2 text-xs font-bold text-amber-600 bg-amber-50 rounded-xl hover:bg-amber-600 hover:text-white transition-all shadow-sm">
                                Edit
                            </a>
                            
                            {{-- Tombol Hapus Total --}}
                            <form action="{{ route('admin.pendaftar.destroyTotal', $item->id) }}" method="POST" data-title="Hapus Total Pendaftar?" data-text="PERINGATAN: Semua berkas, pembayaran, dan histori pendaftaran dari pendaftar ini akan dihapus permanen.">
                                @csrf @method('DELETE')
                                <button type="button" onclick="konfirmasiAksi(this, 'Ya, Hapus Permanen', '#d33')" class="inline-flex items-center px-3 py-2 text-xs font-bold text-red-600 bg-red-50 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Hapus Total Data Pendaftar">
                                    <i class="fas fa-trash-alt">Delete</i>
                                </button>
                            </form>
                        </div>
                    </td>
                    
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                        Belum ada data pendaftar yang masuk atau sesuai dengan pencarian.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Info Jumlah Data & Pagination --}}
    @if($pendaftar->hasPages() || $pendaftar->total() > 0)
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs text-gray-500">
            Menampilkan <strong>{{ $pendaftar->firstItem() ?? 0 }}</strong> sampai <strong>{{ $pendaftar->lastItem() ?? 0 }}</strong> dari total <strong>{{ $pendaftar->total() }}</strong> pendaftar
        </div>
        <div>
            {{ $pendaftar->links() }}
        </div>
    </div>
    @endif
</div>

{{-- Script untuk memunculkan SweetAlert2 pada tombol hapus --}}
<script>
function konfirmasiAksi(button, confirmText, confirmColor) {
    const form = button.closest('form');
    Swal.fire({
        title: form.getAttribute('data-title'),
        text: form.getAttribute('data-text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Memproses...', text: 'Sedang menghapus data dan file terkait.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            form.submit();
        }
    });
}
</script>
@endsection