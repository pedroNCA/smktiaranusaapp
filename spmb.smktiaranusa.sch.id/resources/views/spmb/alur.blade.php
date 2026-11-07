@extends('layouts.public')

@section('title', 'Alur Pendaftaran SPMB - SMK Tiara Nusa')

@section('content')
<div class="max-w-screen-md mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Alur Pendaftaran SPMB 2026</h1>
    
    <ol class="relative border-s border-gray-200 ml-3">                  
        <li class="mb-10 ms-6">            
            <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -start-4 ring-4 ring-white">
                <span class="font-bold text-blue-700">1</span>
            </span>
            <h3 class="flex items-center mb-1 text-lg font-semibold text-gray-900">Daftar Akun & Verifikasi</h3>
            <p class="mb-4 text-base font-normal text-gray-500">Buat akun menggunakan Nama Lengkap, Email, No WhatsApp, dan Password (minimal 12 karakter kombinasi). Lakukan verifikasi OTP yang dikirim ke email.</p>
        </li>
        <li class="mb-10 ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -start-4 ring-4 ring-white">
                <span class="font-bold text-blue-700">2</span>
            </span>
            <h3 class="mb-1 text-lg font-semibold text-gray-900">Login & Isi Formulir</h3>
            <p class="text-base font-normal text-gray-500">Login menggunakan Email/ID SPMB. Lengkapi Data Pendaftaran (termasuk Pilihan Jurusan TJKT/MPLB), Data Diri, Data Orang Tua/Wali, dan Data Periodik.</p>
        </li>
        <li class="mb-10 ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -start-4 ring-4 ring-white">
                <span class="font-bold text-blue-700">3</span>
            </span>
            <h3 class="mb-1 text-lg font-semibold text-gray-900">Submit & Kunci Data</h3>
            <p class="text-base font-normal text-gray-500">Periksa kembali preview formulir. Setelah disubmit, data utama akan dikunci dan tidak dapat diubah (Status: SUBMITTED).</p>
        </li>
        <li class="mb-10 ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -start-4 ring-4 ring-white">
                <span class="font-bold text-blue-700">4</span>
            </span>
            <h3 class="mb-1 text-lg font-semibold text-gray-900">Pembayaran Formulir</h3>
            <p class="text-base font-normal text-gray-500">Unggah bukti pembayaran. Panitia akan melakukan verifikasi. Proses unggah berkas baru akan terbuka jika pembayaran berstatus disetujui (PAYMENT_APPROVED).</p>
        </li>
        <li class="mb-10 ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -start-4 ring-4 ring-white">
                <span class="font-bold text-blue-700">5</span>
            </span>
            <h3 class="mb-1 text-lg font-semibold text-gray-900">Upload Berkas Syarat</h3>
            <p class="text-base font-normal text-gray-500">Unggah scan SKL/Ijazah, NISN, Akta Lahir, dan Kartu Keluarga. Berkas akan masuk ke tahap verifikasi panitia (DOCUMENT_REVIEW).</p>
        </li>
        <li class="ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-green-100 rounded-full -start-4 ring-4 ring-white">
                <span class="font-bold text-green-700">6</span>
            </span>
            <h3 class="mb-1 text-lg font-semibold text-gray-900">Pengumuman Kelulusan</h3>
            <p class="text-base font-normal text-gray-500">Pantau dashboard secara berkala untuk melihat hasil pengumuman kelulusan SPMB Anda.</p>
        </li>
    </ol>
</div>
@endsection