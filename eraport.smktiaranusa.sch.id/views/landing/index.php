<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Raport - SMK Tiara Nusa</title>
    <!-- Favicon -->
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Flowbite v2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased" x-data="{ mobileMenuOpen: false }">

    <!-- NAVBAR / HEADER -->
    <header class="fixed w-full top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo & Brand -->
                <a href="#" class="flex items-center space-x-3 group">
                    <img src="/assets/img/logo.png" alt="Logo SMK Tiara Nusa" class="w-10 h-10 object-contain transition-transform group-hover:scale-105">
                    <div>
                        <span class="text-xl font-extrabold tracking-tight text-slate-900 block leading-none">e-Raport</span>
                        <span class="text-xs font-bold text-emerald-600 tracking-wider uppercase">SMK Tiara Nusa</span>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                    <a href="#tentang" class="hover:text-emerald-600 transition-colors">Tentang</a>
                    <a href="#alur" class="hover:text-emerald-600 transition-colors">Alur Penggunaan</a>
                    <a href="#hak-akses" class="hover:text-emerald-600 transition-colors">Hak Akses</a>
                </nav>

                <!-- Login Button -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/login" class="px-6 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-full hover:bg-emerald-700 hover:shadow-lg hover:shadow-emerald-200 transition-all duration-300">
                        Masuk Sistem
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-500 hover:text-slate-900 focus:outline-none p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-white border-b border-slate-200 px-4 pt-2 pb-6 space-y-2 absolute w-full shadow-lg">
            <a href="#tentang" @click="mobileMenuOpen = false" class="block py-3 text-slate-600 hover:text-emerald-600 font-semibold border-b border-slate-50">Tentang Aplikasi</a>
            <a href="#alur" @click="mobileMenuOpen = false" class="block py-3 text-slate-600 hover:text-emerald-600 font-semibold border-b border-slate-50">Alur Penggunaan</a>
            <a href="#hak-akses" @click="mobileMenuOpen = false" class="block py-3 text-slate-600 hover:text-emerald-600 font-semibold border-b border-slate-50">Hak Akses</a>
            <div class="pt-4">
                <a href="/login" class="block text-center w-full py-3 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md">
                    Masuk Sistem
                </a>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28 bg-gradient-to-b from-emerald-50/50 via-white to-slate-50">
        <!-- Dekorasi Background -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-72 h-72 rounded-full bg-emerald-100/50 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-blue-50/60 blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight mb-8">
                    Manajemen Laporan Hasil Belajar <br class="hidden sm:block"/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500">Lebih Cepat & Akurat</span>
                </h1>
                <p class="text-lg text-slate-600 mb-10 leading-relaxed max-w-2xl mx-auto">
                    Platform e-Raport terintegrasi untuk memudahkan Administrator, Wali Kelas, dan Guru Mapel dalam mengelola asesmen dan mencetak rapor peserta didik secara efisien.
                </p>
                <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="/login" class="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-white bg-emerald-600 rounded-full hover:bg-emerald-700 shadow-lg hover:shadow-emerald-300/50 transition-all hover:-translate-y-0.5 text-center">
                        Mulai Gunakan Sistem
                    </a>
                    <a href="#alur" class="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-slate-700 bg-white border border-slate-200 rounded-full hover:bg-slate-50 hover:border-slate-300 transition-all text-center shadow-sm">
                        Pelajari Cara Kerja
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- TENTANG & BENEFIT SECTION -->
    <section id="tentang" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-6">
                        Solusi Digital Manajemen Penilaian Sekolah Terpadu
                    </h2>
                    <p class="text-slate-600 leading-relaxed mb-8 text-lg">
                        e-Raport SMK Tiara Nusa dikembangkan khusus untuk mendukung operasional akademik sekolah. Dengan pendekatan digital, proses rekapitulasi nilai yang sebelumnya memakan waktu kini dapat diselesaikan dengan kolaborasi langsung dalam satu platform.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mt-1">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-bold text-slate-900">Efisien & Cepat</h4>
                                <p class="text-slate-600 mt-1">Mendukung import nilai massal menggunakan format Excel (.xlsx), memangkas waktu input data secara signifikan.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mt-1">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-bold text-slate-900">Aman & Terpusat</h4>
                                <p class="text-slate-600 mt-1">Seluruh data tersimpan terpusat dengan sistem enkripsi dan pembatasan hak akses sesuai peran pengguna.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gambar/Ilustrasi Representatif -->
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-emerald-100 to-teal-50 rounded-3xl transform rotate-3 scale-105 -z-10"></div>
                    <img src="/assets/img/image.jpeg" alt="Kegiatan Belajar" class="rounded-3xl shadow-xl object-cover h-[450px] w-full">
                </div>
            </div>
        </div>
    </section>

    <!-- ALUR PENGGUNAAN (WORKFLOW) -->
    <section id="alur" class="py-20 bg-slate-900 text-white relative overflow-hidden">
        <!-- Dekorasi Background -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-600/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-emerald-400 font-bold tracking-wider uppercase text-sm">Cara Kerja</span>
                <h2 class="text-3xl font-extrabold tracking-tight mt-2 mb-4">Alur Penggunaan Sistem</h2>
                <p class="text-slate-400">Tiga langkah mudah bagaimana sistem e-Raport memproses data dari awal hingga pencetakan dokumen.</p>
            </div>

            <div class="relative">
                <!-- Garis Penghubung (Hanya muncul di desktop) -->
                <div class="hidden md:block absolute top-12 left-[15%] right-[15%] h-0.5 bg-gradient-to-r from-emerald-500/20 via-emerald-500 to-emerald-500/20 z-0"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Step 1 -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="w-24 h-24 bg-slate-800 border-4 border-slate-900 rounded-full flex items-center justify-center mb-6 shadow-xl shadow-slate-900/50">
                            <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">1. Persiapan Data</h3>
                        <p class="text-slate-400 text-sm leading-relaxed px-4">Administrator mendaftarkan data siswa, guru, pembagian rombongan belajar (kelas), dan menugaskan mata pelajaran.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="w-24 h-24 bg-emerald-600 border-4 border-slate-900 rounded-full flex items-center justify-center mb-6 shadow-xl shadow-emerald-900/50">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">2. Input Penilaian</h3>
                        <p class="text-slate-400 text-sm leading-relaxed px-4">Guru mata pelajaran masuk ke sistem untuk memasukkan nilai formatif/sumatif dan capaian kompetensi siswa.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="w-24 h-24 bg-slate-800 border-4 border-slate-900 rounded-full flex items-center justify-center mb-6 shadow-xl shadow-slate-900/50">
                            <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">3. Cetak Raport</h3>
                        <p class="text-slate-400 text-sm leading-relaxed px-4">Wali Kelas memeriksa rekapitulasi nilai, memasukkan absensi, dan mencetak dokumen raport siswa.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HAK AKSES SECTION -->
    <section id="hak-akses" class="py-20 bg-slate-50 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-4">Fitur Berdasarkan Hak Akses</h2>
                <p class="text-slate-600">Sistem dirancang dengan pembagian hak akses (Role-Based Access) yang spesifik untuk menjaga integritas data dan efisiensi kerja.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1: Admin -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:border-emerald-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Panel Administrator</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Memiliki kendali penuh untuk mengelola Pengaturan Master Sekolah, Manajemen Akun (Guru/Siswa), Data Kelas, dan Mata Pelajaran.
                    </p>
                </div>

                <!-- Feature 2: Wali Kelas -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:border-emerald-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Wali Kelas Terpadu</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Memantau rekapitulasi nilai kelas binaan, mengelola data absensi siswa, mengunggah tanda tangan digital, serta finalisasi cetak raport.
                    </p>
                </div>

                <!-- Feature 3: Guru Mapel -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:border-emerald-100 transition-all duration-300 group">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Input Nilai Mapel</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Fokus pada tugas pengajaran. Guru dapat dengan mudah menginput dan memperbarui nilai serta deskripsi capaian kompetensi siswa di kelas yang diampu.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-sm text-slate-500">
            <div class="flex items-center space-x-2 mb-4 md:mb-0">
                <img src="/assets/img/logo.png" alt="Logo" class="w-6 h-6 grayscale opacity-50">
                <p>&copy; <?= date('Y') ?> e-Raport SMK Tiara Nusa. Hak Cipta Dilindungi.</p>
            </div>
            <p class="font-medium text-slate-600">Platform Digital Akademik</p>
        </div>
    </footer>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>

</html>