<?php
// includes/bk_navbar.php
?>
<header class="bg-white shadow-sm z-10 border-b border-gray-200 relative">
    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">

        <!-- Tombol Hamburger untuk Mobile -->
        <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 focus:outline-none lg:hidden">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Judul Halaman dinamis -->
        <div class="flex-1 px-4 lg:px-0 flex justify-between items-center">
            <h2 class="text-xl font-bold text-primary"><?= $page_title ?? 'Dashboard' ?></h2>

            <!-- Profil & Dropdown Menu dengan Alpine.js -->
            <div class="relative" x-data="{ dropdownOpen: false }">
                
                <!-- Tombol Profil (Nama & Foto) -->
                <button @click="dropdownOpen = !dropdownOpen" @click.away="dropdownOpen = false" class="flex items-center gap-3 focus:outline-none p-1 rounded-full hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-200">
                    
                    <!-- Nama Admin -->
                    <span class="text-sm font-semibold text-gray-700 hidden sm:block text-right">
                        <span class="block text-xs text-gray-500 font-normal leading-tight">Halo,</span>
                        <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?>
                    </span>
                    
                    <!-- Foto Profil (Otomatis generate inisial nama) -->
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_nama'] ?? 'Admin BK') ?>&background=0D8ABC&color=fff&bold=true" alt="Profile" class="h-10 w-10 rounded-full border-2 border-primary object-cover shadow-sm">
                    
                    <!-- Ikon Panah Bawah -->
                    <svg class="w-4 h-4 text-gray-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Panel Dropdown -->
                <div x-show="dropdownOpen" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-lg py-1 z-50" 
                     style="display: none;">
                    
                    <!-- Menu Profil -->
                    <a href="profil.php" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-primary transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Profil Saya
                    </a>

                    <!-- Menu Ganti Password -->
                    <a href="ganti_password.php" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-primary transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        Ganti Password
                    </a>

                    <div class="border-t border-gray-100 my-1"></div>

                    <!-- Menu Keluar (Logout) -->
                    <a href="../../auth/logout.php" class="flex items-center gap-3 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Keluar
                    </a>
                </div>

            </div>
        </div>

    </div>
</header>

<!-- Area utama konten yang bisa di-scroll -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50/50">