@extends('layouts.admin')
@section('title', 'Tambah Pendaftar Baru')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-4xl mx-auto">
    <div class="mb-6 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800">Tambah Pendaftar Baru</h2>
        <p class="text-sm text-gray-500">Registrasi manual siswa oleh admin SPMB.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 text-green-700 p-4 rounded-lg font-medium border border-green-200">{{ session('success') }}</div>
    @endif

    {{-- Tambahan State Alpine.js untuk Pencarian Sekolah --}}
    <form action="{{ route('admin.pendaftar.store') }}" method="POST" 
          x-data="{ 
              jenisRekomendasi: 'Tidak',
              searchSekolah: '',
              showSekolah: false,
              sekolahList: ['SMPN 1 Depok', 'SMPN 2 Depok', 'MTsN 1 Depok', 'SMP IT Nurul Fikri'], 
              get filteredSekolah() {
                  if (this.searchSekolah === '') return this.sekolahList;
                  return this.sekolahList.filter(s => s.toLowerCase().includes(this.searchSekolah.toLowerCase()));
              }
          }">
        @csrf
        {{-- Tampilkan error validasi --}}
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-red-700">
        <p class="mb-2 font-bold">Terjadi kesalahan:</p>
        <ul class="list-inside list-disc text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
        
        <!-- Input Tersembunyi untuk ID Gelombang -->
        <input type="hidden" name="period_id" value="{{ $period->id ?? 1 }}">
        <input type="hidden" name="spmb_id" value="{{ $spmb_id }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            
            <!-- Data Terkunci -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">ID SPMB</label>
                    <input type="text" value="{{ $spmb_id }}" disabled class="w-full bg-gray-100 border-gray-300 rounded text-gray-600 font-bold cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tahun Pelajaran</label>
                    <input type="text" value="{{ $tahun_pelajaran }}" disabled class="w-full bg-gray-100 border-gray-300 rounded text-gray-600 font-bold cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gelombang</label>
                    <input type="text" value="{{ $period->name ?? 'Gelombang Aktif' }}" disabled class="w-full bg-gray-100 border-gray-300 rounded text-gray-600 font-bold cursor-not-allowed">
                </div>
            </div>

            <!-- Akun & Data Diri -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full border-gray-300 rounded-lg focus:ring-blue-500 p-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required class="w-full border-gray-300 rounded-lg focus:ring-blue-500 p-2.5 text-sm">
            </div>

            <!-- BARU: Asal Sekolah & No WhatsApp -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sekolah Asal (SMP/MTs) <span class="text-red-500">*</span></label>
                <input type="text" name="sekolah_asal" required x-model="searchSekolah" @focus="showSekolah = true" @click.away="showSekolah = false" placeholder="Ketik untuk mencari sekolah..." autocomplete="off" class="w-full border border-gray-300 rounded-lg focus:ring-blue-500 p-2.5 text-sm">
                
                <div x-show="showSekolah" x-cloak class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                    <template x-for="sch in filteredSekolah" :key="sch">
                        <div @click="searchSekolah = sch; showSekolah = false" class="px-4 py-2 cursor-pointer hover:bg-blue-50 text-sm" x-text="sch"></div>
                    </template>
                    <div x-show="searchSekolah !== '' && filteredSekolah.length === 0" @click="showSekolah = false" class="px-4 py-2 cursor-pointer hover:bg-green-50 text-sm text-green-700 font-bold">
                        + Gunakan sekolah baru: "<span x-text="searchSekolah"></span>"
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp <span class="text-red-500">*</span></label>
                <input type="text" name="whatsapp" required placeholder="08123456789" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 p-2.5 text-sm">
            </div>

            <!-- Jalur Masuk & Jurusan -->
            <div class="border-t pt-4 md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Jalur Masuk <span class="text-red-500">*</span></label>
                    <select name="jalur_masuk" required class="border border-gray-300 text-gray-700 text-sm rounded-lg block w-full p-2.5">
                        <option value="">-- Pilih Jalur Masuk --</option>
                        <option value="Reguler">Reguler</option>
                        <option value="Beasiswa">Beasiswa</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Pilihan Jurusan <span class="text-red-500">*</span></label>
                    <select name="pilihan_jurusan" required class="border border-gray-300 text-gray-700 text-sm rounded-lg block w-full p-2.5">
                        <option value="">-- Pilih Jurusan --</option>
                        <option value="TJKT">Teknik Jaringan Komputer dan Telekomunikasi</option>
                        <option value="MPLB">Manajemen Perkantoran dan Layanan Bisnis</option>
                    </select>
                </div>
            </div>

            <!-- Rekomendasi -->
            <div class="md:col-span-2 border-t pt-4">
                <label class="block mb-2 text-sm font-medium text-gray-700">Rekomendasi Dari <span class="text-red-500">*</span></label>
                <div class="flex gap-4 mb-4">
                    <label class="flex items-center"><input type="radio" name="rekomendasi_status" value="Tidak" x-model="jenisRekomendasi" class="mr-2"> Tidak Ada</label>
                    <label class="flex items-center"><input type="radio" name="rekomendasi_status" value="Alumni" x-model="jenisRekomendasi" class="mr-2"> Alumni</label>
                    <label class="flex items-center"><input type="radio" name="rekomendasi_status" value="Siswa Aktif" x-model="jenisRekomendasi" class="mr-2"> Siswa Aktif</label>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200" x-show="jenisRekomendasi === 'Alumni' || jenisRekomendasi === 'Siswa Aktif'" x-cloak>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nama Lengkap <span x-text="jenisRekomendasi"></span> <span class="text-red-500">*</span></label>
                        <input type="text" name="rekomendasi_nama" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                    </div>
                    <div x-show="jenisRekomendasi === 'Siswa Aktif'">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Kelas <span class="text-red-500">*</span></label>
                        <input type="text" name="rekomendasi_kelas" placeholder="Contoh: XII TJKT 1" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                    </div>
                </div>
            </div>

        </div>

        <div class="flex justify-end gap-3 border-t pt-4">
            <button type="reset" class="px-5 py-2.5 bg-gray-100 rounded-lg font-medium text-gray-700 hover:bg-gray-200">Reset</button>
            <button type="submit" class="px-5 py-2.5 bg-blue-600 rounded-lg font-bold text-white hover:bg-blue-700">Simpan Pendaftar</button>
        </div>
    </form>
</div>
@endsection