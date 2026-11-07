@extends('layouts.public')

@section('title', 'Jadwal SPMB - SMK Tiara Nusa')

@section('content')
<div class="max-w-screen-lg mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-900 mb-4 text-center">Jadwal SPMB Tahun Ajaran 2027/2028</h1>
    <p class="text-center text-gray-600 mb-8">Pastikan Anda mengikuti jadwal yang telah ditetapkan agar tidak tertinggal tahapan seleksi.</p>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Gelombang / Kegiatan</th>
                    <th scope="col" class="px-6 py-3">Tanggal Mulai</th>
                    <th scope="col" class="px-6 py-3">Tanggal Berakhir</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $item)
                <tr class="bg-white border-b hover:bg-gray-50">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $item->title }}</th>
                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($item->start_date)->translatedFormat('d F Y') }}</td>
<td class="px-6 py-4">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->translatedFormat('d F Y') : '-' }}</td>
                    <td class="px-6 py-4">
                        @if($item->status == 'Dibuka')
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded border border-green-400">Dibuka</span>
                        @elseif($item->status == 'Selesai')
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded border border-red-400">Selesai</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded border border-gray-400">Menunggu</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="bg-white border-b">
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada jadwal yang diatur oleh admin.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection