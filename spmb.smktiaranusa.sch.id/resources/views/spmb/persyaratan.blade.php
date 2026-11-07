@extends('layouts.public')

@section('title', 'Persyaratan SPMB - SMK Tiara Nusa')

@section('content')

@php
    // Memisahkan data berdasarkan tipe jalur
    $reqUmum = $requirements->where('type', 'Umum');
    $reqReguler = $requirements->where('type', 'Reguler');
    $reqBeasiswa = $requirements->where('type', 'Beasiswa');
@endphp

<div class="max-w-screen-xl mx-auto py-16 px-4">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">Persyaratan Pendaftaran</h1>
        <p class="mt-4 text-lg text-gray-600">Pastikan kelengkapan dokumen berikut telah disiapkan sebelum melakukan pendaftaran.</p>
    </div>

    <!-- 1. Tabel Persyaratan Umum (Tampil Penuh di Atas) -->
    @if($reqUmum->count() > 0)
    <div class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-gray-500 pl-3">Persyaratan Umum (Wajib)</h2>
        <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-sm">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-100 text-gray-900 uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-4 w-1/3 border-b border-gray-300">Persyaratan</th>
                        <th scope="col" class="px-6 py-4 w-2/3 border-b border-gray-300">Deskripsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($reqUmum as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $item->title }}</td>
                        <td class="px-6 py-4">{{ $item->description }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- 2. Grid Dua Kolom: Reguler (Kiri) & Beasiswa (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Kolom Jalur Reguler -->
        <div>
            <h2 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-gray-500 pl-3">Jalur Reguler</h2>
            <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-sm">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="bg-gray-100 text-gray-900 uppercase">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-2/5 border-b border-gray-300">Persyaratan</th>
                            <th scope="col" class="px-4 py-3 w-3/5 border-b border-gray-300">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($reqReguler as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 font-semibold text-gray-900">{{ $item->title }}</td>
                            <td class="px-4 py-4">{{ $item->description }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">Belum ada persyaratan khusus jalur reguler.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kolom Jalur Beasiswa -->
        <div>
            <h2 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-gray-500 pl-3">Jalur Beasiswa</h2>
            <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-sm">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="bg-gray-100 text-gray-900 uppercase">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-2/5 border-b border-gray-300">Persyaratan</th>
                            <th scope="col" class="px-4 py-3 w-3/5 border-b border-gray-300">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($reqBeasiswa as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 font-semibold text-gray-900">{{ $item->title }}</td>
                            <td class="px-4 py-4">{{ $item->description }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">Belum ada persyaratan khusus jalur beasiswa.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Tampilan Kosong Total (Jika database kosong sama sekali) -->
    @if($requirements->isEmpty())
    <div class="text-center py-16 bg-white border border-gray-200 rounded-xl shadow-sm">
        <p class="text-gray-500">Data persyaratan belum diatur oleh administrator.</p>
    </div>
    @endif
</div>
@endsection