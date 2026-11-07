@extends('layouts.student')
@section('title', 'Data Diri - SPMB')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style> [x-cloak] { display: none !important; } </style>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" 
     x-data="formulirData()" 
     x-init="initData()" 
     data-sekolah="{{ json_encode($sekolah_list ?? []) }}">
    
    <!-- MENU NAVIGASI CLICKABLE -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex gap-6 overflow-x-auto text-sm font-medium">
        <a href="{{ route('student.formulir.data-pendaftaran') }}" class="text-gray-500 hover:text-blue-600">1. Pendaftaran</a>
        <a href="{{ route('student.formulir.data-diri') }}" class="relative text-blue-700 font-bold border-b-2 border-blue-700 pb-1">
            2. Data Diri
            @if(!\App\Models\Student::where('user_id', Auth::id())->exists()) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>
        <a href="{{ route('student.formulir.data-orang-tua') }}" class="text-gray-500 hover:text-blue-600 relative">
            3. Orang Tua
            @if(!\App\Models\StudentParent::where('student_id', optional($student)->id)->exists()) <span class="absolute -top-1 -right-2 w-2 h-2 bg-red-500 rounded-full"></span> @endif
        </a>
        <a href="{{ route('student.formulir.data-periodik') }}" class="text-gray-500 hover:text-blue-600">4. Periodik</a>
    </div>

    <div class="p-6 md:p-8">
        {{-- LOGIKA KUNCI FORMULIR --}}
        @php
            $registration = \App\Models\Registration::where('user_id', Auth::id())->first();
            $isLocked = $registration && in_array($registration->status, ['VERIFIED', 'DOCUMENT_REVIEW', 'ACCEPTED']);
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
                <button @click="show = false" type="button">&times;</button>
            </div>
        @endif
        
        <!-- ERROR DARI SERVER (Laravel) -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg text-red-700">
                <p class="font-bold">⚠️ Perhatian! Ada data tidak sesuai ketentuan:</p>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h3 class="text-xl font-bold text-gray-900 mb-6">Formulir Data Pribadi Siswa</h3>
        
        <!-- Menggunakan validasi bawaan HTML5 -->
        <form action="{{ route('student.formulir.store-data-diri') }}" method="POST">
            @csrf
            <fieldset {{ $isLocked ? 'disabled' : '' }}>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" value="{{ Auth::user()->name }}" disabled class="bg-gray-100 border border-gray-300 text-gray-700 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Email (Sesuai Akun) <span class="text-red-500">*</span></label>
                        <input type="email" value="{{ Auth::user()->email }}" disabled class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Jenis Kelamin <span class="text-red-500">*</span></label>
                        <select name="jenis_kelamin" required class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            <option value="">-- Pilih --</option>
                            <option value="L" {{ (optional($student)->gender == 'L') ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ (optional($student)->gender == 'P') ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    
                    <div x-data="{ nisn: '{{ old('nisn', $student->nisn ?? '') }}' }">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-gray-700">NISN <span class="text-red-500">*</span></label>
                            <a href="https://nisn.data.kemendikdasmen.go.id/" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline">Cek NISN di sini</a>
                        </div>
                        <input type="text" name="nisn" required x-model="nisn" minlength="10" maxlength="10" pattern="\d{10}" title="NISN harus tepat 10 angka" @input="nisn = nisn.replace(/[^0-9]/g, '')" placeholder="Harus 10 angka" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        <p class="text-xs mt-1" :class="nisn.length === 10 ? 'text-green-600' : 'text-gray-500'" x-text="nisn.length + '/10 digit angka'"></p>
                    </div>

                    <div class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Sekolah Asal (SMP/MTs) <span class="text-red-500">*</span></label>
                        <input type="text" name="sekolah_asal" required x-model="searchSekolah" @focus="showSekolah = true" @click.away="showSekolah = false" placeholder="Ketik untuk mencari sekolah..." autocomplete="off" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        <div x-show="showSekolah" x-cloak class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="sch in filteredSekolah" :key="sch">
                                <div @click="searchSekolah = sch; showSekolah = false" class="px-4 py-2 cursor-pointer hover:bg-blue-50 text-sm" x-text="sch"></div>
                            </template>
                            <div x-show="searchSekolah !== '' && filteredSekolah.length === 0" @click="showSekolah = false" class="px-4 py-2 cursor-pointer hover:bg-green-50 text-sm text-green-700 font-bold">
                                + Gunakan sekolah baru: "<span x-text="searchSekolah"></span>"
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Tempat Lahir <span class="text-red-500">*</span></label>
                        <input type="text" name="tempat_lahir" required x-model="searchLahir" @focus="showLahir = true" @click.away="showLahir = false" placeholder="Ketik nama kota..." autocomplete="off" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        <div x-show="showLahir && searchLahir.length > 0" x-cloak class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="kota in filteredCities" :key="kota">
                                <div @click="searchLahir = kota; showLahir = false" class="px-4 py-2 cursor-pointer hover:bg-blue-50 text-sm" x-text="kota"></div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Tanggal Lahir <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_lahir" required value="{{ old('tanggal_lahir', $student->birth_date ?? '') }}" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Agama <span class="text-red-500">*</span></label>
                        <select name="agama" required class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            <option value="">-- Pilih Agama --</option>
                            <option value="Islam" {{ (optional($student)->religion == 'Islam') ? 'selected' : '' }}>Islam</option>
                            <option value="Kristen" {{ (optional($student)->religion == 'Kristen') ? 'selected' : '' }}>Kristen</option>
                            <option value="Katolik" {{ (optional($student)->religion == 'Katolik') ? 'selected' : '' }}>Katolik</option>
                            <option value="Hindu" {{ (optional($student)->religion == 'Hindu') ? 'selected' : '' }}>Hindu</option>
                            <option value="Buddha" {{ (optional($student)->religion == 'Buddha') ? 'selected' : '' }}>Buddha</option>
                            <option value="Konghucu" {{ (optional($student)->religion == 'Konghucu') ? 'selected' : '' }}>Konghucu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Berkebutuhan Khusus <span class="text-red-500">*</span></label>
                        <select name="berkebutuhan_khusus" required class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            <option value="">-- Pilih --</option>
                            <option value="Tidak" {{ (optional($student)->special_needs == 0 && optional($student)->id != null) ? 'selected' : '' }}>Tidak</option>
                            <option value="Ya" {{ (optional($student)->special_needs == 1) ? 'selected' : '' }}>Ya</option>
                        </select>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-1/2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Anak ke-</label>
                            <input type="number" name="anak_ke" min="1" required value="{{ old('anak_ke', $student->child_number ?? '') }}" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div class="w-1/2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Dari</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="jumlah_saudara" min="1" required value="{{ old('jumlah_saudara', $student->siblings_count ?? '') }}" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <span class="text-sm font-medium text-gray-700 whitespace-nowrap">Bersaudara</span>
                            </div>
                        </div>
                    </div>
                    
                    <div x-data="{ nik: '{{ old('nik', $student->nik ?? '') }}' }">
                        <label class="block mb-2 text-sm font-medium text-gray-700">NIK <span class="text-red-500">*</span></label>
                        <input type="text" name="nik" required x-model="nik" minlength="16" maxlength="16" pattern="\d{16}" title="NIK harus tepat 16 angka" @input="nik = nik.replace(/[^0-9]/g, '')" placeholder="Harus 16 digit NIK" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        <p class="text-xs mt-1" :class="nik.length === 16 ? 'text-green-600' : 'text-gray-500'" x-text="nik.length + '/16 digit angka'"></p>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">No Registrasi Akta Lahir <span class="text-red-500">*</span></label>
                        <input type="text" name="no_akta" required value="{{ old('no_akta', $student->birth_cert_no ?? '') }}" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                    </div>
                </div>

                <!-- CASCADING ALAMAT -->
                <div class="mt-8 border-t pt-6">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Alamat Tempat Tinggal Lengkap</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Provinsi <span class="text-red-500">*</span></label>
                            <input type="hidden" name="provinsi" x-model="selectedProvName">
                            <select x-model="selectedProvId" @change="provinsiChanged($event)" required class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Provinsi --</option>
                                <template x-for="prov in provinces" :key="prov.id">
                                    <option :value="prov.id" x-text="prov.name" :selected="prov.id === selectedProvId"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Kabupaten / Kota <span class="text-red-500">*</span></label>
                            <input type="hidden" name="kabupaten" x-model="selectedKabName">
                            <select x-model="selectedKabId" @change="kabupatenChanged($event)" required :disabled="!selectedProvId" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="">-- Pilih Kabupaten/Kota --</option>
                                <template x-for="kab in kabupatenList" :key="kab.id">
                                    <option :value="kab.id" x-text="kab.name" :selected="kab.id === selectedKabId"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Kecamatan <span class="text-red-500">*</span></label>
                            <input type="hidden" name="kecamatan" x-model="selectedKecName">
                            <select x-model="selectedKecId" @change="kecamatanChanged($event)" required :disabled="!selectedKabId" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="">-- Pilih Kecamatan --</option>
                                <template x-for="kec in kecamatanList" :key="kec.id">
                                    <option :value="kec.id" x-text="kec.name" :selected="kec.id === selectedKecId"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Kelurahan / Desa <span class="text-red-500">*</span></label>
                            <input type="hidden" name="kelurahan" x-model="selectedKelName">
                            <select x-model="selectedKelId" @change="kelurahanChanged($event)" required :disabled="!selectedKecId" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="">-- Pilih Kelurahan/Desa --</option>
                                <template x-for="kel in kelurahanList" :key="kel.id">
                                    <option :value="kel.id" x-text="kel.name" :selected="kel.id === selectedKelId"></option>
                                </template>
                            </select>
                        </div>

						<div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Dusun / Jalan <span class="text-red-500">*</span></label>
                            <input type="text" name="dusun" required value="{{ old('dusun', $address->district ?? '') }}" placeholder="Contoh: Dusun Melati / Jl. Merdeka No. 1" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4 mt-2">
                            <div x-data="{ rt: '{{ old('rt', $address->rt ?? '') }}' }">
                                <label class="block mb-2 text-sm font-medium text-gray-700">RT <span class="text-red-500">*</span></label>
                                <input type="text" name="rt" required x-model="rt" maxlength="3" @input="rt = rt.replace(/[^0-9]/g, '')" placeholder="001" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            </div>
                            <div x-data="{ rw: '{{ old('rw', $address->rw ?? '') }}' }">
                                <label class="block mb-2 text-sm font-medium text-gray-700">RW <span class="text-red-500">*</span></label>
                                <input type="text" name="rw" required x-model="rw" maxlength="3" @input="rw = rw.replace(/[^0-9]/g, '')" placeholder="001" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            </div>
                            <div x-data="{ pos: '{{ old('kode_pos', $address->postal_code ?? '') }}' }">
                                <label class="block mb-2 text-sm font-medium text-gray-700">Kode Pos <span class="text-red-500">*</span></label>
                                <input type="text" name="kode_pos" required x-model="pos" minlength="5" maxlength="5" pattern="\d{5}" title="Kode pos harus tepat 5 angka" @input="pos = pos.replace(/[^0-9]/g, '')" placeholder="xxxxx" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8 flex justify-end">
                @if(!$isLocked)
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-6 py-2.5 transition-colors">Simpan Data</button>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Skrip bawaan tidak perlu diubah, tetap sama --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formulirData', () => ({
            searchSekolah: '{{ old('sekolah_asal', $student->school_origin ?? '') }}',
            showSekolah: false,
            sekolahList: [],
            get filteredSekolah() {
                if (this.searchSekolah === '') return this.sekolahList;
                return this.sekolahList.filter(s => s.toLowerCase().includes(this.searchSekolah.toLowerCase()));
            },

            allCities: [],
            searchLahir: '{{ old('tempat_lahir', $student->birth_place ?? '') }}',
            showLahir: false,
            get filteredCities() {
                if (this.searchLahir === '') return [];
                return this.allCities.filter(c => c.toLowerCase().includes(this.searchLahir.toLowerCase())).slice(0, 10);
            },

            provinces: [], kabupatenList: [], kecamatanList: [], kelurahanList: [],
            selectedProvId: '',  selectedProvName: '{{ old('provinsi', $address->province ?? '') }}',
            selectedKabId: '',   selectedKabName: '{{ old('kabupaten', $address->city ?? '') }}',
            selectedKecId: '',   selectedKecName: '{{ old('kecamatan', $address->subdistrict ?? '') }}',
            selectedKelId: '',   selectedKelName: '{{ old('kelurahan', $address->village ?? '') }}',

            initData() {
                const el = document.querySelector('[data-sekolah]');
                if (el) {
                    try { this.sekolahList = JSON.parse(el.getAttribute('data-sekolah')); } 
                    catch (e) { this.sekolahList = []; }
                }
                this.loadSemuaKotaLahir();
                this.loadAlamatCascading();
            },

            async loadAlamatCascading() {
                try {
                    let resProv = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
                    this.provinces = await resProv.json();
                    
                    if (this.selectedProvName) {
                        let prov = this.provinces.find(p => p.name === this.selectedProvName);
                        if (prov) {
                            this.selectedProvId = prov.id;
                            let resKab = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${this.selectedProvId}.json`);
                            this.kabupatenList = await resKab.json();
                            
                            if (this.selectedKabName) {
                                let kab = this.kabupatenList.find(k => k.name === this.selectedKabName);
                                if (kab) {
                                    this.selectedKabId = kab.id;
                                    let resKec = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${this.selectedKabId}.json`);
                                    this.kecamatanList = await resKec.json();
                                    
                                    if (this.selectedKecName) {
                                        let kec = this.kecamatanList.find(k => k.name === this.selectedKecName);
                                        if (kec) {
                                            this.selectedKecId = kec.id;
                                            let resKel = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${this.selectedKecId}.json`);
                                            this.kelurahanList = await resKel.json();
                                            
                                            if (this.selectedKelName) {
                                                let kel = this.kelurahanList.find(k => k.name === this.selectedKelName);
                                                if (kel) {
                                                    this.selectedKelId = kel.id;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (err) { }
            },

            async provinsiChanged(e) {
                this.selectedProvId = e.target.value;
                this.selectedProvName = e.target.options[e.target.selectedIndex].text;
                this.selectedKabId = ''; this.selectedKabName = '';
                this.selectedKecId = ''; this.selectedKecName = '';
                this.selectedKelId = ''; this.selectedKelName = '';
                this.kabupatenList = []; this.kecamatanList = []; this.kelurahanList = [];
                
                if (this.selectedProvId) {
                    let res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${this.selectedProvId}.json`);
                    this.kabupatenList = await res.json();
                }
            },

            async kabupatenChanged(e) {
                this.selectedKabId = e.target.value;
                this.selectedKabName = e.target.options[e.target.selectedIndex].text;
                this.selectedKecId = ''; this.selectedKecName = '';
                this.selectedKelId = ''; this.selectedKelName = '';
                this.kecamatanList = []; this.kelurahanList = [];
                
                if (this.selectedKabId) {
                    let res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${this.selectedKabId}.json`);
                    this.kecamatanList = await res.json();
                }
            },

            async kecamatanChanged(e) {
                this.selectedKecId = e.target.value;
                this.selectedKecName = e.target.options[e.target.selectedIndex].text;
                this.selectedKelId = ''; this.selectedKelName = '';
                this.kelurahanList = [];
                
                if (this.selectedKecId) {
                    let res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${this.selectedKecId}.json`);
                    this.kelurahanList = await res.json();
                }
            },

            kelurahanChanged(e) {
                this.selectedKelId = e.target.value;
                this.selectedKelName = e.target.options[e.target.selectedIndex].text;
            },

            async loadSemuaKotaLahir() {
                try {
                    const res = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
                    const provs = await res.json();
                    const promises = provs.map(p => fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${p.id}.json`).then(r => r.json()));
                    const allData = await Promise.all(promises);
                    this.allCities = allData.flat().map(k => k.name);
                } catch (e) {}
            }
        }))
    })
</script>
@endsection