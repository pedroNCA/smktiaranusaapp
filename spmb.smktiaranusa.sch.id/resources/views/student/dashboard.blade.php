@extends('layouts.student')
@section('title', 'Dashboard Siswa')
@section('content')

@php
    $status = $registration ? $registration->status : 'DRAFT';
    $step = 1;
    // Jika formulir sudah dikirim (SUBMITTED) atau masuk antrean bayar
    if (in_array($status, ['SUBMITTED', 'WAITING_PAYMENT', 'PAYMENT_REVIEW'])) $step = 2;
    // Jika pembayaran sudah diverifikasi dan masuk tahap review dokumen
    if (in_array($status, ['VERIFIED', 'DOCUMENT_REVIEW'])) $step = 3; 
    // Jika semua proses selesai
    if (in_array($status, ['ACCEPTED', 'ANNOUNCED'])) $step = 4;
@endphp

<!-- Sambutan -->
<div class="mb-6 md:mb-10 bg-gradient-to-r from-blue-700 to-indigo-800 rounded-2xl p-5 md:p-8 text-white card-3d relative overflow-hidden">
    <div class="absolute -right-20 -top-20 w-48 h-48 md:w-64 md:h-64 bg-white opacity-5 rounded-full blur-2xl md:blur-3xl"></div>
    <div class="absolute -right-10 top-10 w-32 h-32 md:w-40 md:h-40 bg-white opacity-10 rounded-full blur-xl md:blur-2xl"></div>

    <h2 class="text-xl md:text-3xl font-black mb-2 md:mb-3 tracking-tight break-words text-center">Selamat Datang,<br class="md:hidden"> {{ Auth::user()->name }}</h2>
    <p class="text-blue-100 text-xs md:text-lg max-w-2xl font-light text-center mx-auto leading-relaxed">
        Selesaikan tahapan pendaftaran di bawah ini untuk menjadi bagian dari SMK Tiara Nusa.
        Ikuti petunjuk dengan teliti dan pastikan semua data yang Anda masukkan benar. Jika ada pertanyaan, silakan hubungi panitia melalui kontak resmi kami
    </p>
</div>

<!-- Progress Tracker Pendaftaran -->
<div class="mb-10 md:mb-12">
    <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-6">Progres Pendaftaran</h3>
    
    <div class="relative">
        <!-- Garis Penghubung (Diberi -z-10 agar berada di paling belakang dan tidak menutupi dropdown) -->
        <div class="absolute left-[12%] right-[12%] top-[14px] md:top-[24px] transform -translate-y-1/2 h-1 md:h-1.5 bg-gray-200 rounded-full -z-10"></div>
        <div class="absolute left-[12%] top-[14px] md:top-[24px] transform -translate-y-1/2 h-1 md:h-1.5 bg-blue-600 rounded-full transition-all duration-1000 -z-10" style="width: {{ ($step - 1) * 33.33 }}%;"></div>

        <!-- Container Steps (Dihilangkan class relative agar tidak memblokir dropdown profil dari header) -->
        <div class="flex justify-between items-start">
            
            <!-- Step 1: Formulir -->
            <div class="flex flex-col items-center w-1/4">
                <div class="w-7 h-7 sm:w-8 sm:h-8 md:w-12 md:h-12 rounded-full border-2 md:border-4 {{ $step >= 1 ? 'bg-blue-600 border-blue-200 text-white shadow-md' : 'bg-gray-100 border-white text-gray-400' }} flex items-center justify-center font-bold text-[10px] md:text-xl transition-all">
                    @if($step > 1) ✓ @else 1 @endif
                </div>
                <span class="mt-2 md:mt-3 text-[9px] sm:text-[10px] md:text-sm font-bold text-center leading-tight {{ $step >= 1 ? 'text-blue-700' : 'text-gray-500' }}">Isi<br class="md:hidden"> Formulir</span>
            </div>

            <!-- Step 2: Pembayaran -->
            <div class="flex flex-col items-center w-1/4">
                <div class="w-7 h-7 sm:w-8 sm:h-8 md:w-12 md:h-12 rounded-full border-2 md:border-4 {{ $step >= 2 ? 'bg-blue-600 border-blue-200 text-white shadow-md' : 'bg-gray-100 border-white text-gray-400' }} flex items-center justify-center font-bold text-[10px] md:text-xl transition-all">
                    @if($step > 2) ✓ @else 2 @endif
                </div>
                <span class="mt-2 md:mt-3 text-[9px] sm:text-[10px] md:text-sm font-bold text-center leading-tight {{ $step >= 2 ? 'text-blue-700' : 'text-gray-500' }}">Bayar</span>
            </div>

            <!-- Step 3: Upload Berkas -->
            <div class="flex flex-col items-center w-1/4">
                <div class="w-7 h-7 sm:w-8 sm:h-8 md:w-12 md:h-12 rounded-full border-2 md:border-4 {{ $step >= 3 ? 'bg-blue-600 border-blue-200 text-white shadow-md' : 'bg-gray-100 border-white text-gray-400' }} flex items-center justify-center font-bold text-[10px] md:text-xl transition-all">
                    @if($step > 3) ✓ @else 3 @endif
                </div>
                <span class="mt-2 md:mt-3 text-[9px] sm:text-[10px] md:text-sm font-bold text-center leading-tight {{ $step >= 3 ? 'text-blue-700' : 'text-gray-500' }}">Upload<br class="md:hidden"> Berkas</span>
            </div>

            <!-- Step 4: Verifikasi -->
            <div class="flex flex-col items-center w-1/4">
                <div class="w-7 h-7 sm:w-8 sm:h-8 md:w-12 md:h-12 rounded-full border-2 md:border-4 {{ $step >= 4 ? 'bg-green-500 border-green-200 text-white shadow-md' : 'bg-gray-100 border-white text-gray-400' }} flex items-center justify-center font-bold text-[10px] md:text-xl transition-all">
                    4
                </div>
                <span class="mt-2 md:mt-3 text-[9px] sm:text-[10px] md:text-sm font-bold text-center leading-tight {{ $step >= 4 ? 'text-green-600' : 'text-gray-500' }}">Selesai</span>
            </div>
        </div>
    </div>
