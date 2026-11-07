@extends('layouts.admin')
@section('title', 'Audit Log Sistem - SPMB')
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Log Aktivitas Sistem</h2>
    <p class="text-gray-600">Rekam jejak aktivitas pengguna di dalam panel admin.</p>
</div>

<div class="relative overflow-x-auto shadow-md sm:rounded-lg bg-white">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3">Waktu</th>
                <th scope="col" class="px-6 py-3">Pengguna</th>
                <th scope="col" class="px-6 py-3">Aksi</th>
                <th scope="col" class="px-6 py-3">IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs ?? [] as $log)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-6 py-4">{{ $log->user->name ?? 'Sistem' }}</td>
                <td class="px-6 py-4">{{ $log->action }}</td>
                <td class="px-6 py-4">{{ $log->ip_address }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center">Belum ada log aktivitas tercatat.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <!-- Tambahkan Paginasi di sini jika diperlukan -->
</div>
@endsection