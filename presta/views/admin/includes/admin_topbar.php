<header class="bg-white border-b border-gray-100 h-[70px] px-6 flex items-center justify-between sticky top-0 z-30 shadow-sm w-full">

    <!-- Tombol Menu Mobile -->
    <div class="flex items-center">
        <!-- Memanggil JS murni untuk toggle -->
        <button onclick="toggleSidebar(event)" class="p-2 -ml-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors focus:outline-none lg:hidden flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <span class="text-sm font-bold text-gray-800 lg:hidden ml-2">PRESTA ADMIN</span>
    </div>

    <!-- Kanan: Profil Dropdown -->
    <div class="relative flex items-center ml-auto" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">

        <button @click="profileOpen = !profileOpen" class="flex items-center gap-3 focus:outline-none cursor-pointer group p-1.5 rounded-full hover:bg-gray-50 transition-colors">
            <span class="text-gray-700 font-semibold text-sm hidden sm:inline-block">
                <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin'); ?>
            </span>
            <div class="w-10 h-10 bg-brand-600 rounded-full text-white flex items-center justify-center font-bold text-sm shadow-sm group-hover:bg-brand-700 transition-colors">
                <?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'A', 0, 1)); ?>
            </div>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="profileOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-3"
            style="display: none;"
            class="absolute right-0 top-14 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">

            <div class="p-4 bg-slate-50 border-b border-gray-100">
                <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Login sebagai</p>
                <p class="text-sm font-bold text-gray-900 truncate mt-0.5">
                    <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Administrator') ?>
                </p>
            </div>

            <div class="p-1.5 space-y-0.5 font-medium text-xs">
                <a href="index.php?route=admin/ubah_password" class="flex items-center gap-2.5 px-3.5 py-2.5 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors group">
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-brand-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <span>Ubah Password</span>
                </a>

                <a href="index.php?route=auth/logout" class="flex items-center gap-2.5 px-3.5 py-2.5 text-red-600 rounded-xl hover:bg-red-50 transition-colors group">
                    <svg class="w-4 h-4 text-red-500 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
    </div>
</header>