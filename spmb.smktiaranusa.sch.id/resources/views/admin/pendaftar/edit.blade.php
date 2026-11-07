@extends('layouts.admin')
@section('title', 'Edit Pendaftar')

@section('content')
<div class="mb-6 flex justify-between items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Data Pendaftar</h1>
        <p class="text-gray-500 text-sm mt-1">ID SPMB: <span class="font-bold text-blue-600">{{ $registration->spmb_id }}</span></p>
    </div>
    <a href="{{ route('admin.pendaftar.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
        Kembali
    </a>
</div>

<form action="{{ route('admin.pendaftar.update', $registration->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- SECTION 1: DATA DIRI --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Identitas Peserta Didik</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $registration->user->name ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">No WhatsApp / Telepon</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $registration->user->whatsapp ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">NISN</label>
                <input type="text" name="nisn" value="{{ old('nisn', $student->nisn) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">NIK</label>
                <input type="text" name="nik" value="{{ old('nik', $student->nik) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Asal Sekolah</label>
                <input type="text" name="school_origin" value="{{ old('school_origin', $student->school_origin) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div class="flex gap-4">
                <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Tempat Lahir</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place', $student->birth_place) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Lahir</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            </div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Kelamin</label>
                <select name="gender" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border">
                    <option value="L" {{ $student->gender == 'L' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="P" {{ $student->gender == 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Agama</label>
                <input type="text" name="religion" value="{{ old('religion', $student->religion) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div class="flex gap-4">
                <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Anak Ke</label>
                    <input type="number" name="child_number" value="{{ old('child_number', $student->child_number) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Dari Saudara</label>
                    <input type="number" name="siblings_count" value="{{ old('siblings_count', $student->siblings_count) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            </div>
            <div class="flex items-center pt-5">
                <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="student_special_needs" value="1" {{ $student->special_needs ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Siswa Berkebutuhan Khusus
                </label>
            </div>
        </div>
    </div>

    {{-- SECTION 2: ALAMAT --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Alamat Tempat Tinggal</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 flex gap-4">
                <!-- PERBAIKAN: Mengganti name="street" menjadi name="district" dan value dari $address->street menjadi $address->district -->
                <div class="w-2/3"><label class="block text-xs font-semibold text-gray-600 mb-1">Dusun / Jalan</label>
                    <input type="text" name="district" value="{{ old('district', $address->district ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                <div class="w-1/6"><label class="block text-xs font-semibold text-gray-600 mb-1">RT</label>
                    <input type="text" name="rt" value="{{ old('rt', $address->rt ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                <div class="w-1/6"><label class="block text-xs font-semibold text-gray-600 mb-1">RW</label>
                    <input type="text" name="rw" value="{{ old('rw', $address->rw ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            </div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Kelurahan / Desa</label>
                <input type="text" name="village" value="{{ old('village', $address->village ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan</label>
                <input type="text" name="subdistrict" value="{{ old('subdistrict', $address->subdistrict ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Kabupaten / Kota</label>
                <input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            <div class="flex gap-4">
                <div class="w-2/3"><label class="block text-xs font-semibold text-gray-600 mb-1">Provinsi</label>
                    <input type="text" name="province" value="{{ old('province', $address->province ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                <div class="w-1/3"><label class="block text-xs font-semibold text-gray-600 mb-1">Kode Pos</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code ?? '') }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: ORANG TUA --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Data Orang Tua / Wali</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['title' => 'Data Ayah', 'key' => 'ayah', 'model' => $father],
                ['title' => 'Data Ibu', 'key' => 'ibu', 'model' => $mother],
                ['title' => 'Data Wali', 'key' => 'wali', 'model' => $guardian]
            ] as $parent)
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <h3 class="font-bold text-gray-800 mb-3">{{ $parent['title'] }}</h3>
                <div class="space-y-3">
                    <div><label class="block text-xs text-gray-600">Nama</label>
                        <input type="text" name="{{ $parent['key'] }}_name" value="{{ old($parent['key'].'_name', $parent['model']->name ?? '') }}" class="w-full text-sm border-gray-300 rounded p-2 border"></div>
                    <div><label class="block text-xs text-gray-600">Tahun Lahir</label>
                        <input type="number" name="{{ $parent['key'] }}_birth_year" value="{{ old($parent['key'].'_birth_year', $parent['model']->birth_year ?? '') }}" class="w-full text-sm border-gray-300 rounded p-2 border"></div>
                    <div><label class="block text-xs text-gray-600">Pendidikan</label>
                        <input type="text" name="{{ $parent['key'] }}_education" value="{{ old($parent['key'].'_education', $parent['model']->education ?? '') }}" class="w-full text-sm border-gray-300 rounded p-2 border"></div>
                    <div><label class="block text-xs text-gray-600">Pekerjaan</label>
                        <input type="text" name="{{ $parent['key'] }}_occupation" value="{{ old($parent['key'].'_occupation', $parent['model']->occupation ?? '') }}" class="w-full text-sm border-gray-300 rounded p-2 border"></div>
                    <div><label class="block text-xs text-gray-600">Penghasilan Bulanan</label>
                        <input type="text" name="{{ $parent['key'] }}_monthly_income" value="{{ old($parent['key'].'_monthly_income', $parent['model']->monthly_income ?? '') }}" placeholder="Contoh: 2.000.000 - 4.999.999" class="w-full text-sm border-gray-300 rounded p-2 border"></div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700">
                            <input type="checkbox" name="{{ $parent['key'] }}_special_needs" value="1" {{ isset($parent['model']->special_needs) && $parent['model']->special_needs ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                            Berkebutuhan Khusus
                        </label>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- SECTION 4: PERIODIK & JURUSAN --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Data Periodik & Jurusan</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Tinggi Badan (Cm)</label>
                        <input type="number" name="height" value="{{ old('height', $student->height) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                    <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Berat Badan (Kg)</label>
                        <input type="number" name="weight" value="{{ old('weight', $student->weight) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                </div>
                <div class="flex gap-4">
                    <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Waktu Tempuh (Menit)</label>
                        <input type="number" name="time_to_school" value="{{ old('time_to_school', $student->time_to_school) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                    <div class="w-1/2"><label class="block text-xs font-semibold text-gray-600 mb-1">Jarak Tempuh (Km)</label>
                        <input type="number" step="0.1" name="distance_to_school" value="{{ old('distance_to_school', $student->distance_to_school) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Transportasi ke Sekolah</label>
                    <input type="text" name="transportation" value="{{ old('transportation', $student->transportation) }}" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border"></div>
            </div>
            
            <div class="space-y-4 bg-blue-50 p-4 rounded-xl border border-blue-100">
                <div><label class="block text-xs font-bold text-blue-800 mb-1">Pilihan Jurusan</label>
                    <select name="major" class="w-full text-sm border-blue-300 rounded-lg p-2.5 border">
                        <option value="TJKT" {{ $registration->major == 'TJKT' ? 'selected' : '' }}>TJKT - Teknik Jaringan Komputer</option>
                        <option value="MPLB" {{ $registration->major == 'MPLB' ? 'selected' : '' }}>MPLB - Manajemen Perkantoran</option>
                    </select>
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Rekomendasi (Siswa/Tinus)</label>
                    <input type="text" name="recommendation_name" value="{{ old('recommendation_name', $registration->recommendation_name) }}" placeholder="Nama Siswa" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border mb-2">
                    <input type="text" name="recommendation_class" value="{{ old('recommendation_class', $registration->recommendation_class) }}" placeholder="Kelas Siswa" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border">
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Rekomendasi (Alumni)</label>
                    <input type="text" name="recommendation_alumni" value="{{ old('recommendation_alumni', $registration->recommendation_alumni) }}" placeholder="Nama Alumni" class="w-full text-sm border-gray-300 rounded-lg p-2.5 border">
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 5: UPLOAD DOKUMEN --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Manajemen Dokumen</h2>
        <p class="text-xs text-gray-500 mb-4">Upload file PDF/JPG/PNG baru di sini jika murid belum menguploadnya (atau jika admin ingin mengganti berkas yang ditolak). Biarkan kosong jika tidak ingin mengubah.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(['NISN', 'Akta_Lahir', 'Kartu_Keluarga', 'SKL_Ijazah'] as $doc)
                @php 
                    $existingDoc = $registration->documents->where('type', $doc)->first(); 
                @endphp
                <div class="border rounded-lg p-3 {{ $existingDoc ? 'border-green-300 bg-green-50' : 'border-gray-300 bg-gray-50' }}">
                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ str_replace('_', ' ', $doc) }}</label>
                    
                    @if($existingDoc)
                        <span class="inline-block text-xs font-bold text-green-700 mb-2 bg-green-100 px-2 py-1 rounded">✓ File sudah ada</span>
                    @else
                        <span class="inline-block text-xs font-bold text-red-600 mb-2 bg-red-100 px-2 py-1 rounded">✗ Belum upload</span>
                    @endif
                    
                    <input type="file" name="doc_{{ $doc }}" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white">
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end gap-3 mb-10">
        <button type="reset" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Reset Form</button>
        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">Simpan Perubahan</button>
    </div>
</form>
@endsection