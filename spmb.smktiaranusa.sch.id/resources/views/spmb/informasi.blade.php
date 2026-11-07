@extends('layouts.public')

@section('title', 'Informasi SPMB - SMK Tiara Nusa')

@section('content')
<div class="max-w-screen-xl mx-auto py-16 px-4">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-block py-1 px-3 rounded-full bg-blue-50 text-blue-600 text-sm font-semibold mb-4 border border-blue-100">Tentang Seleksi</span>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl mb-6">Informasi Umum Penerimaan Siswa Baru</h1>
            <div class="text-gray-600 space-y-4 leading-relaxed">
                {!! $information->content ?? '<p>Informasi umum pendaftaran belum diatur oleh admin.</p>' !!}
            </div>
            <div class="mt-8">
                <a href="/register" class="inline-flex justify-center items-center py-3.5 px-7 text-base font-medium text-white rounded-full bg-blue-600 hover:bg-blue-700 transition-all duration-300 shadow-md">
                    Daftar Akun Sekarang
                </a>
            </div>
        </div>
        <div class="relative">
            <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=1000&auto=format&fit=crop" alt="Suasana Sekolah" class="rounded-3xl shadow-xl object-cover w-full h-[400px]">
        </div>
    </div>
</div>
@endsection