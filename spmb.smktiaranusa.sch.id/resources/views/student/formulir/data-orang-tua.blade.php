@extends('layouts.student')
@section('title', 'Data Orang Tua - SPMB')

@section('content')
    @php
        $user = Auth::user();
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        $registration = \App\Models\Registration::where('user_id', $user->id)->first();
        
        // Cek kelengkapan data per bagian
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
            @if(!$isPendaftaranFilled) 
                <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> 
            @endif
        </a>
        
        <!-- 2. Data Diri -->
        <a href="{{ route('student.formulir.data-diri') }}" class="relative {{ request()->routeIs('student.formulir.data-diri') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            2. Data Diri
            @if(!$isDataDiriFilled) 
                <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> 
            @endif
        </a>
        
        <!-- 3. Orang Tua -->
        <a href="{{ route('student.formulir.data-orang-tua') }}" class="relative {{ request()->routeIs('student.formulir.data-orang-tua') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            3. Orang Tua
            @if(!$isOrangTuaFilled) 
                <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> 
            @endif
        </a>
        
        <!-- 4. Periodik -->
        <a href="{{ route('student.formulir.data-periodik') }}" class="relative {{ request()->routeIs('student.formulir.data-periodik') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            4. Periodik
            @if(!$isPeriodikFilled) 
                <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> 
            @endif
        </a>

        <a href="{{ route('student.formulir.preview') }}" class="text-gray-500 hover:text-blue-600 ml-auto">5. Preview & Finalisasi &rarr;</a>
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

        <h3 class="text-xl font-bold text-gray-900 mb-6">Data Ayah, Ibu, dan Wali</h3>
        
        <form action="{{ route('student.formulir.store-orang-tua') }}" method="POST">
            @csrf
            
            <fieldset {{ $isLocked ? 'disabled' : '' }}>
                <!-- ============================== AYAH KANDUNG ============================== -->
                <div class="mb-8 p-5 bg-gray-50 rounded-xl border">
                    <h4 class="font-bold text-blue-700 mb-4">Data Ayah Kandung</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nama Ayah <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $ayah->name ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Tahun Lahir <span class="text-red-500">*</span></label>
                            <select name="tahun_lahir_ayah" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Tahun --</option>
                                @for($i = date('Y') - 15; $i >= 1940; $i--)
                                    <option value="{{ $i }}" {{ old('tahun_lahir_ayah', $ayah->birth_year ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Berkebutuhan Khusus <span class="text-red-500">*</span></label>
                            <select name="bk_ayah" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih --</option>
                                <option value="Tidak" {{ old('bk_ayah', isset($ayah) ? ($ayah->special_needs == 1 ? 'Ya' : 'Tidak') : '') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                                <option value="Ya" {{ old('bk_ayah', isset($ayah) ? ($ayah->special_needs == 1 ? 'Ya' : 'Tidak') : '') == 'Ya' ? 'selected' : '' }}>Ya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pekerjaan <span class="text-red-500">*</span></label>
                            <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $ayah->occupation ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pendidikan Terakhir <span class="text-red-500">*</span></label>
                            <select name="pendidikan_ayah" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="Tidak Sekolah" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'Tidak Sekolah' ? 'selected' : '' }}>Tidak Sekolah</option>
                                <option value="SD/Sederajat" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'SD/Sederajat' ? 'selected' : '' }}>SD / Sederajat</option>
                                <option value="SMP/Sederajat" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'SMP/Sederajat' ? 'selected' : '' }}>SMP / Sederajat</option>
                                <option value="SMA/Sederajat" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'SMA/Sederajat' ? 'selected' : '' }}>SMA / SMK / Sederajat</option>
                                <option value="D1-D3" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'D1-D3' ? 'selected' : '' }}>D1 - D3</option>
                                <option value="S1-D4" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'S1-D4' ? 'selected' : '' }}>S1 / D4</option>
                                <option value="S2" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'S2' ? 'selected' : '' }}>S2</option>
                                <option value="S3" {{ old('pendidikan_ayah', $ayah->education ?? '') == 'S3' ? 'selected' : '' }}>S3</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Penghasilan Bulanan <span class="text-red-500">*</span></label>
                            <select name="penghasilan_ayah" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
    <option value="">-- Pilih Penghasilan --</option>
    <option value="Tidak Berpenghasilan" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == 'Tidak Berpenghasilan' ? 'selected' : '' }}>Tidak Berpenghasilan</option>
    <option value="Kurang dari 500.000" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == 'Kurang dari 500.000' ? 'selected' : '' }}>Kurang dari 500.000</option>
    <option value="500.001 - 2.000.000" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == '500.001 - 2.000.000' ? 'selected' : '' }}>500.001 - 2.000.000</option>
    <option value="2.000.001 - 3.500.000" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == '2.000.001 - 3.500.000' ? 'selected' : '' }}>2.000.001 - 3.500.000</option>
    <option value="3.500.001 - 5.000.000" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == '3.500.001 - 5.000.000' ? 'selected' : '' }}>3.500.001 - 5.000.000</option>
    <option value="5.000.001 - 10.000.000" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == '5.000.001 - 10.000.000' ? 'selected' : '' }}>5.000.001 - 10.000.000</option>
    <option value="Lebih dari 10.000.000" {{ old('penghasilan_ayah', $ayah->monthly_income ?? '') == 'Lebih dari 10.000.000' ? 'selected' : '' }}>Lebih dari 10.000.000</option>
