@extends('layouts.student')
@section('title', 'Data Periodik - SPMB')

@section('content')
    @php
        $user = Auth::user();
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        $registration = \App\Models\Registration::where('user_id', $user->id)->first();
        
        // Cek kelengkapan data per bagian untuk indikator titik merah
        $isPendaftaranFilled = $registration && !empty($registration->entry_path) && !empty($registration->major);
        $isDataDiriFilled = $student && !empty($student->nisn) && !empty($student->nik);
        
        $ayah = $student ? \App\Models\StudentParent::where('student_id', $student->id)->where('type', 'ayah')->first() : null;
        $ibu = $student ? \App\Models\StudentParent::where('student_id', $student->id)->where('type', 'ibu')->first() : null;
        $isOrangTuaFilled = $ayah && $ibu && !empty($ayah->name) && !empty($ibu->name);

        $isPeriodikFilled = $student && !empty($student->distance_to_school) && !empty($student->transportation);

        // LOGIKA KUNCI FORMULIR
        $isLocked = $registration && in_array($registration->status, ['VERIFIED', 'DOCUMENT_REVIEW', 'ACCEPTED']);
    @endphp

    <!-- HEADER MENU NAVIGASI (CLICKABLE DENGAN INDIKATOR TITIK MERAH) -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex gap-6 overflow-x-auto text-sm font-medium">
        
        <!-- 1. Pendaftaran -->
        <a href="{{ route('student.formulir.data-pendaftaran') }}" class="relative {{ request()->routeIs('student.formulir.data-pendaftaran') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            1. Pendaftaran
            @if(!$isPendaftaranFilled) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>
        
        <!-- 2. Data Diri -->
        <a href="{{ route('student.formulir.data-diri') }}" class="relative {{ request()->routeIs('student.formulir.data-diri') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            2. Data Diri
            @if(!$isDataDiriFilled) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>
        
        <!-- 3. Orang Tua -->
        <a href="{{ route('student.formulir.data-orang-tua') }}" class="relative {{ request()->routeIs('student.formulir.data-orang-tua') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            3. Orang Tua
            @if(!$isOrangTuaFilled) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>
        
        <!-- 4. Periodik -->
        <a href="{{ route('student.formulir.data-periodik') }}" class="relative {{ request()->routeIs('student.formulir.data-periodik') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            4. Periodik
            @if(!$isPeriodikFilled) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>

        <a href="{{ route('student.formulir.preview') }}" class="text-gray-500 hover:text-blue-600 ml-auto">5. Preview</a>
    </div>

    <div class="p-6 md:p-8">
        
        @if($isLocked)
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg text-yellow-800 shadow-sm flex items-start gap-3">
                <i class="fas fa-lock text-yellow-600 mt-1"></i>
                <div>
                    <p class="font-bold">Formulir Terkunci</p>
                    <p class="text-sm mt-0.5">Pembayaran Anda telah diverifikasi. Data ini sudah tidak dapat diubah lagi. Jika ada kesalahan, silakan hubungi Panitia.</p>
                </div>
            </div>
        @endif

        <!-- POPUP NOTIFIKASI SUCCESS / ERROR -->
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="block sm:inline font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-700 hover:text-green-900">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="block sm:inline font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-700 hover:text-red-900">&times;</button>
            </div>
        @endif
        
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg text-red-700">
                <p class="font-bold">⚠️ Perhatian! Ada data wajib yang terlewat atau tidak sesuai:</p>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h3 class="text-xl font-bold text-gray-900 mb-6">Data Periodik Siswa</h3>
        
        <form action="{{ route('student.formulir.store-data-periodik') }}" method="POST">
            @csrf
            
            <fieldset {{ $isLocked ? 'disabled' : '' }}>
                <div class="mb-8 p-5 bg-gray-50 rounded-xl border">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Tinggi Badan (cm) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.1" name="tinggi_badan" value="{{ old('tinggi_badan', $student->height ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Contoh: 165">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Berat Badan (kg) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.1" name="berat_badan" value="{{ old('berat_badan', $student->weight ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Contoh: 55">
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Jarak Tempuh ke Sekolah (km) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.1" name="jarak_sekolah" value="{{ old('jarak_sekolah', $student->distance_to_school ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Contoh: 2.5">
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Waktu Tempuh ke Sekolah (Menit) <span class="text-red-500">*</span></label>
                            <input type="number" name="waktu_tempuh" value="{{ old('waktu_tempuh', $student->time_to_school ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Contoh: 15">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Alat Transportasi Utama <span class="text-red-500">*</span></label>
                            <select name="alat_transportasi" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Transportasi --</option>
                                <option value="Jalan Kaki" {{ old('alat_transportasi', $student->transportation ?? '') == 'Jalan Kaki' ? 'selected' : '' }}>Jalan Kaki</option>
                                <option value="Kendaraan Pribadi" {{ old('alat_transportasi', $student->transportation ?? '') == 'Kendaraan Pribadi' ? 'selected' : '' }}>Kendaraan Pribadi</option>
                                <option value="Kendaraan Umum" {{ old('alat_transportasi', $student->transportation ?? '') == 'Kendaraan Umum' ? 'selected' : '' }}>Kendaraan Umum</option>
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8 flex justify-between">
                <a href="{{ route('student.formulir.data-orang-tua') }}" class="text-gray-700 bg-white border border-gray-300 font-medium rounded-lg text-sm px-6 py-2.5 hover:bg-gray-50 transition-colors">← Kembali</a>
                @if(!$isLocked)
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-6 py-2.5">Simpan</button>
                @endif
            </div>
        </form>
    </div>
@endsection