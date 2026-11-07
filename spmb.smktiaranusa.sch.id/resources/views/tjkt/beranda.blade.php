@extends('layouts.public')

@section('title', 'Teknik Jaringan Komputer dan Telekomunikasi - SMK Tiara Nusa')

@section('content')
<!-- Hero Section TJKT dengan Foto -->
<section class="relative bg-gray-900 bg-cover bg-center bg-no-repeat bg-blend-multiply" style="background-image: url('https://images.unsplash.com/photo-1544717305-2782549b5136?q=80&w=2070&auto=format&fit=crop');">
    <div class="relative px-4 mx-auto max-w-screen-xl text-center py-28 lg:py-40">
        <span class="inline-block py-1 px-3 rounded-full bg-blue-800 text-white text-sm font-semibold mb-6 border border-blue-700">Program Keahlian Unggulan</span>
        <h1 class="mb-4 text-4xl font-extrabold tracking-tight text-white md:text-5xl lg:text-6xl">Teknik Jaringan Komputer & Telekomunikasi</h1>
        <p class="mb-8 text-lg font-normal text-gray-300 lg:text-xl sm:px-16 lg:px-48 leading-relaxed">Membentuk tenaga ahli profesional di bidang Infrastruktur Jaringan, Server, Cyber Security, dan Teknologi Fiber Optic.</p>
        <div class="flex justify-center space-x-4">
            <a href="{{ route('spmb.beranda') }}" class="py-3.5 px-7 text-base font-medium text-white rounded-full bg-blue-600 hover:bg-blue-700 transition-all shadow-md">Daftar Jurusan Ini</a>
        </div>
    </div>
</section>

<!-- Seksi Kompetensi -->
<section class="py-20 bg-white">
    <div class="max-w-screen-xl mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Fokus Kompetensi TJKT</h2>
            <p class="text-gray-600">Kurikulum disesuaikan dengan kebutuhan industri teknologi modern dan sertifikasi internasional.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-8 bg-gray-50 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <h3 class="mb-3 text-xl font-bold text-gray-900">Jaringan & Keamanan</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Perancangan topologi jaringan skala enterprise dan implementasi cyber security standar industri.</p>
            </div>
            <div class="p-8 bg-gray-50 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <h3 class="mb-3 text-xl font-bold text-gray-900">Administrasi Server</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Manajemen sistem operasi server Windows dan Linux, serta konfigurasi cloud computing.</p>
            </div>
            <div class="p-8 bg-gray-50 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <h3 class="mb-3 text-xl font-bold text-gray-900">Fiber Optic</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Penyambungan, pengujian, dan pemeliharaan infrastruktur kabel serat optik (FTTH).</p>
            </div>
        </div>
    </div>
</section>
@endsection