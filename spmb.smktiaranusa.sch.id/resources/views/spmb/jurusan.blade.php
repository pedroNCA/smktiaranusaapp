@extends('layouts.public')

@section('title', 'Pilihan Jurusan - SPMB SMK Tiara Nusa')

@section('content')
<div class="max-w-screen-xl mx-auto p-4 py-16">
    <!-- Header Section -->
    <div class="text-center mb-16 max-w-2xl mx-auto">
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight md:text-5xl">Pilihan Jurusan</h1>
        <p class="mt-4 text-lg text-gray-500">Temukan program keahlian yang sesuai dengan minat dan bakat Anda untuk masa depan yang gemilang bersama SMK Tiara Nusa.</p>
    </div>

    <!-- Cards Grid -->
    <div class="grid md:grid-cols-2 gap-8 lg:gap-12 px-4 md:px-0">
        @forelse($majors as $major)
            @php 
                $isTJKT = strtoupper($major->code) === 'TJKT';
                $themeColor = $isTJKT ? 'blue' : 'emerald';
            @endphp

            <div class="relative bg-white p-8 md:p-10 rounded-3xl shadow-sm border border-gray-200 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden">
                <!-- Dekorasi Garis Atas -->
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r {{ $isTJKT ? 'from-blue-600 to-cyan-400' : 'from-emerald-600 to-teal-400' }}"></div>
                
                <!-- Dekorasi Latar Belakang Lingkaran (Halus) -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-{{ $themeColor }}-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity"></div>

                <div class="relative z-10">
                    <!-- Ikon Dinamis berdasarkan Jurusan -->
                    <div class="w-16 h-16 bg-{{ $themeColor }}-50 text-{{ $themeColor }}-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-{{ $themeColor }}-100 group-hover:scale-110 transition-transform duration-300">
                        @if($isTJKT)
                            <!-- Ikon Server/Jaringan untuk TJKT -->
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        @else
                            <!-- Ikon Briefcase/Kantor untuk MPLB -->
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        @endif
                    </div>
                    
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">{{ $major->code }}</h2>
                    <p class="text-xl text-{{ $themeColor }}-700 font-bold mb-4">{{ $major->name }}</p>
                    
                    @if($major->description)
                        <p class="text-gray-600 mb-8 leading-relaxed">{{ $major->description }}</p>
                    @else
                        <!-- Teks placeholder jika deskripsi kosong agar layout tetap seimbang -->
                        <p class="text-gray-500 mb-8 leading-relaxed">Pelajari lebih lanjut mengenai kompetensi, fasilitas, dan peluang karir yang ditawarkan oleh program keahlian ini.</p>
                    @endif
                    
                    @php $routeCode = strtolower($major->code); @endphp
                    @if(Route::has($routeCode . '.beranda'))
                        <a href="{{ route($routeCode . '.beranda') }}" class="inline-flex items-center justify-center w-full sm:w-auto px-6 py-3 text-base font-semibold text-white bg-{{ $themeColor }}-600 rounded-xl hover:bg-{{ $themeColor }}-700 focus:ring-4 focus:ring-{{ $themeColor }}-300 transition-all shadow-md hover:shadow-lg group/btn">
                            Lihat Profil {{ $major->code }}
                            <svg class="w-5 h-5 ml-2 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-2 flex flex-col items-center justify-center py-16 px-4 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-300">
                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-lg font-medium text-gray-900">Data jurusan belum tersedia.</p>
                <p class="text-gray-500 mt-1">Silakan tambahkan data jurusan melalui halaman admin.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection