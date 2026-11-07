@extends('layouts.student')
@section('title', 'Data Pendaftaran - SPMB')
@section('content')
<!-- HEADER MENU NAVIGASI (CLICKABLE) -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex gap-6 overflow-x-auto text-sm font-medium">
        <a href="{{ route('student.formulir.data-pendaftaran') }}" class="{{ request()->routeIs('student.formulir.data-pendaftaran') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">1. Pendaftaran</a>
        
        <a href="{{ route('student.formulir.data-diri') }}" class="relative {{ request()->routeIs('student.formulir.data-diri') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">
            2. Data Diri
            @if(!\App\Models\Student::where('user_id', Auth::id())->exists()) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>
        
        <a href="{{ route('student.formulir.data-orang-tua') }}" class="{{ request()->routeIs('student.formulir.data-orang-tua') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">3. Orang Tua</a>
        <a href="{{ route('student.formulir.data-periodik') }}" class="{{ request()->routeIs('student.formulir.data-periodik') ? 'text-blue-700 font-bold border-b-2 border-blue-700 pb-1' : 'text-gray-500 hover:text-blue-600' }}">4. Periodik</a>
        <a href="{{ route('student.formulir.preview') }}" class="text-gray-500 hover:text-blue-600 ml-auto">5. Preview & Finalisasi &rarr;</a>
    </div>

    <div class="p-6 md:p-8">
        {{-- LOGIKA KUNCI FORMULIR --}}
        @php
            $isLocked = isset($registration) && in_array($registration->status, ['VERIFIED', 'DOCUMENT_REVIEW', 'ACCEPTED']);
        @endphp

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
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative flex items-center justify-between">
                <span class="font-medium">{{ session('success') }}</span>
                <button @click="show = false">&times;</button>
            </div>
        @endif
        
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg text-red-700">
                <p class="font-bold">Mohon Periksa Kembali Form Anda:</p>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div x-data="{ jenisRekomendasi: '{{ old('rekomendasi_status', $registration->recommendation_status ?? 'Tidak') }}' }">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Data Pendaftaran & Program Keahlian</h3>
            
            <form action="{{ route('student.formulir.store-pendaftaran') }}" method="POST">
                @csrf
                <fieldset {{ $isLocked ? 'disabled' : '' }}>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">ID SPMB (Otomatis)</label>
                            <input type="text" value="{{ $registration->spmb_id ?? 'SPMB-2026-XXXX' }}" disabled class="bg-gray-100 border border-gray-300 text-gray-700 font-bold text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Tahun Pelajaran</label>
                            <input type="text" value="2027/2028" disabled class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Gelombang Pendaftaran</label>
                            <input type="text" value="{{ $period->name ?? 'Gelombang 1' }}" disabled class="bg-gray-100 border border-gray-300 text-gray-700 font-bold text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Jalur Masuk <span class="text-red-500">*</span></label>
                            <select name="jalur_masuk" required class="border text-gray-700 text-sm rounded-lg block w-full p-2.5 {{ $errors->has('jalur_masuk') ? 'border-red-500 bg-red-50' : 'border-gray-300' }}">
                                <option value="">-- Pilih Jalur Masuk --</option>
                                <option value="Reguler" @selected(old('jalur_masuk', $registration->entry_path ?? '') == 'Reguler')>Reguler</option>
                            </select>
                            @error('jalur_masuk') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pilihan Jurusan <span class="text-red-500">*</span></label>
                            <select name="pilihan_jurusan" required style="border-color: {{ $errors->has('pilihan_jurusan') ? '#ef4444' : '#d1d5db' }}" class="border text-gray-700 text-sm rounded-lg block w-full p-2.5 @error('pilihan_jurusan') bg-red-50 @enderror">
                                <option value="">-- Pilih Jurusan --</option>
                                <option value="TJKT" @selected(old('pilihan_jurusan', $registration->major ?? '') == 'TJKT')>Teknik Jaringan Komputer dan Telekomunikasi</option>
                                <option value="MPLB" @selected(old('pilihan_jurusan', $registration->major ?? '') == 'MPLB')>Manajemen Perkantoran dan Layanan Bisnis</option>
                            </select>
                            @error('pilihan_jurusan') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2 border-t pt-4">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Rekomendasi Dari <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center"><input type="radio" name="rekomendasi_status" value="Tidak" x-model="jenisRekomendasi" class="mr-2"> Tidak Ada</label>
                                <label class="flex items-center"><input type="radio" name="rekomendasi_status" value="Alumni" x-model="jenisRekomendasi" class="mr-2"> Alumni</label>
                                <label class="flex items-center"><input type="radio" name="rekomendasi_status" value="Siswa Aktif" x-model="jenisRekomendasi" class="mr-2"> Siswa Aktif</label>
                            </div>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="jenisRekomendasi === 'Alumni' || jenisRekomendasi === 'Siswa Aktif'" x-cloak>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Nama Lengkap <span x-text="jenisRekomendasi"></span> <span class="text-red-500">*</span></label>
                                <input type="text" name="rekomendasi_nama" value="{{ old('rekomendasi_nama', $registration->recommendation_name ?? '') }}" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            </div>
                            
                            <div x-show="jenisRekomendasi === 'Siswa Aktif'">
                                <label class="block mb-2 text-sm font-medium text-gray-700">Kelas <span class="text-red-500">*</span></label>
                                <input type="text" name="rekomendasi_kelas" value="{{ old('rekomendasi_kelas', $registration->recommendation_class ?? '') }}" placeholder="Contoh: XII TJKT 1" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="mt-8 flex justify-end">
                    @if(!$isLocked)
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-6 py-2.5">Simpan</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection