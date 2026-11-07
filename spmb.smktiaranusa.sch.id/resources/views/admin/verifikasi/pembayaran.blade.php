@extends('layouts.admin')
@section('title', 'Verifikasi Pembayaran - SPMB Admin')
@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Verifikasi Pembayaran</h1>
    <p class="text-gray-500 text-sm mt-1">Daftar siswa yang menunggu verifikasi bukti pembayaran pendaftaran.</p>
</div>

{{-- Panel Filter Seragam --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-5 mb-6">
    <form method="GET" action="{{ route('admin.verifikasi.pembayaran') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="w-full">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pencarian</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari SPMB / NIK..." class="w-full text-sm border-gray-300 rounded-xl shadow-sm pl-10 pr-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="w-full">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Status Pembayaran</label>
            <select name="status" class="w-full text-sm border-gray-300 rounded-xl shadow-sm px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Status</option>
                <option value="PAYMENT_REVIEW" {{ request('status') == 'PAYMENT_REVIEW' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="VERIFIED" {{ request('status') == 'VERIFIED' ? 'selected' : '' }}>Sudah Verifikasi</option>
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
            <button type="submit" class="flex-1 py-2.5 bg-gray-800 text-white text-sm font-bold rounded-xl hover:bg-gray-900 shadow-sm transition">Terapkan</button>
            <a href="{{ route('admin.verifikasi.pembayaran') }}" class="px-4 py-2.5 bg-gray-100 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-200 transition" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
        </div>
    </form>
</div>

{{-- Tabel Utama --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto w-full">
        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-4 font-bold">ID SPMB & Nama</th>
                    <th class="px-5 py-4 font-bold">Jurusan</th>
                    <th class="px-5 py-4 font-bold text-center">Status</th>
                    <th class="px-5 py-4 font-bold text-center">Bukti Bayar</th>
                    <th class="px-5 py-4 font-bold text-center">Aksi Verifikasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($registrations as $item)
                <tr class="hover:bg-blue-50/50 transition-colors {{ $item->status == 'PAYMENT_REVIEW' ? 'bg-orange-50/20' : '' }}">
                    <td class="px-5 py-4">
                        <div class="font-bold text-gray-900">{{ $item->spmb_id }}</div>
                        <div class="text-xs font-semibold text-gray-500 mt-0.5">{{ $item->student->user->name ?? ($item->user->name ?? '-') }}</div>
                    </td>
                    <td class="px-5 py-4 font-medium">{{ $item->major ?? '-' }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($item->status == 'VERIFIED')
                            <span class="inline-flex items-center bg-green-100 text-green-700 border border-green-200 text-[11px] font-bold px-2.5 py-1 rounded-full"><i class="fas fa-check-circle mr-1"></i> Terverifikasi</span>
                        @else
                            <span class="inline-flex items-center bg-orange-100 text-orange-700 border border-orange-200 text-[11px] font-bold px-2.5 py-1 rounded-full animate-pulse"><i class="fas fa-clock mr-1"></i> Review</span>
                        @endif
                    </td>
                    
                    <td class="px-5 py-4 text-center">
                        @php $payment = $item->payments->first(); @endphp
                        @if($item->payment_proof || ($payment && $payment->proof_path))
                            <div class="flex items-center justify-center gap-2">
                                {{-- Tombol Cek Bukti --}}
                                <a href="{{ route('admin.pendaftaran.lihatBukti', $item->id) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-600 hover:text-white transition-colors" title="Lihat Bukti">
                                    <i class="fas fa-search-dollar mr-1"></i> Cek Bukti
                                </a>
                                
                                {{-- Tombol Hapus Bukti --}}
                                <form action="{{ route('admin.verifikasi.pembayaran.destroyBukti', $item->id) }}" method="POST" data-title="Hapus Bukti Fisik?" data-text="File bukti bayar di Google Drive akan dihapus. Siswa harus upload ulang.">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="konfirmasiAksi(this, 'Ya, Hapus', '#d33')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-600 hover:text-white transition-colors" title="Hapus Bukti Salah/Buram">
                                        <i class="fas fa-trash mr-1"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Tidak ada file</span>
                        @endif
                    </td>

                    <td class="px-5 py-4 text-center">
                        @if($item->status == 'VERIFIED')
                            <div class="text-[11px] font-bold text-green-600 bg-green-50 py-1.5 px-3 rounded-lg border border-green-200 inline-block cursor-default">
                                <i class="fas fa-check-double mr-1"></i> Sudah Disetujui
                            </div>
                        @else
                            <div class="flex items-center justify-center gap-2">
                                <form action="{{ route('admin.verifikasi.pembayaran.update', $item->id) }}" method="POST" data-title="Setujui Pembayaran?" data-text="Pembayaran pendaftar ini akan disahkan.">
    @csrf @method('PUT')
    <input type="hidden" name="status" value="VERIFIED">
    <button type="button" onclick="konfirmasiAksi(this, 'Ya, Setujui', '#16a34a')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 transition shadow-sm">
        <i class="fas fa-check mr-1"></i> Ya
    </button>
</form>
                                <!--<form action="{{ route('admin.pendaftar.updateStatus', $item->id) }}" method="POST" data-title="Tolak Pembayaran?" data-text="Siswa akan diminta mengunggah ulang bukti bayar.">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="WAITING_PAYMENT">
                                    <button type="button" onclick="konfirmasiAksi(this, 'Tolak & Perbaiki', '#d33')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition shadow-sm">
                                        <i class="fas fa-times mr-1"></i> Tolak
                                    </button>
                                </form>-->
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-gray-400 font-medium">Tidak ada data pembayaran yang sesuai filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($registrations->hasPages())
    <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-xs font-medium text-gray-500 text-center md:text-left">
            Menampilkan <strong>{{ $registrations->firstItem() ?? 0 }}</strong> sampai <strong>{{ $registrations->lastItem() ?? 0 }}</strong> dari <strong>{{ $registrations->total() }}</strong> transaksi
        </div>
        <div class="w-full md:w-auto overflow-x-auto">{{ $registrations->links() }}</div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Cek jika ada Session yang membawa Link WhatsApp (Berlaku untuk Setuju, Tolak, & Hapus)
    @if(session('wa_link'))
        Swal.fire({
            title: 'Aksi Berhasil!',
            text: '{{ session('success') }}',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="fab fa-whatsapp mr-1"></i> Beri Tahu via WA',
            cancelButtonText: 'Tutup',
            buttonsStyling: false, // Mematikan styling default agar warna konsisten
            customClass: {
                confirmButton: 'px-5 py-2.5 text-white font-bold rounded-xl shadow-md ml-2 transition-all',
                cancelButton: 'px-5 py-2.5 bg-gray-500 text-white font-bold rounded-xl shadow-md hover:bg-gray-600 mr-2 transition-all'
            },
            didRender: () => {
                // Memaksa tombol konfirmasi langsung solid berwarna hijau WhatsApp sejak pertama kali muncul
                const confirmBtn = Swal.getConfirmButton();
                if (confirmBtn) {
                    confirmBtn.style.backgroundColor = '#25D366'; 
                }
            },
            reverseButtons: true,
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Membuka tab baru yang langsung menuju ke wa.me dengan pesan otomatis
                window.open("{!! session('wa_link') !!}", '_blank');
            }
        });
        
    @elseif(session('success'))
        Swal.fire({
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
    @endif
    
    @if(session('error'))
        Swal.fire({
            title: 'Gagal!',
            text: '{{ session('error') }}',
            icon: 'error'
        });
    @endif
});

// Fungsi konfirmasi umum untuk tombol Aksi (Setuju, Tolak, Hapus)
function konfirmasiAksi(button, confirmText, confirmColor) {
    const form = button.closest('form');
    Swal.fire({
        title: form.getAttribute('data-title'),
        text: form.getAttribute('data-text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal',
        customClass: {
            confirmButton: 'px-4 py-2 text-white font-bold rounded-xl shadow-sm ml-2',
            cancelButton: 'px-4 py-2 bg-gray-500 text-white font-bold rounded-xl shadow-sm hover:bg-gray-600 mr-2'
        },
        didRender: () => {
            const confirmBtn = Swal.getConfirmButton();
            if (confirmBtn) {
                confirmBtn.style.backgroundColor = confirmColor;
            }
        },
        buttonsStyling: false,
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ 
                title: 'Memproses...', 
                text: 'Sedang memperbarui data...', 
                allowOutsideClick: false, 
                didOpen: () => { Swal.showLoading(); } 
            });
            HTMLFormElement.prototype.submit.call(form);
        }
    });
}
</script>

@endsection