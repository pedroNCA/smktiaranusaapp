@extends('layouts.admin')
@section('title', 'Belum Isi Formulir - SPMB')
@section('content')

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Siswa Belum Mengisi Formulir</h1>
        <p class="text-gray-500 text-sm mt-1">Daftar akun yang sudah mendaftar namun belum melengkapi data pendaftaran SPMB.</p>
    </div>
</div>

{{-- Panel Filter & Pencarian --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-5 mb-6">
    <form action="{{ route('admin.akun-siswa.pending') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-1/3">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pencarian Siswa</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau no WA..." class="w-full text-sm border-gray-300 rounded-xl shadow-sm pl-10 pr-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-sm transition">Cari</button>
            <a href="{{ route('admin.akun-siswa.pending') }}" class="px-4 py-2.5 bg-gray-100 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-200 transition" title="Reset Filter"><i class="fas fa-sync-alt">Reset</i></a>
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
                    <th class="px-5 py-4 font-bold text-center">Aksi Reminder</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    @php
                        // Logika format No WA: 08 -> 628
                        $wa = $user->whatsapp;
                        if($wa && str_starts_with($wa, '08')) {
                            $waLink = '628' . substr($wa, 2);
                        } else {
                            $waLink = preg_replace('/[^0-9]/', '', (string)$wa);
                        }
                        
                        $pesan = urlencode("Halo {$user->name}, kami dari panitia SPMB. Kami melihat Anda sudah membuat akun namun belum mengisi formulir pendaftaran. Silakan segera login ke sistem untuk melengkapi pendaftaran Anda. Terima kasih.");
                    @endphp
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-5 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                    <td class="px-5 py-4 font-medium">{{ $user->email }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($user->whatsapp)
                            <span class="text-sm font-semibold text-gray-700">{{ $user->whatsapp }}</span>
                        @else
                            <span class="text-xs text-gray-400 italic">Tidak ada</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center">
                        @if($user->whatsapp)
                            <a href="https://wa.me/{{ $waLink }}?text={{ $pesan }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 shadow-sm transition">
                                <i class="fab fa-whatsapp mr-1.5 text-sm"></i> Kirim WA Pengingat
                            </a>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                                <i class="fas fa-times-circle mr-1.5"></i> Tidak Bisa Dikirim
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center text-gray-400 font-medium">Semua akun siswa sudah mengisi formulir pendaftaran.</td>
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
@endsection