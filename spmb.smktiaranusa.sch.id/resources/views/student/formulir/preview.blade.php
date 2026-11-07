@extends('layouts.student')
@section('title', 'Preview Formulir')
@section('content')

@php
    // Cek Jurusan untuk Tema Warna & Nama Lengkap Program
    $majorRaw = strtolower($registration->major ?? '');
    $isMPLB = str_contains($majorRaw, 'mplb') || str_contains($majorRaw, 'perkantoran');
    $isTJKT = str_contains($majorRaw, 'tjkt') || str_contains($majorRaw, 'teknik');
    
    $majorFullName = '-';
    if ($isMPLB) {
        $majorFullName = 'MANAJEMEN PERKANTORAN DAN LAYANAN BISNIS';
    } elseif ($isTJKT) {
        $majorFullName = 'TEKNIK JARINGAN KOMPUTER DAN TELEKOMUNIKASI';
    } else {
        $majorFullName = strtoupper($registration->major ?? '-');
    }

    // Konfigurasi Warna
    $headerBg = $isMPLB ? 'bg-pink-200' : 'bg-blue-200';
    $headerText = $isMPLB ? 'text-pink-900' : 'text-blue-900';
    $programBg = $isMPLB ? 'bg-pink-100' : 'bg-blue-100';
    $borderColor = 'border-gray-400';

    // Ambil relasi data yang diperlukan
    $address = $student ? \App\Models\StudentAddress::where('student_id', $student->id)->first() : null;
    $ayah = $student ? \App\Models\StudentParent::where('student_id', $student->id)->where('type', 'ayah')->first() : null;
    $ibu = $student ? \App\Models\StudentParent::where('student_id', $student->id)->where('type', 'ibu')->first() : null;
    $wali = $student ? \App\Models\StudentParent::where('student_id', $student->id)->where('type', 'wali')->first() : null;

    // LOGIKA STATUS PENDAFTARAN
    $status = $registration->status ?? 'DRAFT';
    $isLocked = in_array($status, ['VERIFIED', 'DOCUMENT_REVIEW', 'ACCEPTED']);
    $isSubmitted = in_array($status, ['SUBMITTED', 'VERIFIED', 'DOCUMENT_REVIEW', 'ACCEPTED']);
@endphp

<!-- BANNER NOTIFIKASI STATUS -->
@if($isLocked)
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl shadow-sm flex items-start gap-3">
        <svg class="w-6 h-6 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        <div>
            <h4 class="font-bold text-yellow-800 text-lg">Pendaftaran Telah Diverifikasi</h4>
            <p class="text-sm text-yellow-700 mt-1">Pembayaran/data Anda telah diverifikasi oleh Panitia. Formulir pendaftaran sudah terkunci dan tidak dapat diubah lagi.</p>
        </div>
    </div>
@elseif($isSubmitted)
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl shadow-sm flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
            <h4 class="font-bold text-blue-800 text-lg">Pendaftaran Telah Dikirim</h4>
            <p class="text-sm text-blue-700 mt-1">Anda sudah mengirimkan formulir ini. Silakan tunggu proses verifikasi selanjutnya dari Panitia.</p>
        </div>
    </div>
@elseif(isset($unfilled_fields) && count($unfilled_fields) > 0)
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl shadow-sm">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <h4 class="font-bold text-red-800 text-lg">Pendaftaran Belum Bisa Dikirim!</h4>
                <p class="text-sm text-red-700 mt-1">Sistem mendeteksi ada data wajib yang belum Anda isi. Silakan lengkapi bagian berikut:</p>
                <ul class="list-disc list-inside mt-2 text-sm text-red-600 font-medium">
                    @foreach($unfilled_fields as $field => $url)
                        <li>
                            Bidang <strong>{{ $field }}</strong> kosong. 
                            <a href="{{ $url }}" class="underline hover:text-red-800 ml-1">Lengkapi Sekarang &rarr;</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@else
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl shadow-sm flex items-start gap-3">
        <svg class="w-6 h-6 text-green-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
            <h4 class="font-bold text-green-800 text-lg">Status: Menunggu Finalisasi</h4>
            <p class="text-sm text-green-700 mt-1">Seluruh data wajib telah terisi. Periksa kembali form di bawah ini. Jika sudah yakin benar, centang persetujuan dan klik tombol <strong>"Kirim Pendaftaran"</strong> di paling bawah halaman.</p>
        </div>
    </div>
@endif

<!-- BUNGKUS LUAR DENGAN OVERFLOW-X -->
<div class="w-full overflow-x-auto rounded border border-gray-300 shadow-sm bg-white">
    <!-- KERTAS FORMULIR -->
    <div class="p-4 md:p-6 text-xs md:text-sm text-gray-900" style="font-family: Arial, sans-serif; min-width: 700px;">
        
        <!-- HEADER -->
        <div class="flex items-center mb-6">
            <img src="{{ asset('logo.png') }}" alt="Logo SMK Tiara Nusa" class="w-16 h-16 md:w-24 md:h-24 object-contain shrink-0">
            <div class="flex-1 text-center">
                <h1 class="text-lg md:text-2xl font-bold uppercase tracking-wide">Formulir Pendaftaran Calon Peserta Didik</h1>
            </div>
        </div>

        <!-- PROGRAM PILIHAN -->
        <div class="flex justify-end items-center mb-4">
            <span class="mr-2 md:mr-4 font-medium">Program :</span>
            <div class="{{ $programBg }} border {{ $borderColor }} px-4 md:px-6 py-1.5 font-bold uppercase text-center text-xs md:text-sm max-w-[300px] md:max-w-none">
                {{ $majorFullName }}
            </div>
        </div>

        <!-- TABEL DATA -->
        <table class="w-full border-collapse border {{ $borderColor }}">
            
            <!-- BAGIAN 1: IDENTITAS -->
            <tr class="{{ $headerBg }} {{ $headerText }}">
                <td colspan="3" class="border {{ $borderColor }} text-center font-bold py-1.5">IDENTITAS PESERTA DIDIK (WAJIB DI ISI)</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 w-40 md:w-64 whitespace-nowrap">a. Nama Lengkap</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 w-4 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 uppercase font-medium">{{ Auth::user()->name }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 whitespace-nowrap">b. Jenis Kelamin</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">
                    {{ isset($student->gender) ? ($student->gender == 'L' ? 'Laki-laki' : 'Perempuan') : '-' }}
                </td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">c. NISN</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 tracking-[0.2em] md:tracking-[0.3em] font-mono">{{ $student->nisn ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">d. Nama Sekolah Asal</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->school_origin ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">e. Tempat Lahir</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->birth_place ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">f. Tanggal Lahir</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 tracking-[0.1em] md:tracking-[0.2em] font-mono">{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : '- / - / -' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">g. Agama</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->religion ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 whitespace-nowrap">h. Berkebutuhan Khusus</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ isset($student->special_needs) ? ($student->special_needs == 1 ? 'Ya' : 'Tidak') : '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">i. Anak ke-</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">
                    <span class="inline-block w-8 md:w-12 text-center border-b border-gray-400">{{ $student->child_number ?? '-' }}</span> dari 
                    <span class="inline-block w-8 md:w-12 text-center border-b border-gray-400 ml-1 md:ml-2">{{ $student->siblings_count ?? '-' }}</span> Bersaudara
                </td>
            </tr>
            
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 whitespace-nowrap">j. Alamat Tempat Tinggal</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 bg-gray-50"></td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Dusun / Jalan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} p-0">
                    <div class="flex items-center w-full h-full">
                        <div class="px-2 md:px-3 py-1.5 flex-1">{{ $address->district ?? '-' }}</div>
                        <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} w-16 md:w-24 whitespace-nowrap">RT : <span class="font-medium">{{ $address->rt ?? '-' }}</span></div>
                        <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} w-16 md:w-24 whitespace-nowrap">RW : <span class="font-medium">{{ $address->rw ?? '-' }}</span></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Kelurahan / Desa</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} p-0">
                    <div class="flex items-center w-full h-full">
                        <div class="px-2 md:px-3 py-1.5 flex-1">{{ $address->village ?? '-' }}</div>
                        <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} w-32 md:w-48 whitespace-nowrap">Kode Pos : <span class="font-medium">{{ $address->postal_code ?? '-' }}</span></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Kecamatan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $address->subdistrict ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Kabupaten / Kota</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $address->city ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Propinsi</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $address->province ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">k. No Telepon / WA</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ Auth::user()->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">l. Email Pribadi</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ Auth::user()->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">m. No NIK</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 tracking-[0.1em] md:tracking-[0.2em] font-mono">{{ $student->nik ?? '-' }}</td>
            </tr>

            <!-- BAGIAN 2: AYAH -->
            <tr class="{{ $headerBg }} {{ $headerText }}">
                <td colspan="3" class="border {{ $borderColor }} text-center font-bold py-1.5">DATA AYAH KANDUNG (WAJIB DIISI)</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">n. Nama Ayah</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} p-0">
                    <div class="flex items-center w-full h-full">
                        <div class="px-2 md:px-3 py-1.5 flex-1">{{ $ayah->name ?? '-' }}</div>
                        <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} whitespace-nowrap bg-gray-50/50">
                            Thn Lahir : <span class="font-medium ml-1 md:ml-2">{{ $ayah->birth_year ?? '-' }}</span>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8 whitespace-nowrap">Berkebutuhan Khusus</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ isset($ayah->special_needs) ? ($ayah->special_needs == 1 ? 'Ya' : 'Tidak') : '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Pekerjaan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $ayah->occupation ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Pendidikan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $ayah->education ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8 whitespace-nowrap">Penghasilan Bulanan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $ayah->monthly_income ?? '-' }}</td>
            </tr>

            <!-- BAGIAN 3: IBU -->
            <tr class="{{ $headerBg }} {{ $headerText }}">
                <td colspan="3" class="border {{ $borderColor }} text-center font-bold py-1.5">DATA IBU KANDUNG (WAJIB DIISI)</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">o. Nama Ibu</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} p-0">
                    <div class="flex items-center w-full h-full">
                        <div class="px-2 md:px-3 py-1.5 flex-1">{{ $ibu->name ?? '-' }}</div>
                        <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} whitespace-nowrap bg-gray-50/50">
                            Thn Lahir : <span class="font-medium ml-1 md:ml-2">{{ $ibu->birth_year ?? '-' }}</span>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8 whitespace-nowrap">Berkebutuhan Khusus</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ isset($ibu->special_needs) ? ($ibu->special_needs == 1 ? 'Ya' : 'Tidak') : '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Pekerjaan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $ibu->occupation ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Pendidikan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $ibu->education ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8 whitespace-nowrap">Penghasilan Bulanan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $ibu->monthly_income ?? '-' }}</td>
            </tr>

            <!-- BAGIAN 4: WALI -->
            <tr class="{{ $headerBg }} {{ $headerText }}">
                <td colspan="3" class="border {{ $borderColor }} text-center font-bold py-1.5">DATA WALI/PENANGGUNGJAWAB</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">p. Nama Wali</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} p-0">
                    <div class="flex items-center w-full h-full">
                        <div class="px-2 md:px-3 py-1.5 flex-1">{{ $wali->name ?? '-' }}</div>
                        <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} whitespace-nowrap bg-gray-50/50">
                            Thn Lahir : <span class="font-medium ml-1 md:ml-2">{{ $wali->birth_year ?? '-' }}</span>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Pekerjaan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $wali->occupation ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Pendidikan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $wali->education ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 pl-4 md:pl-8">Penghasilan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $wali->monthly_income ?? '-' }}</td>
            </tr>

            <!-- BAGIAN 5: PERIODIK -->
            <tr class="{{ $headerBg }} {{ $headerText }}">
                <td colspan="3" class="border {{ $borderColor }} text-center font-bold py-1.5">DATA PERIODIK (WAJIB DIISI)</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 whitespace-nowrap">q. Waktu Tempuh Berangkat</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->time_to_school ?? '-' }} Menit</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 whitespace-nowrap">r. Jarak Tempat Tinggal</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->distance_to_school ?? '-' }} KM</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 whitespace-nowrap">s. Alat Transportasi</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->transportation ?? '-' }}</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">t. Tinggi Badan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->height ?? '-' }} Cm</td>
            </tr>
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">u. Berat Badan</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5">{{ $student->weight ?? '-' }} Kg</td>
            </tr>

            <!-- BAGIAN 6: REKOMENDASI -->
            <tr>
                <td class="border {{ $borderColor }} px-2 md:px-3 py-1.5 font-bold uppercase whitespace-nowrap">Rekomendasi Dari</td>
                <td class="border {{ $borderColor }} px-1 py-1.5 text-center">:</td>
                <td class="border {{ $borderColor }} p-0">
                    <div class="flex flex-col w-full">
                        <!-- Siswa Aktif -->
                        <div class="flex items-center border-b {{ $borderColor }}">
                            <div class="px-2 md:px-3 py-1.5 w-24 md:w-32 border-r {{ $borderColor }} whitespace-nowrap">SISWA TINUS :</div>
                            <div class="px-2 md:px-3 py-1.5 flex-1">
                                {{ $registration && $registration->recommendation_status == 'Siswa Aktif' ? $registration->recommendation_name : '-' }}
                            </div>
                            <div class="px-2 md:px-3 py-1.5 border-l {{ $borderColor }} w-24 md:w-48 whitespace-nowrap">Kelas : 
                                <span class="font-medium ml-1 md:ml-2">{{ $registration && $registration->recommendation_status == 'Siswa Aktif' ? $registration->recommendation_class : '-' }}</span>
                            </div>
                        </div>
                        <!-- Alumni -->
                        <div class="flex items-center">
                            <div class="px-2 md:px-3 py-1.5 w-24 md:w-32 border-r {{ $borderColor }}">ALUMNI :</div>
                            <div class="px-2 md:px-3 py-1.5 flex-1">
                                {{ $registration && $registration->recommendation_status == 'Alumni' ? $registration->recommendation_name : '-' }}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
<!-- NOTE: Bungkus luar dengan 'overflow-x-auto' menjaga tabel tetap utuh saat scroll horizontal di HP -->

<!-- TOMBOL AKSI BAWAH -->
<div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 md:p-6 rounded-xl border border-gray-200 shadow-sm">
    
    <!-- Tombol Kembali -->
    <a href="{{ route('student.formulir.data-periodik') }}" class="w-full md:w-auto text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-6 py-2.5 text-center transition-colors">
        &larr; Kembali ke Formulir
    </a>

    <!-- Tombol Finalisasi -->
    @if(!isset($unfilled_fields) || count($unfilled_fields) == 0)
        <div class="w-full md:w-auto">
            <form action="{{ route('student.formulir.submit-final') }}" method="POST">
                @csrf
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <label class="flex items-center gap-2 {{ $isSubmitted ? 'cursor-not-allowed opacity-75' : 'cursor-pointer' }}">
                        <input type="checkbox" required 
                            class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 disabled:opacity-75 disabled:cursor-not-allowed"
                            {{ $isSubmitted ? 'checked disabled' : '' }}>
                        <span class="text-sm font-medium text-gray-800">Data sudah benar dan valid.</span>
                    </label>
                    
                    <button type="submit" 
                        {{ $isSubmitted ? 'disabled' : '' }} 
                        class="w-full md:w-auto text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-md flex items-center justify-center gap-2 transition-colors
                        {{ $isSubmitted ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }}">
                        
                        @if($isSubmitted)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Pendaftaran Terkirim
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Kirim Pendaftaran
                        @endif
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

@endsection