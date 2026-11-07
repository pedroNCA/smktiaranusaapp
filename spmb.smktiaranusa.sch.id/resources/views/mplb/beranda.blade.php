@extends('layouts.public')

@section('title', 'Manajemen Perkantoran dan Layanan Bisnis - SMK Tiara Nusa')

@section('content')
<!-- Hero Section MPLB dengan Foto -->
<section class="relative bg-gray-900 bg-cover bg-center bg-no-repeat bg-blend-multiply" style="background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2070&auto=format&fit=crop');">
    <div class="relative px-4 mx-auto max-w-screen-xl text-center py-28 lg:py-40">
        <span class="inline-block py-1 px-3 rounded-full bg-emerald-800 text-white text-sm font-semibold mb-6 border border-emerald-700">Program Keahlian Unggulan</span>
        <h1 class="mb-4 text-4xl font-extrabold tracking-tight text-white md:text-5xl lg:text-6xl">Manajemen Perkantoran & Layanan Bisnis</h1>
        <p class="mb-8 text-lg font-normal text-gray-300 lg:text-xl sm:px-16 lg:px-48 leading-relaxed">Mencetak tenaga profesional di bidang administrasi, kesekretarisan digital, dan pengelolaan bisnis modern.</p>
        <div class="flex justify-center space-x-4">
            <a href="{{ route('spmb.beranda') }}" class="py-3.5 px-7 text-base font-medium text-white rounded-full bg-emerald-600 hover:bg-emerald-700 transition-all shadow-md">Daftar Jurusan Ini</a>
        </div>
    </div>
</section>

<!-- Seksi Kompetensi -->
<section class="py-20 bg-white">
    <div class="max-w-screen-xl mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Fokus Kompetensi MPLB</h2>
            <p class="text-gray-600">Membekali siswa dengan keahlian manajerial dan penguasaan aplikasi perkantoran terkini.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-8 bg-gray-50 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <h3 class="mb-3 text-xl font-bold text-gray-900">Administrasi Digital</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Pengelolaan dokumen, kearsipan elektronik, dan otomatisasi tata naskah kantor.</p>
            </div>
            <div class="p-8 bg-gray-50 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <h3 class="mb-3 text-xl font-bold text-gray-900">Layanan Bisnis & Humas</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Kompetensi public relations, korespondensi bisnis, dan pelayanan prima (service excellence).</p>
            </div>
            <div class="p-8 bg-gray-50 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <h3 class="mb-3 text-xl font-bold text-gray-900">Manajemen Keuangan</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Pengelolaan kas kecil, pembukuan sederhana, dan administrasi transaksi keuangan perusahaan.</p>
            </div>
        </div>
    </div>
</section>
@endsection