</select>
                        </div>
                    </div>
                </div>

                <!-- ============================== IBU KANDUNG ============================== -->
                <div class="mb-8 p-5 bg-gray-50 rounded-xl border">
                    <h4 class="font-bold text-pink-700 mb-4">Data Ibu Kandung</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nama Ibu <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $ibu->name ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Tahun Lahir <span class="text-red-500">*</span></label>
                            <select name="tahun_lahir_ibu" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Tahun --</option>
                                @for($i = date('Y') - 15; $i >= 1940; $i--)
                                    <option value="{{ $i }}" {{ old('tahun_lahir_ibu', $ibu->birth_year ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Berkebutuhan Khusus <span class="text-red-500">*</span></label>
                            <select name="bk_ibu" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih --</option>
                                <option value="Tidak" {{ old('bk_ibu', isset($ibu) ? ($ibu->special_needs == 1 ? 'Ya' : 'Tidak') : '') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                                <option value="Ya" {{ old('bk_ibu', isset($ibu) ? ($ibu->special_needs == 1 ? 'Ya' : 'Tidak') : '') == 'Ya' ? 'selected' : '' }}>Ya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pekerjaan <span class="text-red-500">*</span></label>
                            <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $ibu->occupation ?? '') }}" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pendidikan Terakhir <span class="text-red-500">*</span></label>
                            <select name="pendidikan_ibu" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="Tidak Sekolah" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'Tidak Sekolah' ? 'selected' : '' }}>Tidak Sekolah</option>
                                <option value="SD/Sederajat" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'SD/Sederajat' ? 'selected' : '' }}>SD / Sederajat</option>
                                <option value="SMP/Sederajat" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'SMP/Sederajat' ? 'selected' : '' }}>SMP / Sederajat</option>
                                <option value="SMA/Sederajat" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'SMA/Sederajat' ? 'selected' : '' }}>SMA / SMK / Sederajat</option>
                                <option value="D1-D3" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'D1-D3' ? 'selected' : '' }}>D1 - D3</option>
                                <option value="S1-D4" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'S1-D4' ? 'selected' : '' }}>S1 / D4</option>
                                <option value="S2" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'S2' ? 'selected' : '' }}>S2</option>
                                <option value="S3" {{ old('pendidikan_ibu', $ibu->education ?? '') == 'S3' ? 'selected' : '' }}>S3</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Penghasilan Bulanan <span class="text-red-500">*</span></label>
                            <select name="penghasilan_ibu" required class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
    <option value="">-- Pilih Penghasilan --</option>
    <option value="Tidak Berpenghasilan" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == 'Tidak Berpenghasilan' ? 'selected' : '' }}>Tidak Berpenghasilan</option>
    <option value="Kurang dari 500.000" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == 'Kurang dari 500.000' ? 'selected' : '' }}>Kurang dari 500.000</option>
    <option value="500.001 - 2.000.000" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == '500.001 - 2.000.000' ? 'selected' : '' }}>500.001 - 2.000.000</option>
    <option value="2.000.001 - 3.500.000" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == '2.000.001 - 3.500.000' ? 'selected' : '' }}>2.000.001 - 3.500.000</option>
    <option value="3.500.001 - 5.000.000" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == '3.500.001 - 5.000.000' ? 'selected' : '' }}>3.500.001 - 5.000.000</option>
    <option value="5.000.001 - 10.000.000" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == '5.000.001 - 10.000.000' ? 'selected' : '' }}>5.000.001 - 10.000.000</option>
    <option value="Lebih dari 10.000.000" {{ old('penghasilan_ibu', $ibu->monthly_income ?? '') == 'Lebih dari 10.000.000' ? 'selected' : '' }}>Lebih dari 10.000.000</option>
