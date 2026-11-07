@extends('layouts.public')

@section('title', 'Portal SPMB - SMK Tiara Nusa')

@section('content')
<!-- Hero Section dengan Responsivitas Optimal di Mobile -->
<section class="relative bg-gray-900 bg-cover bg-center bg-no-repeat bg-blend-multiply" style="background-image: url('{{ asset('foto-sekolah.jpeg') }}');">
    <div class="relative px-4 sm:px-6 lg:px-8 mx-auto max-w-screen-xl text-center py-20 sm:py-28 lg:py-36">
        
        <!-- Badge Gelombang 
        <span class="inline-block py-1 px-3 sm:px-4 rounded-full bg-blue-800 text-white text-xs sm:text-sm font-semibold mb-4 sm:mb-6 border border-blue-700 shadow-sm">
            Gelombang Pertama Telah Dibuka
        </span>-->
        
        <!-- Judul Utama (Ukuran teks disesuaikan agar tidak terpotong di mobile) -->
        <h1 class="mb-4 text-3xl sm:text-5xl lg:text-7xl font-extrabold tracking-tight text-white leading-tight drop-shadow-lg">
            Penerimaan Murid Baru <span class="block sm:inline text-blue-400">2027/2028</span>
        </h1>
        
        <!-- Deskripsi dengan padding yang aman untuk layar kecil -->
        <p class="mb-8 text-sm sm:text-base lg:text-xl font-normal text-gray-300 max-w-2xl mx-auto leading-relaxed px-2">
            Wujudkan masa depan gemilang bersama SMK Tiara Nusa<br> Pilih keahlianmu di bidang <strong class="text-white">TJKT</strong> atau <strong class="text-white">MPLB</strong>
        </p>
        
        <!-- Tombol Aksi (Fleksibel: melebar penuh di HP, berdampingan di tablet/desktop) -->
        <div class="flex flex-col sm:flex-row justify-center items-center gap-3 sm:gap-4 px-4 sm:px-0">
            <a href="/register" class="w-full sm:w-auto group inline-flex justify-center items-center py-3 px-6 text-sm sm:text-base font-medium text-white rounded-full bg-blue-600 hover:bg-blue-700 transition-all duration-300 shadow-md">
                Buat Akun Sekarang
                <svg class="w-4 h-4 ms-2 transition-transform group-hover:translate-x-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                </svg>
            </a>
            <a href="#jurusan" class="w-full sm:w-auto inline-flex justify-center items-center py-3 px-6 text-sm sm:text-base font-medium text-white rounded-full bg-gray-800/80 hover:bg-gray-800 transition-all duration-300 border border-gray-700 shadow-md">
                Lihat Jurusan
            </a>
        </div>
    </div>
</section>

<!-- Menu Card Akses Cepat (Responsif Grid) -->
<section class="py-12 sm:py-16 bg-gray-50 -mt-8 sm:-mt-10 relative z-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-screen-xl mx-auto">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 text-center">
            
            <!-- Card Jadwal -->
            <a href="{{ route('spmb.jadwal') }}" class="group block p-6 sm:p-8 bg-white border border-gray-100 rounded-3xl shadow-sm hover:shadow-xl hover:border-blue-100 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4 sm:mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h5 class="mb-1 sm:mb-2 text-lg sm:text-xl font-bold text-gray-900 group-hover:text-blue-700 transition-colors">Jadwal SPMB</h5>
                <p class="text-xs sm:text-sm text-gray-500">Timeline pendaftaran</p>
            </a>

            <!-- Card Biaya -->
            <a href="{{ route('spmb.biaya') }}" class="group block p-6 sm:p-8 bg-white border border-gray-100 rounded-3xl shadow-sm hover:shadow-xl hover:border-emerald-100 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4 sm:mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h5 class="mb-1 sm:mb-2 text-lg sm:text-xl font-bold text-gray-900 group-hover:text-emerald-700 transition-colors">Info Biaya</h5>
                <p class="text-xs sm:text-sm text-gray-500">Rincian & ketentuan</p>
            </a>

            <!-- Card Alur -->
            <a href="{{ route('spmb.alur') }}" class="group block p-6 sm:p-8 bg-white border border-gray-100 rounded-3xl shadow-sm hover:shadow-xl hover:border-purple-100 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mb-4 sm:mb-6 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <h5 class="mb-1 sm:mb-2 text-lg sm:text-xl font-bold text-gray-900 group-hover:text-purple-700 transition-colors">Alur Daftar</h5>
                <p class="text-xs sm:text-sm text-gray-500">Langkah registrasi</p>
            </a>

            <!-- Card FAQ -->
            <a href="{{ route('spmb.faq') }}" class="group block p-6 sm:p-8 bg-white border border-gray-100 rounded-3xl shadow-sm hover:shadow-xl hover:border-orange-100 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-4 sm:mb-6 group-hover:bg-orange-600 group-hover:text-white transition-all duration-300">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h5 class="mb-1 sm:mb-2 text-lg sm:text-xl font-bold text-gray-900 group-hover:text-orange-700 transition-colors">Bantuan (FAQ)</h5>
                <p class="text-xs sm:text-sm text-gray-500">Tanya jawab umum</p>
            </a>

        </div>
    </div>
</section>

<!-- Seksi Pilihan Jurusan (TJKT & MPLB) -->
<section id="jurusan" class="py-16 sm:py-20 bg-white border-t border-gray-100 px-4 sm:px-6 lg:px-8">
    <div class="max-w-screen-xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <span class="inline-block py-1 px-3 rounded-full bg-blue-50 text-blue-600 text-xs sm:text-sm font-semibold mb-3 border border-blue-100">Kompetensi Keahlian</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">Pilihan Program Keahlian</h2>
            <p class="text-gray-600 text-sm sm:text-base">Pilih jurusan impianmu dan temukan informasi mendalam seputar fasilitas, kurikulum, serta keunggulannya.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 sm:gap-8">
            <!-- Card TJKT -->
            <div class="bg-gray-50 border border-gray-100 rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <span class="inline-block py-1 px-3 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold mb-4">TJKT</span>
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">Teknik Jaringan Komputer & Telekomunikasi</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-6">Mempelajari infrastruktur jaringan komputer skala enterprise, administrasi server, keamanan siber (cyber security), dan teknologi fiber optic.</p>
                </div>
                <div class="pt-6 border-t border-gray-200/60 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Akreditasi A</span>
                    <a href="{{ route('tjkt.beranda') }}" class="inline-flex items-center text-xs sm:text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 py-2 px-4 sm:py-2.5 sm:px-6 rounded-full transition-all shadow-sm">
                        Profil TJKT &rarr;
                    </a>
                </div>
            </div>

            <!-- Card MPLB -->
            <div class="bg-gray-50 border border-gray-100 rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <span class="inline-block py-1 px-3 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">MPLB</span>
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">Manajemen Perkantoran & Layanan Bisnis</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-6">Mempelajari administrasi perkantoran modern, otomatisasi tata naskah digital, kearsipan elektronik, korespondensi, dan layanan bisnis profesional.</p>
                </div>
                <div class="pt-6 border-t border-gray-200/60 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Akreditasi A</span>
                    <a href="{{ route('mplb.beranda') }}" class="inline-flex items-center text-xs sm:text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 py-2 px-4 sm:py-2.5 sm:px-6 rounded-full transition-all shadow-sm">
                        Profil MPLB &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection