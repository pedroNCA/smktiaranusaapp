<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SPMB - SMK Tiara Nusa')</title>
    <!-- Vite: Memanggil Tailwind CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased font-sans flex flex-col min-h-screen">

    <!-- Navbar Utama -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-20 top-0 left-0 shadow-sm">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            
            <!-- Kiri: Logo & Nama Sekolah -->
            <a href="{{ route('spmb.beranda') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img src="{{ asset('logo.png') }}" class="h-10 w-auto" alt="Logo SMK Tiara Nusa">
                <span class="self-center text-2xl font-extrabold whitespace-nowrap text-blue-700 tracking-tight">SMK Tiara Nusa</span>
            </a>
            
            <!-- Kanan: Tombol Toggle Mobile (Burger Menu) -->
            <div class="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                <!-- Tombol Login/Dashboard Khusus Desktop (Tampil di layar medium ke atas) -->
                <div class="hidden md:block">
                    @auth
                        <a href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('student.dashboard') }}" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-6 py-2.5 text-center transition-all shadow-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-6 py-2.5 text-center transition-all shadow-sm">Login</a>
                    @endauth
                </div>

                <button data-collapse-toggle="navbar-sticky" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200" aria-controls="navbar-sticky" aria-expanded="false">
                    <span class="sr-only">Buka menu utama</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                    </svg>
                </button>
            </div>

            <!-- Tengah: Menu Navigasi (Termasuk tombol Login/Dashboard saat tampilan Mobile) -->
            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-sticky">
                <ul class="flex flex-col p-4 md:p-0 mt-4 font-medium border border-gray-100 rounded-lg bg-gray-50 md:space-x-6 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white">
                    <li><a href="{{ route('spmb.beranda') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Beranda</a></li>
                    <li><a href="{{ route('spmb.jurusan') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Jurusan</a></li>
                    <li><a href="{{ route('spmb.jadwal') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Jadwal</a></li>
                    <!--<li><a href="{{ route('spmb.biaya') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Biaya</a></li>-->
                    <li><a href="{{ route('spmb.persyaratan') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Syarat</a></li>
                    <li><a href="{{ route('spmb.alur') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Alur</a></li>
                    <li><a href="{{ route('spmb.faq') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">FAQ</a></li>
                    <li><a href="{{ route('spmb.kontak') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-600 md:p-0 transition-colors">Kontak</a></li>
                    
                    <!-- Tombol Login/Dashboard Khusus Mobile (Muncul di dalam dropdown menu HP) -->
                    <li class="pt-2 mt-2 border-t border-gray-200 md:hidden">
                        @auth
                            <a href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('student.dashboard') }}" class="block w-full py-2.5 px-3 text-center text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="block w-full py-2.5 px-3 text-center text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors">Login</a>
                        @endauth
                    </li>
                </ul>
            </div>
            
        </div>
    </nav>

    <!-- Konten Dinamis -->
    <main class="mt-20 flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white rounded-lg shadow m-4 mt-auto border-t">
        <div class="w-full mx-auto max-w-screen-xl p-6 md:flex md:items-center md:justify-between">
            <span class="text-sm text-gray-500 sm:text-center">© {{ date('Y') }} <a href="{{ route('spmb.beranda') }}" class="hover:underline text-blue-600">SMK Tiara Nusa</a>. Hak Cipta Dilindungi.</span>
            <ul class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 sm:mt-0">
                <li><a href="{{ route('spmb.informasi') }}" class="hover:underline me-4 md:me-6">Tentang SPMB</a></li>
                <li><a href="{{ route('spmb.persyaratan') }}" class="hover:underline me-4 md:me-6">Kebijakan Privasi</a></li>
                <li><a href="{{ route('spmb.kontak') }}" class="hover:underline">Bantuan / Kontak</a></li>
            </ul>
        </div>
    </footer>

    <!-- Script Flowbite -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>