</select>
                        </div>
                    </div>
                </div>

                <!-- ============================== WALI (Opsional) ============================== -->
                @php
                    $wali = $student ? \App\Models\StudentParent::where('student_id', $student->id)->where('type', 'wali')->first() : null;
                @endphp
                <div class="mb-8 p-5 bg-blue-50/40 rounded-xl border border-blue-200">
                    <h4 class="font-bold text-gray-800 mb-4">Data Wali (Opsional / Kosongkan jika tidak ada)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nama Wali</label>
                            <input type="text" name="nama_wali" value="{{ old('nama_wali', $wali->name ?? '') }}" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5" placeholder="Nama wali...">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Tahun Lahir</label>
                            <select name="tahun_lahir_wali" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Tahun --</option>
                                @for($i = date('Y') - 15; $i >= 1940; $i--)
                                    <option value="{{ $i }}" {{ old('tahun_lahir_wali', $wali->birth_year ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Berkebutuhan Khusus</label>
                            <select name="bk_wali" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih --</option>
                                <option value="Tidak" {{ old('bk_wali', isset($wali) ? ($wali->special_needs == 1 ? 'Ya' : 'Tidak') : '') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                                <option value="Ya" {{ old('bk_wali', isset($wali) ? ($wali->special_needs == 1 ? 'Ya' : 'Tidak') : '') == 'Ya' ? 'selected' : '' }}>Ya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pekerjaan</label>
                            <input type="text" name="pekerjaan_wali" value="{{ old('pekerjaan_wali', $wali->occupation ?? '') }}" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pendidikan Terakhir</label>
                            <select name="pendidikan_wali" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="Tidak Sekolah" {{ old('pendidikan_wali', $wali->education ?? '') == 'Tidak Sekolah' ? 'selected' : '' }}>Tidak Sekolah</option>
                                <option value="SD/Sederajat" {{ old('pendidikan_wali', $wali->education ?? '') == 'SD/Sederajat' ? 'selected' : '' }}>SD / Sederajat</option>
                                <option value="SMP/Sederajat" {{ old('pendidikan_wali', $wali->education ?? '') == 'SMP/Sederajat' ? 'selected' : '' }}>SMP / Sederajat</option>
                                <option value="SMA/Sederajat" {{ old('pendidikan_wali', $wali->education ?? '') == 'SMA/Sederajat' ? 'selected' : '' }}>SMA / SMK / Sederajat</option>
                                <option value="D1-D3" {{ old('pendidikan_wali', $wali->education ?? '') == 'D1-D3' ? 'selected' : '' }}>D1 - D3</option>
                                <option value="S1-D4" {{ old('pendidikan_wali', $wali->education ?? '') == 'S1-D4' ? 'selected' : '' }}>S1 / D4</option>
                                <option value="S2" {{ old('pendidikan_wali', $wali->education ?? '') == 'S2' ? 'selected' : '' }}>S2</option>
                                <option value="S3" {{ old('pendidikan_wali', $wali->education ?? '') == 'S3' ? 'selected' : '' }}>S3</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Penghasilan Bulanan</label>
                            <select name="penghasilan_wali" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
    <option value="">-- Pilih Penghasilan --</option>
    <option value="Tidak Berpenghasilan" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == 'Tidak Berpenghasilan' ? 'selected' : '' }}>Tidak Berpenghasilan</option>
    <option value="Kurang dari 500.000" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == 'Kurang dari 500.000' ? 'selected' : '' }}>Kurang dari 500.000</option>
    <option value="500.001 - 2.000.000" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == '500.001 - 2.000.000' ? 'selected' : '' }}>500.001 - 2.000.000</option>
    <option value="2.000.001 - 3.500.000" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == '2.000.001 - 3.500.000' ? 'selected' : '' }}>2.000.001 - 3.500.000</option>
    <option value="3.500.001 - 5.000.000" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == '3.500.001 - 5.000.000' ? 'selected' : '' }}>3.500.001 - 5.000.000</option>
    <option value="5.000.001 - 10.000.000" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == '5.000.001 - 10.000.000' ? 'selected' : '' }}>5.000.001 - 10.000.000</option>
    <option value="Lebih dari 10.000.000" {{ old('penghasilan_wali', $wali->monthly_income ?? '') == 'Lebih dari 10.000.000' ? 'selected' : '' }}>Lebih dari 10.000.000</option>
</select>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8 flex justify-between">
                <a href="{{ route('student.formulir.data-diri') }}" class="text-gray-700 bg-white border border-gray-300 font-medium rounded-lg text-sm px-6 py-2.5 hover:bg-gray-50 transition-colors">← Kembali</a>
                @if(!$isLocked)
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-6 py-2.5">Simpan & Lanjutkan →</button>
                @endif
            </div>
        </form>
    </div>
@endsection