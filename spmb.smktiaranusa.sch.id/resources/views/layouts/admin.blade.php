<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel - SPMB SMK Tiara Nusa')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- x-cloak style -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 antialiased">

    <!-- Navbar Admin -->
    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 fixed left-0 right-0 top-0 z-50">
        <div class="flex flex-wrap justify-between items-center">
            <div class="flex justify-start items-center">
                <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation" aria-controls="drawer-navigation" class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between mr-4">
                    <img src="{{ asset('logo.png') }}" class="mr-3 h-8" alt="Logo" onerror="this.src='https://ui-avatars.com/api/?name=SPMB&background=0D8ABC&color=fff'" />
                    <span class="self-center text-xl font-bold whitespace-nowrap text-blue-700">SPMB Admin</span>
                </a>
            </div>
            
            <div class="flex items-center lg:order-2">
                <!-- Dropdown Profil Trigger -->
                <button type="button" class="flex items-center gap-3 focus:ring-4 focus:ring-gray-100 rounded-full p-1" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                    <span class="sr-only">Buka menu pengguna</span>
                    <span class="hidden md:block text-sm font-medium text-gray-700">{{ Auth::user()->name ?? 'Admin' }}</span>
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                        {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                    </div>
                </button>

                <!-- Dropdown Profil Menu -->
                <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-xl shadow-lg border border-gray-100 min-w-[200px]" id="dropdown-user">
                    <div class="px-4 py-3" role="none">
                        <p class="text-sm font-bold text-gray-900" role="none">{{ Auth::user()->name ?? 'Administrator' }}</p>
                        <p class="text-sm font-medium text-gray-500 truncate" role="none">{{ Auth::user()->email ?? 'admin@smktiaranusa.sch.id' }}</p>
                    </div>
                    <ul class="py-2" role="none">
                        <li><a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-600 transition-colors">Profil Saya</a></li>
                        <li><a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-600 transition-colors">Ganti Password</a></li>
                    </ul>
                    <div class="py-2">
                        <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold transition-colors">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside id="drawer-navigation" class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0" aria-label="Sidenav">
        <div class="overflow-y-auto py-5 px-3 h-full bg-white">
            
            @php
    // Logika Deteksi URL Sidebar
    $isPendaftaranActive = request()->routeIs('admin.pendaftar.*', 'admin.verifikasi.*', 'admin.akun-siswa.*');
    $isMasterActive = request()->routeIs('registration-periods.*', 'admin.schedules.*', 'admin.requirements.*', 'admin.costs.*');
    $isObservasiActive = request()->routeIs('admin.observasi.*', 'admin.tpa.*'); // <-- TAMBAHAN: Deteksi menu Observasi & TPA
    $isInfoActive = request()->routeIs('admin.faqs.*', 'admin.contact.*');
    $isLaporanActive = request()->routeIs('admin.laporan.*');
    $isSistemActive = request()->routeIs('admin.sistem.*');
@endphp

            <ul class="space-y-2">
    <!-- Menu Dashboard -->
    <li>
        <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 text-base font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700 group' : 'text-gray-900 hover:bg-gray-100 group' }}">
            <svg aria-hidden="true" class="w-6 h-6 transition duration-75 {{ request()->routeIs('admin.dashboard') ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-900' }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path></svg>
            <span class="ml-3 {{ request()->routeIs('admin.dashboard') ? 'font-bold' : '' }}">Dashboard</span>
        </a>
    </li>

    <!-- Grup: Pendaftaran Mahasiswa -->
    <li>
        <button type="button" class="flex items-center w-full p-2 text-base font-medium text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 {{ $isPendaftaranActive ? 'bg-gray-100' : '' }}" aria-controls="dropdown-pendaftaran" data-collapse-toggle="dropdown-pendaftaran">
            <svg aria-hidden="true" class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">Proses Pendaftaran</span>
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
        <ul id="dropdown-pendaftaran" class="{{ $isPendaftaranActive ? '' : 'hidden' }} py-2 space-y-2">
            <li>
    <a href="{{ route('admin.pendaftar.index') }}"
       class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.pendaftar.index') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
        Data Pendaftar
    </a>
</li>
            <li>
                <a href="{{ route('admin.verifikasi.pembayaran') }}" class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.verifikasi.pembayaran') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Verifikasi Bayar</a>
            </li>
            <li>
    <a href="{{ route('admin.verifikasi.berkas') }}"
       class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.verifikasi.berkas') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
        Verifikasi Berkas
    </a>
</li>

<li>
    <a href="{{ route('admin.pendaftar.create') }}"
       class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.pendaftar.create') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
        Tambah Pendaftar
    </a>
</li>
            <li>
                <a href="{{ route('admin.akun-siswa.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.akun-siswa.index') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Data Akun Siswa</a>
            </li>
            <li>
                <a href="{{ route('admin.akun-siswa.pending') }}" class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.akun-siswa.pending') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Belum Isi Formulir</a>
            </li>
        </ul>
    </li>

<!-- Cetak Formulir -->
    <li>
        <a href="{{ route('admin.cetak-formulir.index') }}" class="flex items-center p-2 text-base font-medium rounded-lg transition-colors {{ request()->routeIs('admin.cetak-formulir.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-900 hover:bg-gray-100' }}">
            <svg class="w-6 h-6 transition duration-75 {{ request()->routeIs('admin.cetak-formulir.*') ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <span class="ml-3">Cetak Formulir</span>
        </a>
    </li>

    <!-- ========================================== -->
    <!-- GRUP BARU: OBSERVASI & TPA (CBT)          -->
    <!-- ========================================== -->
    <li>
        <button type="button" class="flex items-center w-full p-2 text-base font-medium text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 {{ $isObservasiActive ? 'bg-gray-100' : '' }}" aria-controls="dropdown-observasi" data-collapse-toggle="dropdown-observasi">
            <svg aria-hidden="true" class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-6 9l2 2 4-4"></path></svg>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">Observasi & TPA</span>
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
        <ul id="dropdown-observasi" class="{{ $isObservasiActive ? '' : 'hidden' }} py-2 space-y-2">
            <li>
                <a href="{{ route('admin.observasi.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.observasi.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Status Observasi</a>
            </li>
            <li>
                <a href="{{ route('admin.tpa.soal.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.tpa.soal.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Kelola Soal TPA</a>
            </li>
            <li>
                <a href="{{ route('admin.tpa.settings.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.tpa.settings.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Pengaturan Waktu CBT</a>
            </li>
            <li>
                <a href="{{ route('admin.tpa.hasil.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.tpa.hasil.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Hasil Tes TPA</a>
            </li>
        </ul>
    </li>

    <!-- Grup: Master Data / Pengaturan SPMB -->
    <li>
        <button type="button" class="flex items-center w-full p-2 text-base font-medium text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 {{ $isMasterActive ? 'bg-gray-100' : '' }}" aria-controls="dropdown-master" data-collapse-toggle="dropdown-master">
            <svg aria-hidden="true" class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">Pengaturan SPMB</span>
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
        <ul id="dropdown-master" class="{{ $isMasterActive ? '' : 'hidden' }} py-2 space-y-2">
            <li>
                <a href="{{ route('registration-periods.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('registration-periods.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Gelombang Pendaftaran</a>
            </li>
            <li>
                <a href="{{ route('admin.schedules.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.schedules.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Jadwal Kegiatan</a>
            </li>
            <li>
                <a href="{{ route('admin.requirements.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.requirements.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Persyaratan</a>
            </li>
            <li>
                <a href="{{ route('admin.costs.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.costs.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Rincian Biaya</a>
            </li>
        </ul>
    </li>

<!-- Laporan Dropdown -->
    <li>
        <button type="button" class="flex items-center w-full p-2 text-base font-medium text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 {{ request()->routeIs('admin.laporan.*', 'admin.asal-sekolah.*') ? 'bg-gray-100' : '' }}" aria-controls="dropdown-laporan" data-collapse-toggle="dropdown-laporan">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">Laporan & Rekap</span>
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
        <ul id="dropdown-laporan" class="{{ request()->routeIs('admin.laporan.*', 'admin.asal-sekolah.*') ? '' : 'hidden' }} py-2 space-y-2">
            <li>
                <a href="{{ route('admin.laporan.rekapitulasi') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.laporan.rekapitulasi') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Rekapitulasi Pendaftar</a>
            </li>
            <li>
                <a href="{{ route('admin.asal-sekolah.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.asal-sekolah.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Data Asal Sekolah</a>
            </li>
            <li>
                <a href="{{ route('admin.laporan.excel') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 text-gray-700 hover:bg-gray-100">Download Excel Pendaftar</a>
            </li>
        </ul>
    </li>

    <!-- Grup: Konten Publik / Informasi -->
    <li>
        <button type="button" class="flex items-center w-full p-2 text-base font-medium text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 {{ $isInfoActive ? 'bg-gray-100' : '' }}" aria-controls="dropdown-informasi" data-collapse-toggle="dropdown-informasi">
            <svg aria-hidden="true" class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path></svg>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">Pusat Informasi</span>
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
        <ul id="dropdown-informasi" class="{{ $isInfoActive ? '' : 'hidden' }} py-2 space-y-2">
            <li>
                <a href="{{ route('admin.faqs.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.faqs.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Kelola FAQ</a>
            </li>
            <li>
                <a href="{{ route('admin.contact.index') }}" class="flex items-center w-full p-2 text-sm font-medium transition duration-75 rounded-lg pl-11 {{ request()->routeIs('admin.contact.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Pengaturan Kontak</a>
            </li>
        </ul>
    </li>

    <!-- Grup: Pengaturan Sistem -->
    <li>
        <button type="button" class="flex items-center w-full p-2 text-base font-medium text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 {{ $isSistemActive ? 'bg-gray-100' : '' }}" aria-controls="dropdown-sistem" data-collapse-toggle="dropdown-sistem">
            <svg class="w-6 h-6 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">Pengaturan Sistem</span>
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
        <ul id="dropdown-sistem" class="{{ $isSistemActive ? '' : 'hidden' }} py-2 space-y-2">
            <li>
                <a href="{{ route('admin.sistem.manajemen-user') }}" class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.sistem.manajemen-user') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Manajemen User</a>
            </li>
            <li>
                <a href="{{ route('admin.sistem.audit-log') }}" class="flex items-center w-full p-2 text-sm font-medium transition rounded-lg pl-11 {{ request()->routeIs('admin.sistem.audit-log') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-700 hover:bg-gray-100' }}">Audit Log</a>
            </li>
        </ul>
    </li>
</ul>
        </div>
    </aside>

    <!-- Konten Utama -->
    <main class="p-4 md:ml-64 h-auto pt-20">
        @yield('content')
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>