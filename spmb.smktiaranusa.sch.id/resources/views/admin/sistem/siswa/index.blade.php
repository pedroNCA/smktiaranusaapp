@extends('layouts.admin')
@section('title', 'Data Akun Siswa - SPMB')
@section('content')

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Akun Siswa</h1>
        <p class="text-gray-500 text-sm mt-1">Manajemen seluruh akun pengguna terdaftar dengan akses sebagai siswa.</p>
    </div>
</div>

@if(session('success'))
<div class="p-4 mb-4 text-sm text-green-800 rounded-xl bg-green-50 border border-green-200 flex items-center shadow-sm">
    <i class="fas fa-check-circle mr-2 text-green-600"></i> {{ session('success') }}
</div>
@endif

{{-- Panel Filter & Pencarian --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-5 mb-6">
    <form action="{{ route('admin.akun-siswa.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-1/3">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pencarian Akun</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau no WA..." class="w-full text-sm border-gray-300 rounded-xl shadow-sm pl-10 pr-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-sm transition">
                <i class="fas fa-search mr-1.5"></i> Cari
            </button>
            <a href="{{ route('admin.akun-siswa.index') }}" class="inline-flex items-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-200 transition shadow-sm" title="Reset Filter">
                <i class="fas fa-sync-alt mr-1.5"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- Tabel Utama --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto w-full">
        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-4 font-bold">Nama Lengkap</th>
                    <th class="px-5 py-4 font-bold">Email</th>
                    <th class="px-5 py-4 font-bold text-center">No. WhatsApp</th>
                    <th class="px-5 py-4 font-bold text-center">Terdaftar Pada</th>
                    <th class="px-5 py-4 font-bold text-center">Aksi Manajemen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    @php
                        $wa = $user->whatsapp;
                        if($wa && str_starts_with($wa, '08')) {
                            $waLink = '628' . substr($wa, 2);
                        } elseif($wa && str_starts_with($wa, '+62')) {
                            $waLink = '62' . substr($wa, 3);
                        } else {
                            $waLink = preg_replace('/[^0-9]/', '', (string)$wa);
                        }
                    @endphp
                <tr class="hover:bg-blue-50/50 transition-colors" x-data="{ showPwdModal: false }">
                    <td class="px-5 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                    <td class="px-5 py-4 font-medium">{{ $user->email }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($user->whatsapp)
                            <a href="https://wa.me/{{ $waLink }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold text-green-700 bg-green-100 rounded-lg hover:bg-green-200 transition-colors">
                                <i class="fab fa-whatsapp mr-1"></i> {{ $user->whatsapp }}
                            </a>
                        @else
                            <span class="text-xs text-gray-400 italic">Tidak ada</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center text-gray-500 font-medium">{{ $user->created_at->format('d M Y') }}</td>
                    
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button @click="showPwdModal = true" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition">
                                <i class="fas fa-key mr-1.5"></i> Password
                            </button>
                            
                            <form action="{{ route('admin.akun-siswa.destroy', $user->id) }}" method="POST" data-title="Hapus Akun Siswa?" data-text="Semua data login untuk siswa ini akan dihapus permanen.">
                                @csrf @method('DELETE')
                                <button type="button" onclick="konfirmasiAksi(this, 'Ya, Hapus', '#d33')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition" title="Hapus Akun">
                                    <i class="fas fa-trash-alt mr-1.5"></i> Hapus
                                </button>
                            </form>
                        </div>

                        <!-- Modal Ganti Password (Alpine) -->
                        <div x-show="showPwdModal" style="display: none;" class="fixed inset-0 z-[100] flex justify-center items-center bg-gray-900 bg-opacity-50 p-4" x-cloak>
                            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 relative" @click.away="showPwdModal = false">
                                <button @click="showPwdModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
                                <h3 class="text-lg font-bold text-gray-900 mb-1 border-b pb-3 text-left">Ganti Password Siswa</h3>
                                <p class="text-xs text-gray-500 mb-4 text-left">Akun: <span class="font-bold text-blue-600">{{ $user->name }}</span></p>
                                
                                <form action="{{ route('admin.akun-siswa.updatePassword', $user->id) }}" method="POST" class="text-left">
                                    @csrf @method('PUT')
                                    <div class="mb-5">
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Password Baru (Min. 6 Karakter)</label>
                                        <input type="text" name="password" required minlength="6" class="w-full text-sm border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2.5">
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="showPwdModal = false" class="px-4 py-2 bg-gray-100 text-gray-600 text-xs font-bold rounded-xl hover:bg-gray-200 transition">Batal</button>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition">Simpan Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-gray-400 font-medium">Data akun siswa tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-xs font-medium text-gray-500 text-center md:text-left">Menampilkan <strong>{{ $users->firstItem() ?? 0 }}</strong> - <strong>{{ $users->lastItem() ?? 0 }}</strong> dari <strong>{{ $users->total() }}</strong> akun</div>
        <div class="w-full md:w-auto overflow-x-auto">{{ $users->links() }}</div>
    </div>
    @endif
</div>

{{-- Script SweetAlert2 Konfirmasi Hapus --}}
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
            Swal.fire({ title: 'Memproses...', text: 'Mohon tunggu.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            form.submit();
        }
    });
}
</script>
@endsection