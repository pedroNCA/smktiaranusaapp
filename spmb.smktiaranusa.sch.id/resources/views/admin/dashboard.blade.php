@extends('layouts.admin')

@section('title', 'Dashboard Admin - SPMB')

@section('content')

@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;

    // 1. Ambil Gelombang yang aktif secara OTOMATIS berdasarkan tanggal hari ini
    $today = Carbon::now()->toDateString();
    $activePeriod = DB::table('registration_periods')
        ->where('start_date', '<=', $today)
        ->where('end_date', '>=', $today)
        ->first();
        
    $periodId = $activePeriod ? $activePeriod->id : null;
    $periodName = $activePeriod ? $activePeriod->name : 'Tidak Ada Gelombang Aktif Saat Ini';
    
    // 2. Kalkulasi Statistik Berdasarkan Gelombang Aktif
    $totalPendaftar = 0;
    $sudahBayar = 0;
    $antrianVerifikasi = 0;
    $berkasLengkap = 0;
    $recentRegistrations = [];

    if ($periodId) {
        $totalPendaftar = DB::table('registrations')
            ->where('period_id', $periodId)
            ->where('status', '!=', 'DRAFT')
            ->count();

        $sudahBayar = DB::table('registrations')
            ->where('period_id', $periodId)
            ->whereIn('status', ['PAYMENT_REVIEW', 'PAYMENT_APPROVED', 'WAITING_DOCUMENT', 'DOCUMENT_REVIEW', 'VERIFIED', 'ANNOUNCED', 'ACCEPTED'])
            ->count();

        $antrianVerifikasi = DB::table('registrations')
            ->where('period_id', $periodId)
            ->whereIn('status', ['PAYMENT_REVIEW', 'DOCUMENT_REVIEW'])
            ->count();

        $berkasLengkap = DB::table('registrations')
            ->where('period_id', $periodId)
            ->whereIn('status', ['VERIFIED', 'ANNOUNCED', 'ACCEPTED'])
            ->count();

        // PERBAIKAN QUERY
        $recentRegistrations = DB::table('registrations')
            ->join('students', 'registrations.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('registrations.spmb_id', 'registrations.status', 'users.name as student_name', 'registrations.major as major_code')
            ->where('registrations.period_id', $periodId)
            ->where('registrations.status', '!=', 'DRAFT')
            ->orderBy('registrations.created_at', 'desc')
            ->limit(5)
            ->get();
    }
@endphp

<!-- Header Dashboard -->
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Statistik</h1>
        <p class="text-gray-500 text-sm mt-1 flex items-center gap-2">
            Status Gelombang Saat Ini: 
            <span class="{{ $activePeriod ? 'bg-green-100 text-green-800 border-green-400' : 'bg-red-100 text-red-800 border-red-400' }} text-xs font-bold px-2.5 py-1 rounded-lg border shadow-sm">
                {{ $periodName }}
            </span>
        </p>
    </div>
    
    <!-- Tombol Pengaturan diarahkan ke Gelombang Pendaftaran -->
    <a href="{{ route('registration-periods.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-xl shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        Atur Gelombang
    </a>
</div>

<!-- Widget KPI -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <!-- Widget Total Pendaftar -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Pendaftar</p>
                <h3 class="text-3xl font-extrabold text-gray-900 mt-1">{{ number_format($totalPendaftar) }}</h3>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Widget Sudah Bayar -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-sm font-medium text-gray-500">Sudah Bayar Formulir</p>
                <h3 class="text-3xl font-extrabold text-gray-900 mt-1">{{ number_format($sudahBayar) }}</h3>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Widget Antrian Verifikasi -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-sm font-medium text-gray-500">Antrian Verifikasi</p>
                <h3 class="text-3xl font-extrabold text-gray-900 mt-1">{{ number_format($antrianVerifikasi) }}</h3>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <!--<a href="#" class="mt-4 block text-sm font-medium text-amber-600 hover:underline relative z-10">Proses antrian sekarang &rarr;</a>-->
    </div>

    <!-- Widget Diterima -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-sm font-medium text-gray-500">Berkas Lengkap</p>
                <h3 class="text-3xl font-extrabold text-gray-900 mt-1">{{ number_format($berkasLengkap) }}</h3>
            </div>
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-400 relative z-10">Siap diumumkan</p>
    </div>
</div>

<!-- Tabel Ringkasan Pendaftar Terbaru -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="font-bold text-gray-900">Pendaftar Terbaru</h3>
        <a href="{{ route('admin.pendaftar.index') }}" class="text-sm font-medium text-blue-600 hover:underline">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">ID SPMB</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Nama Lengkap</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Pilihan Jurusan</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentRegistrations as $reg)
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $reg->spmb_id }}</td>
                        <td class="px-6 py-4">{{ $reg->student_name }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-1 rounded border border-gray-300">
                                {{ $reg->major_code ?? 'Belum Pilih' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if(in_array($reg->status, ['WAITING_PAYMENT', 'WAITING_DOCUMENT']))
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-1 rounded border border-red-400">Menunggu Tindakan</span>
                            @elseif(in_array($reg->status, ['PAYMENT_REVIEW', 'DOCUMENT_REVIEW']))
                                <span class="bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-1 rounded border border-amber-400">Perlu Direview</span>
                            @elseif(in_array($reg->status, ['VERIFIED', 'ACCEPTED']))
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-1 rounded border border-green-400">Selesai</span>
                            @else
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded border border-blue-400">{{ str_replace('_', ' ', $reg->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Belum ada data pendaftar pada gelombang ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection