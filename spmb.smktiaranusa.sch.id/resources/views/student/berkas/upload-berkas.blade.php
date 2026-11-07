@extends('layouts.student')
@section('title', 'Upload Berkas')
@section('content')

@php
    // Mapping dokumen yang sudah diupload ke dalam array berdasarkan 'type'
    $uploadedDocs = $registration->documents ? $registration->documents->keyBy('type') : collect();
    
    // Status global pendaftaran (misal: ACCEPTED atau VERIFIED pembayaran)
    $isGlobalVerified = in_array($registration->status ?? '', ['VERIFIED', 'ACCEPTED']);

    // LOGIKA PENGUNCIAN PER DOKUMEN:
    $isDocLocked = function($docType) use ($uploadedDocs, $isGlobalVerified, $registration) {
        // Jika dokumen belum pernah diunggah sama sekali, JANGAN KUNCI (biar bisa di-upload)
        if (!$uploadedDocs->has($docType)) {
            return false; 
        }

        // Jika pendaftaran secara global sudah berstatus diverifikasi/diterima total
        if ($isGlobalVerified) {
            return true;
        }
        
        $doc = $uploadedDocs[$docType];
        
        // Jika status dokumen spesifik ini ditolak (REJECTED), JANGAN KUNCI (biar bisa diganti)
        if (isset($doc->status) && $doc->status == 'REJECTED') {
            return false;
        }

        // Jika status dokumen spesifik ini sudah disetujui/diverifikasi panitia, KUNCI
        if (isset($doc->status) && in_array($doc->status, ['APPROVED', 'VERIFIED'])) {
            return true;
        }

        // Default: Jika sudah diunggah tapi belum direview/diverifikasi panitia, 
        // Anda bisa memilih apakah ingin menguncinya atau membiarkannya bisa diganti sebelum diverifikasi. 
        // Berdasarkan permintaan ("jika sudah verifikasi berkas maka berkas tidak bisa diganti"), 
        // maka jika statusnya masih PENDING/belum diverifikasi panitia, kita biarkan tombol Ganti File tetap AKTIF.
        return false; 
    };

    $isDocRejected = function($docType) use ($uploadedDocs) {
        if (!$uploadedDocs->has($docType)) return false;
        $doc = $uploadedDocs[$docType];
        return isset($doc->status) && $doc->status == 'REJECTED';
    };

    $hasNisn = $uploadedDocs->has('NISN');
    $hasAkta = $uploadedDocs->has('Akta_Lahir');
    $hasKK   = $uploadedDocs->has('Kartu_Keluarga');
    $hasSkl  = $uploadedDocs->has('SKL_Ijazah');
@endphp

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Unggah Berkas Persyaratan</h2>
    <p class="text-gray-600 mt-1">Lengkapi dokumen di bawah ini. Format yang diizinkan: JPG, PNG, atau PDF (Maksimal 2MB per file).</p>
</div>

@if($isGlobalVerified)
    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg text-yellow-800 shadow-sm flex items-start gap-3">
        <i class="fas fa-lock text-yellow-600 mt-1 text-lg"></i>
        <div>
            <p class="font-bold">Pendaftaran Telah Diverifikasi</p>
            <p class="text-sm mt-0.5">Seluruh berkas Anda telah diperiksa dan disetujui oleh Panitia. Dokumen sudah tidak dapat diubah kembali.</p>
        </div>
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
    <form action="{{ route('student.berkas.upload.store') }}" method="POST" enctype="multipart/form-data" id="form-upload">
        @csrf
        
        <div class="space-y-6">
            
            <!-- 1. Upload NISN -->
            @php 
                $lockedNisn = $isDocLocked('NISN');
                $rejectedNisn = $isDocRejected('NISN');
            @endphp
            <div class="p-4 border {{ $rejectedNisn ? 'border-red-300 bg-red-50/30' : 'border-gray-200 bg-gray-50' }} rounded-lg flex flex-col md:flex-row gap-4 items-start md:items-center justify-between transition" x-data="{ fileName: '' }">
                <div>
                    <h4 class="font-bold text-gray-900">1. Scan FC NISN</h4>
                    <p class="text-sm text-gray-500">Scan atau foto jelas dokumen NISN.</p>
                    <span x-text="fileName" class="text-xs font-semibold text-blue-600 mt-1 block"></span>
                    
                    @if($hasNisn)
                        <div class="flex items-center gap-2 mt-2">
                            <a href="{{ route('student.berkas.lihat', $uploadedDocs['NISN']->id) }}" target="_blank" class="inline-flex items-center text-xs font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded">
                                <i class="fas fa-check-circle mr-1"></i> Sudah Diunggah (Lihat)
                            </a>
                            @if($rejectedNisn)
                                <span class="inline-flex items-center text-xs font-bold text-red-700 bg-red-100 px-2.5 py-1 rounded">
                                    <i class="fas fa-times-circle mr-1"></i> Ditolak, Harap Unggah Ulang
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="relative">
                    <input type="file" name="nisn" class="absolute inset-0 w-full h-full opacity-0 {{ $lockedNisn ? 'cursor-not-allowed' : 'cursor-pointer' }}" @change="fileName = $event.target.files[0].name" accept=".pdf,.jpg,.jpeg,.png" {{ $lockedNisn ? 'disabled' : '' }}>
                    <button type="button" class="bg-white border {{ $lockedNisn ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-100' : ($hasNisn ? 'border-green-300 text-green-700' : 'border-gray-300 text-gray-700 hover:bg-gray-100') }} px-4 py-2 rounded-lg text-sm font-medium w-full md:w-auto text-center pointer-events-none shadow-sm">
                        <i class="fas fa-file-upload mr-1"></i> {{ $lockedNisn ? 'Terkunci' : ($hasNisn ? 'Ganti File' : 'Pilih File') }}
                    </button>
                </div>
            </div>

            <!-- 2. Upload Akta Kelahiran -->
            @php 
                $lockedAkta = $isDocLocked('Akta_Lahir');
                $rejectedAkta = $isDocRejected('Akta_Lahir');
            @endphp
            <div class="p-4 border {{ $rejectedAkta ? 'border-red-300 bg-red-50/30' : 'border-gray-200 bg-gray-50' }} rounded-lg flex flex-col md:flex-row gap-4 items-start md:items-center justify-between transition" x-data="{ fileName: '' }">
                <div>
                    <h4 class="font-bold text-gray-900">2. Scan FC Akta Lahir <span class="text-red-500">*</span></h4>
                    <p class="text-sm text-gray-500">Wajib diunggah.</p>
                    <span x-text="fileName" class="text-xs font-semibold text-blue-600 mt-1 block"></span>
                    
                    @if($hasAkta)
                        <div class="flex items-center gap-2 mt-2">
                            <a href="{{ route('student.berkas.lihat', $uploadedDocs['Akta_Lahir']->id) }}" target="_blank" class="inline-flex items-center text-xs font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded">
                                <i class="fas fa-check-circle mr-1"></i> Sudah Diunggah (Lihat)
                            </a>
                            @if($rejectedAkta)
                                <span class="inline-flex items-center text-xs font-bold text-red-700 bg-red-100 px-2.5 py-1 rounded">
                                    <i class="fas fa-times-circle mr-1"></i> Ditolak, Harap Unggah Ulang
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="relative">
                    <input type="file" name="akta" class="absolute inset-0 w-full h-full opacity-0 {{ $lockedAkta ? 'cursor-not-allowed' : 'cursor-pointer' }}" @change="fileName = $event.target.files[0].name" accept=".pdf,.jpg,.jpeg,.png" {{ $lockedAkta ? 'disabled' : ($hasAkta ? '' : 'required') }}>
                    <button type="button" class="bg-white border {{ $lockedAkta ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-100' : ($hasAkta ? 'border-green-300 text-green-700' : 'border-gray-300 text-gray-700 hover:bg-gray-100') }} px-4 py-2 rounded-lg text-sm font-medium w-full md:w-auto text-center pointer-events-none shadow-sm">
                        <i class="fas fa-file-upload mr-1"></i> {{ $lockedAkta ? 'Terkunci' : ($hasAkta ? 'Ganti File' : 'Pilih File') }}
                    </button>
                </div>
            </div>

            <!-- 3. Upload Kartu Keluarga -->
            @php 
                $lockedKK = $isDocLocked('Kartu_Keluarga');
                $rejectedKK = $isDocRejected('Kartu_Keluarga');
            @endphp
            <div class="p-4 border {{ $rejectedKK ? 'border-red-300 bg-red-50/30' : 'border-gray-200 bg-gray-50' }} rounded-lg flex flex-col md:flex-row gap-4 items-start md:items-center justify-between transition" x-data="{ fileName: '' }">
                <div>
                    <h4 class="font-bold text-gray-900">3. Scan FC Kartu Keluarga <span class="text-red-500">*</span></h4>
                    <p class="text-sm text-gray-500">Wajib diunggah.</p>
                    <span x-text="fileName" class="text-xs font-semibold text-blue-600 mt-1 block"></span>
                    
                    @if($hasKK)
                        <div class="flex items-center gap-2 mt-2">
                            <a href="{{ route('student.berkas.lihat', $uploadedDocs['Kartu_Keluarga']->id) }}" target="_blank" class="inline-flex items-center text-xs font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded">
                                <i class="fas fa-check-circle mr-1"></i> Sudah Diunggah (Lihat)
                            </a>
                            @if($rejectedKK)
                                <span class="inline-flex items-center text-xs font-bold text-red-700 bg-red-100 px-2.5 py-1 rounded">
                                    <i class="fas fa-times-circle mr-1"></i> Ditolak, Harap Unggah Ulang
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="relative">
                    <input type="file" name="kk" class="absolute inset-0 w-full h-full opacity-0 {{ $lockedKK ? 'cursor-not-allowed' : 'cursor-pointer' }}" @change="fileName = $event.target.files[0].name" accept=".pdf,.jpg,.jpeg,.png" {{ $lockedKK ? 'disabled' : ($hasKK ? '' : 'required') }}>
                    <button type="button" class="bg-white border {{ $lockedKK ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-100' : ($hasKK ? 'border-green-300 text-green-700' : 'border-gray-300 text-gray-700 hover:bg-gray-100') }} px-4 py-2 rounded-lg text-sm font-medium w-full md:w-auto text-center pointer-events-none shadow-sm">
                        <i class="fas fa-file-upload mr-1"></i> {{ $lockedKK ? 'Terkunci' : ($hasKK ? 'Ganti File' : 'Pilih File') }}
                    </button>
                </div>
            </div>

            <!-- 4. Upload SKL/Ijazah -->
            @php 
                $lockedSkl = $isDocLocked('SKL_Ijazah');
                $rejectedSkl = $isDocRejected('SKL_Ijazah');
            @endphp
            <div class="p-4 border {{ $rejectedSkl ? 'border-red-300 bg-red-50/30' : 'border-gray-200 bg-gray-50' }} rounded-lg flex flex-col md:flex-row gap-4 items-start md:items-center justify-between transition" x-data="{ fileName: '' }">
                <div>
                    <h4 class="font-bold text-gray-900">4. Scan FC SKL/Ijazah SMP</h4>
                    <p class="text-sm text-gray-500">Opsional (jika sudah ada).</p>
                    <span x-text="fileName" class="text-xs font-semibold text-blue-600 mt-1 block"></span>
                    
                    @if($hasSkl)
                        <div class="flex items-center gap-2 mt-2">
                            <a href="{{ route('student.berkas.lihat', $uploadedDocs['SKL_Ijazah']->id) }}" target="_blank" class="inline-flex items-center text-xs font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded">
                                <i class="fas fa-check-circle mr-1"></i> Sudah Diunggah (Lihat)
                            </a>
                            @if($rejectedSkl)
                                <span class="inline-flex items-center text-xs font-bold text-red-700 bg-red-100 px-2.5 py-1 rounded">
                                    <i class="fas fa-times-circle mr-1"></i> Ditolak, Harap Unggah Ulang
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="relative">
                    <input type="file" name="skl" class="absolute inset-0 w-full h-full opacity-0 {{ $lockedSkl ? 'cursor-not-allowed' : 'cursor-pointer' }}" @change="fileName = $event.target.files[0].name" accept=".pdf,.jpg,.jpeg,.png" {{ $lockedSkl ? 'disabled' : '' }}>
                    <button type="button" class="bg-white border {{ $lockedSkl ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-100' : ($hasSkl ? 'border-green-300 text-green-700' : 'border-gray-300 text-gray-700 hover:bg-gray-100') }} px-4 py-2 rounded-lg text-sm font-medium w-full md:w-auto text-center pointer-events-none shadow-sm">
                        <i class="fas fa-file-upload mr-1"></i> {{ $lockedSkl ? 'Terkunci' : ($hasSkl ? 'Ganti File' : 'Pilih File') }}
                    </button>
                </div>
            </div>

        </div>

        @php
            // Tombol simpan hanya disembunyikan jika SEMUA dokumen sudah terkunci (diverifikasi)
            $allLocked = $isDocLocked('NISN') && $isDocLocked('Akta_Lahir') && $isDocLocked('Kartu_Keluarga') && $isDocLocked('SKL_Ijazah');
        @endphp

        @if(!$allLocked)
            <div class="mt-8 flex justify-end">
                <button type="button" onclick="confirmUpload()" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-bold rounded-xl text-sm px-6 py-3 shadow-md flex items-center gap-2 transition">
                    <i class="fas fa-cloud-upload-alt"></i> Simpan & Unggah Berkas
                </button>
            </div>
        @endif
    </form>
</div>

<!-- SweetAlert Script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmUpload() {
        const form = document.getElementById('form-upload');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Unggah',
            text: "Pastikan file yang dipilih sudah benar. Lanjutkan menyimpan berkas?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1d4ed8',
            cancelButtonColor: '#6b7280',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Unggah!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Mengunggah Berkas...',
                    text: 'Mohon tunggu, berkas sedang diproses ke sistem.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                form.submit();
            }
        });
    }
</script>

{{-- Notifikasi Sukses dari Controller --}}
@if(session('success'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1d4ed8'
        });
    });
</script>
@endif

{{-- Notifikasi Error Dinamis dari Server --}}
@if($errors->any())
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let errorMessages = '';
        @foreach($errors->all() as $error)
            errorMessages += '• {{ $error }}<br>';
        @endforeach

        Swal.fire({
            icon: 'error',
            title: 'Gagal Validasi File!',
            html: '<div style="text-align: left; font-size: 14px;">' + errorMessages + '</div>',
            confirmButtonColor: '#d33'
        });
    });
</script>
@endif
@endsection