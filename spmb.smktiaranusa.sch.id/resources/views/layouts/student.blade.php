<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Portal Siswa SPMB</title>
    <!-- Tailwind & Alpine JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- SweetAlert2 untuk Notifikasi Pop-up -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .card-3d { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 8px 0 0 #e5e7eb; transition: transform 0.2s, box-shadow 0.2s; }
        .card-3d:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 0 0 #d1d5db; }
        .menu-item { transition: all 0.3s ease; }
        .menu-item:hover { transform: translateX(8px); }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-gray-800 font-sans antialiased flex h-screen w-full overflow-hidden" x-data="{ sidebarOpen: false }">

    <!-- OVERLAY BACKGROUND -->
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 md:hidden" @click="sidebarOpen = false" style="display: none;"></div>

    <!-- SIDEBAR -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 w-72 bg-white border-r border-gray-200 flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-50 transition-transform duration-300 ease-in-out md:relative md:translate-x-0">
        <!-- Logo Area -->
        <div class="h-16 md:h-20 flex items-center justify-between px-6 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-indigo-700 shrink-0">
            <div class="flex items-center gap-3">
                <div class="bg-white p-1.5 rounded-lg shadow-md transform rotate-3 flex items-center justify-center">
                    <img src="{{ asset('logo.png') }}" alt="Logo" class="w-7 h-7 object-contain">
                </div>
                <h1 class="text-lg md:text-xl font-black text-white tracking-wider">SPMB ONLINE</h1>
            </div>
            <button @click="sidebarOpen = false" class="text-white hover:text-gray-200 md:hidden focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Menu Links -->
        <div class="flex-1 overflow-y-auto p-4 space-y-1">
            <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 mt-4">Menu Utama</p>
            
            <a href="{{ route('student.dashboard') }}" class="menu-item flex items-center p-3 rounded-xl {{ request()->routeIs('student.dashboard') ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>

            <a href="{{ route('student.formulir.data-pendaftaran') }}" class="menu-item flex items-center p-3 rounded-xl {{ request()->routeIs('student.formulir.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Isi Formulir
            </a>

            <a href="{{ route('student.pembayaran.informasi') }}" class="menu-item flex items-center p-3 rounded-xl {{ request()->routeIs('student.pembayaran.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zM7 19h1a2 2 0 100-4H7v4z"></path></svg>
                Pembayaran
            </a>

            @php
                $user = Auth::user();
                $reg = \App\Models\Registration::where('user_id', $user->id)->first();
                // PERBAIKAN: Menambahkan status lanjutan agar menu tidak terkunci kembali setelah upload
                $isPaymentApproved = $reg && in_array($reg->status, ['PAYMENT_APPROVED', 'VERIFIED', 'DOCUMENT_REVIEW', 'ACCEPTED', 'ANNOUNCED']);
            @endphp

            @if($isPaymentApproved)
                <a href="{{ route('student.berkas.upload') }}" class="menu-item flex items-center p-3 rounded-xl {{ request()->routeIs('student.berkas.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Upload Berkas
                </a>
            @else
                <button onclick="Swal.fire('Terkunci', 'Silakan selesaikan formulir dan tunggu verifikasi pembayaran terlebih dahulu.', 'info')" class="w-full menu-item flex items-center p-3 rounded-xl text-gray-400 bg-gray-50/50 cursor-not-allowed">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Upload Berkas 🔒
                </button>
            @endif

            <a href="{{ route('student.pengumuman.hasil') }}" class="menu-item flex items-center p-3 rounded-xl {{ request()->routeIs('student.pengumuman.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                Pengumuman
            </a>
        </div>
    </aside>

    <!-- MAIN LAYOUT -->
    <div class="flex-1 flex flex-col min-w-0 max-w-full bg-slate-50 overflow-hidden">
        
        <!-- TOPBAR -->
        <header class="h-16 md:h-20 bg-white shadow-sm border-b border-gray-200 flex items-center justify-between px-3 md:px-8 z-10 shrink-0">
            <div class="flex items-center gap-2 md:gap-4 flex-1 min-w-0">
                <button @click="sidebarOpen = true" class="p-1.5 md:p-2 text-gray-500 hover:text-blue-600 md:hidden focus:outline-none rounded-lg hover:bg-gray-50 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl font-bold text-gray-800 hidden md:block">@yield('title')</h2>
                <h2 class="text-sm font-bold text-gray-800 md:hidden truncate ml-1">@yield('title')</h2>
            </div>
            
            <div class="flex items-center gap-2 md:gap-6 shrink-0">
                <button onclick="Swal.fire('Informasi', 'Selamat datang di Portal SPMB!', 'info')" class="relative p-1 text-gray-500 hover:text-blue-600 transition-colors">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <span class="absolute top-0 right-0 w-2 h-2 md:w-2.5 md:h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                </button>

                <div class="w-px h-6 md:h-8 bg-gray-200"></div>

                <!-- DROPDOWN PROFIL -->
                <div class="relative" x-data="{ profileOpen: false }">
                    <button @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" class="flex items-center gap-2 md:gap-3 focus:outline-none p-1 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-gray-900 truncate max-w-[120px]">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500">Calon Siswa</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-blue-100 border-2 border-blue-200 flex items-center justify-center text-blue-700 font-bold text-sm md:text-lg shadow-sm shrink-0">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="profileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Menu Dropdown -->
                    <div x-show="profileOpen" x-transition.origin.top.right x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                        <a href="{{ route('student.data-akun') ?? '#' }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Data Akun
                        </a>
                        <a href="{{ route('student.ganti-password') ?? '#' }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            Ganti Password
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- KONTEN HALAMAN -->
        <main class="flex-1 w-full overflow-y-auto overflow-x-hidden p-4 md:p-8">
            <div class="max-w-7xl mx-auto w-full">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Script Notifikasi Global (Pop Up) -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if(session('success')) Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', confirmButtonColor: '#2563eb' }); @endif
            @if(session('error')) Swal.fire({ icon: 'error', title: 'Perhatian!', text: '{{ session('error') }}', confirmButtonColor: '#dc2626' }); @endif
            @if(session('info')) Swal.fire({ icon: 'info', title: 'Informasi', text: '{{ session('info') }}', confirmButtonColor: '#2563eb' }); @endif
        });
    </script>
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>