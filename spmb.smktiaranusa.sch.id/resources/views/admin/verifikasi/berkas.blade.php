@extends('layouts.admin')
@section('title', 'Verifikasi Berkas - SPMB Admin')
@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Verifikasi Berkas</h1>
    <p class="text-gray-500 text-sm mt-1">Kelola, periksa, dan verifikasi dokumen persyaratan calon siswa.</p>
</div>

{{-- Panel Filter, Pencarian, & Jumlah Tampil --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-5 mb-6">
    <form method="GET" action="{{ route('admin.verifikasi.berkas') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="w-full">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pencarian</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari SPMB / Nama..." class="w-full text-sm border-gray-300 rounded-xl shadow-sm pl-10 pr-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="w-full">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Status Berkas</label>
            <select name="status" class="w-full text-sm border-gray-300 rounded-xl shadow-sm px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Status</option>
                <option value="DOCUMENT_REVIEW" {{ request('status') == 'DOCUMENT_REVIEW' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="ACCEPTED" {{ request('status') == 'ACCEPTED' ? 'selected' : '' }}>Sudah Verifikasi (Selesai)</option>
            </select>
        </div>
        <div class="w-full">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tampilkan</label>
            <select name="per_page" class="w-full text-sm border-gray-300 rounded-xl shadow-sm px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 Data</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Data</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Data</option>
            </select>
        </div>
        <div class="flex items-center gap-2 w-full">
            <button type="submit" class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-sm transition">Terapkan</button>
            <a href="{{ route('admin.verifikasi.berkas') }}" class="px-4 py-2.5 bg-gray-100 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-200 transition" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
        </div>
    </form>
</div>

{{-- Tabel Utama --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden" x-data="{ openModal: false, currentReg: {} }">
    <div class="overflow-x-auto w-full">
        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-4 font-bold">ID SPMB & Nama</th>
                    <th class="px-5 py-4 font-bold">Jurusan</th>
                    <th class="px-5 py-4 font-bold text-center">Kelengkapan</th>
                    <th class="px-5 py-4 font-bold text-center">Status</th>
                    <th class="px-5 py-4 font-bold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($registrations as $reg)
                <tr class="hover:bg-blue-50/50 transition-colors {{ $reg->status == 'DOCUMENT_REVIEW' ? 'bg-orange-50/20' : '' }}">
                    <td class="px-5 py-4">
                        <div class="font-bold text-gray-900">{{ $reg->spmb_id }}</div>
                        <div class="text-xs font-semibold text-gray-500 mt-0.5">{{ $reg->student->user->name ?? ($reg->user->name ?? '-') }}</div>
                    </td>
                    <td class="px-5 py-4 font-medium">{{ $reg->major ?? '-' }}</td>
                    <td class="px-5 py-4 text-center font-bold {{ count($reg->documents) > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                        {{ count($reg->documents) }} Dokumen
                    </td>
                    
                    <td class="px-5 py-4 text-center">
                        @if($reg->status == 'ACCEPTED')
                            <span class="inline-flex items-center bg-green-100 text-green-700 border border-green-200 text-[11px] font-bold px-2.5 py-1 rounded-full"><i class="fas fa-check-circle mr-1"></i> Terverifikasi</span>
                        @elseif($reg->status == 'DOCUMENT_REVIEW')
                            <span class="inline-flex items-center bg-orange-100 text-orange-700 border border-orange-200 text-[11px] font-bold px-2.5 py-1 rounded-full animate-pulse"><i class="fas fa-clock mr-1"></i> Menunggu</span>
                        @else
                            <span class="inline-flex items-center bg-gray-100 text-gray-700 border border-gray-200 text-[11px] font-bold px-2.5 py-1 rounded-full">{{ str_replace('_', ' ', $reg->status) }}</span>
                        @endif
                    </td>
                    
                    <td class="px-5 py-4 text-center">
                        {{-- Logika Penampilan Tombol Tabel --}}
                        @if(count($reg->documents) == 0)
                            <span class="text-xs text-gray-400 italic"><i class="fas fa-hourglass-half mr-1"></i> Belum Upload</span>
                        @else
                            <button @click="openModal = true; currentReg = {{ json_encode($reg) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm transition {{ $reg->status == 'ACCEPTED' ? 'bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                                <i class="fas {{ $reg->status == 'ACCEPTED' ? 'fa-eye' : 'fa-folder-open' }} mr-1.5"></i> 
                                {{ $reg->status == 'ACCEPTED' ? 'Lihat' : 'Periksa' }}
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-gray-400 font-medium">Tidak ada data berkas yang sesuai filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($registrations->hasPages())
    <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-xs font-medium text-gray-500 text-center md:text-left">
            Menampilkan <strong>{{ $registrations->firstItem() ?? 0 }}</strong> sampai <strong>{{ $registrations->lastItem() ?? 0 }}</strong> dari <strong>{{ $registrations->total() }}</strong> pendaftar
        </div>
        <div class="w-full md:w-auto overflow-x-auto">{{ $registrations->links() }}</div>
    </div>
    @endif

    {{-- MODAL ALPINE JS --}}
    <div x-show="openModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4" style="display: none;" x-cloak>
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="openModal = false">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <span x-text="currentReg.status === 'ACCEPTED' ? 'Detail Berkas:' : 'Pemeriksaan Berkas:'"></span> 
                    <span x-text="currentReg.spmb_id"></span>
                </h3>
                <button @click="openModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
            </div>

            <div class="space-y-4">
                <p class="text-sm font-semibold text-gray-700">Nama Pendaftar: <span class="text-blue-600 font-bold" x-text="currentReg.student?.user?.name || '-'"></span></p>

                <div class="border rounded-xl p-4 bg-gray-50 space-y-3">
                    <h4 class="text-xs font-bold text-gray-500 uppercase">Daftar Dokumen Diunggah:</h4>
                    <template x-if="currentReg.documents && currentReg.documents.length > 0">
                        <ul class="space-y-2">
                            <template x-for="doc in currentReg.documents" :key="doc.id">
                                <li class="flex flex-col sm:flex-row justify-between sm:items-center bg-white p-3 rounded-lg border shadow-sm gap-3">
                                    <div>
                                        <span class="font-bold text-sm text-gray-800" x-text="doc.type"></span>
                                        <span class="block text-[11px] text-gray-400 truncate max-w-[200px]" x-text="doc.file_name"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a x-bind:href="'{{ route('admin.verifikasi.berkas.dokumen.lihat', 'DOC_ID') }}'.replace('DOC_ID', doc.id)" target="_blank" class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded hover:bg-indigo-100 shadow-sm">Buka File</a>
                                        
                                        {{-- Sembunyikan tombol hapus individual jika status sudah ACCEPTED --}}
                                        <template x-if="currentReg.status !== 'ACCEPTED'">
                                            <form x-bind:action="`{{ url('admin/verifikasi/berkas/dokumen') }}/${doc.id}`" method="POST" onsubmit="return confirm('Yakin ingin menghapus dokumen ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-1 bg-red-50 text-red-600 text-xs rounded hover:bg-red-100 shadow-sm" title="Hapus Berkas Jika Buram/Salah"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </template>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="!currentReg.documents || currentReg.documents.length === 0">
                        <p class="text-xs text-gray-400 italic">Belum ada dokumen diunggah.</p>
                    </template>
                </div>

                {{-- TAMPILKAN TOMBOL AKSI HANYA JIKA BELUM DIVERIFIKASI DAN ADA DOKUMEN --}}
                <template x-if="currentReg.status !== 'ACCEPTED' && currentReg.documents && currentReg.documents.length > 0">
                    <div class="space-y-4">
                        <div class="pt-2">
                            <template x-if="currentReg.student?.user?.whatsapp || currentReg.user?.whatsapp">
                                <a :href="'https://wa.me/62' + (currentReg.student?.user?.whatsapp || currentReg.user?.whatsapp).replace(/^0+/, '') + '?text=Halo%20' + (currentReg.student?.user?.name || '') + ',%20terkait%20berkas%20pendaftaran%20SPMB%20Anda%20(ID:%20' + currentReg.spmb_id + '),%20ada%20beberapa%20dokumen%20yang%20perlu%20diperbaiki...'" target="_blank" class="w-full flex items-center justify-center gap-2 bg-green-600 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-green-700 shadow-sm">
                                    <i class="fab fa-whatsapp text-lg"></i> Chat WA untuk Perbaikan
                                </a>
                            </template>
                        </div>

                        {{-- Aksi Ubah Status Berkas --}}
                        <div class="border-t pt-4">
                            <div class="flex flex-col sm:flex-row justify-end gap-2">
                                {{-- Form Tolak Berkas --}}
                                <form :action="'{{ route('admin.verifikasi.berkas.status', ':id') }}'.replace(':id', currentReg.id)" method="POST" class="w-full sm:w-auto" data-title="Tolak / Review Ulang Berkas?" data-text="Status akan dikembalikan agar siswa dapat memperbaiki berkas.">
                                    @csrf 
                                    @method('PUT')
                                    <input type="hidden" name="status" value="VERIFIED"> {{-- Status turun kembali ke VERIFIED agar bisa upload ulang --}}
                                    <button type="button" onclick="konfirmasiAksi(this, 'Tolak / Perbaikan', '#d33')" class="w-full px-4 py-2 bg-amber-500 text-white text-xs font-bold rounded-xl hover:bg-amber-600 shadow-sm transition">
                                        <i class="fas fa-undo mr-1"></i> Tolak / Review Ulang
                                    </button>
                                </form>

                                {{-- Form Setujui Berkas --}}
                                <form :action="'{{ route('admin.verifikasi.berkas.status', ':id') }}'.replace(':id', currentReg.id)" method="POST" class="w-full sm:w-auto" data-title="Verifikasi & Setujui Berkas?" data-text="Pastikan semua berkas valid. Status siswa akan dinyatakan Diterima.">
                                    @csrf 
                                    @method('PUT')
                                    <input type="hidden" name="status" value="ACCEPTED">
                                    <button type="button" onclick="konfirmasiAksi(this, 'Ya, Setujui', '#16a34a')" class="w-full px-4 py-2 bg-green-600 text-white text-xs font-bold rounded-xl hover:bg-green-700 shadow-sm transition">
                                        <i class="fas fa-check-double mr-1"></i> Setujui & Verifikasi
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- TAMPILKAN INFO JIKA SUDAH DIVERIFIKASI --}}
                <template x-if="currentReg.status === 'ACCEPTED'">
                    <div class="border-t mt-4 pt-4 text-center">
                        <span class="inline-flex items-center px-5 py-2.5 bg-green-50 text-green-700 font-bold rounded-xl border border-green-200 text-sm">
                            <i class="fas fa-check-circle mr-2 text-green-600"></i> Berkas telah diverifikasi dan disetujui
                        </span>
                    </div>
                </template>
                
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menangkap session 'wa_link' dari proses setujui/tolak berkas di Controller
    @if(session('wa_link'))
        Swal.fire({
            title: 'Aksi Berhasil!',
            text: '{{ session('success') }}',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="fab fa-whatsapp mr-1"></i> Beri Tahu via WA',
            cancelButtonText: 'Tutup',
            buttonsStyling: false, 
            customClass: {
                confirmButton: 'px-5 py-2.5 text-white font-bold rounded-xl shadow-md ml-2 transition-all',
                cancelButton: 'px-5 py-2.5 bg-gray-500 text-white font-bold rounded-xl shadow-md hover:bg-gray-600 mr-2 transition-all'
            },
            didRender: () => {
                const confirmBtn = Swal.getConfirmButton();
                if (confirmBtn) {
                    confirmBtn.style.backgroundColor = '#25D366'; 
                }
            },
            reverseButtons: true,
            allowOutsideClick: false,
            target: document.body
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("{!! session('wa_link') !!}", '_blank');
            }
        });
        
    @elseif(session('success'))
        Swal.fire({
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false,
            target: document.body
        });
    @endif
    
    @if(session('error'))
        Swal.fire({
            title: 'Gagal!',
            text: '{{ session('error') }}',
            icon: 'error',
            target: document.body
        });
    @endif
});

function konfirmasiAksi(button, confirmText, confirmColor) {
    const form = button.closest('form');
    if (!form) return;

    Swal.fire({
        title: form.getAttribute('data-title'),
        text: form.getAttribute('data-text'),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal',
        target: document.body,
        customClass: {
            confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-sm shadow-md',
            cancelButton: 'px-5 py-2.5 rounded-xl font-bold text-sm shadow-md'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ 
                title: 'Memproses...', 
                text: 'Mohon tunggu sebentar.', 
                allowOutsideClick: false, 
                target: document.body,
                didOpen: () => { 
                    Swal.showLoading(); 
                }
            });
            form.submit();
        }
    });
}
</script>

<style>
    .swal2-container {
        z-index: 99999 !important;
    }
    .swal2-styled.swal2-confirm {
        background-color: #16a34a !important; 
        color: #ffffff !important;
    }
    .swal2-styled.swal2-confirm:hover {
        background-color: #15803d !important;
    }
    .swal2-styled.swal2-cancel {
        background-color: #6b7280 !important; 
        color: #ffffff !important;
    }
    .swal2-styled.swal2-cancel:hover {
        background-color: #4b5563 !important;
    }
</style>
@endsection