</div>

<!-- Card Aksi -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8">
    
    <!-- Card Formulir -->
    <div class="bg-white rounded-2xl p-5 md:p-6 border border-gray-100 card-3d">
        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-4 shadow-sm">
            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        </div>
        <h3 class="text-base md:text-lg font-black text-gray-800 mb-2">1. Formulir Data Diri</h3>
        <p class="text-xs md:text-sm text-gray-500 mb-6 leading-relaxed">Lengkapi identitas diri, data orang tua, dan pilihan program keahlian.</p>
        
        @if($step == 1)
            <a href="{{ route('student.formulir.data-pendaftaran') }}" class="block text-center text-sm md:text-base bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl hover:bg-blue-700 transition shadow-[0_4px_0_0_#1d4ed8] hover:shadow-[0_2px_0_0_#1d4ed8] hover:translate-y-1">Lengkapi Sekarang</a>
        @else
            <button disabled class="w-full text-center text-sm md:text-base bg-gray-100 text-green-600 font-bold py-2.5 px-4 rounded-xl border border-gray-200">✓ Selesai Diisi</button>
        @endif
    </div>

    <!-- Card Pembayaran -->
    <div class="bg-white rounded-2xl p-5 md:p-6 border border-gray-100 card-3d">
        <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-4 shadow-sm">
            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
        </div>
        <h3 class="text-base md:text-lg font-black text-gray-800 mb-2">2. Pembayaran Biaya</h3>
        <p class="text-xs md:text-sm text-gray-500 mb-6 leading-relaxed">Cek rincian biaya dan unggah bukti transfer pendaftaran.</p>
        
        @if($step >= 3)
            <button disabled class="w-full text-center text-sm md:text-base bg-gray-100 text-green-600 font-bold py-2.5 px-4 rounded-xl border border-gray-200">✓ Lunas / Selesai</button>
        @else
            <!-- Tombol Sengaja Dibuka Semua -->
            <a href="{{ route('student.pembayaran.informasi') }}" class="block text-center text-sm md:text-base bg-indigo-600 text-white font-bold py-2.5 px-4 rounded-xl hover:bg-indigo-700 transition shadow-[0_4px_0_0_#4338ca] hover:shadow-[0_2px_0_0_#4338ca] hover:translate-y-1">Lihat Tagihan / Bayar</a>
        @endif
    </div>

    <!-- Card Berkas -->
    <div class="bg-white rounded-2xl p-5 md:p-6 border border-gray-100 card-3d">
        <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center mb-4 shadow-sm">
            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
        </div>
        <h3 class="text-base md:text-lg font-black text-gray-800 mb-2">3. Upload Berkas</h3>
        <p class="text-xs md:text-sm text-gray-500 mb-6 leading-relaxed">Unggah scan KK, Akta Kelahiran, Ijazah, dan dokumen lainnya.</p>
        
        @if($step >= 4)
            <button disabled class="w-full text-center text-sm md:text-base bg-gray-100 text-green-600 font-bold py-2.5 px-4 rounded-xl border border-gray-200">✓ Berkas Diterima</button>
        @else
            <!-- Tombol Sengaja Dibuka Semua -->
            <a href="{{ route('student.berkas.upload') }}" class="block text-center text-sm md:text-base bg-orange-500 text-white font-bold py-2.5 px-4 rounded-xl hover:bg-orange-600 transition shadow-[0_4px_0_0_#c2410c] hover:shadow-[0_2px_0_0_#c2410c] hover:translate-y-1">Unggah Berkas</a>
        @endif
    </div>

</div>
@endsection