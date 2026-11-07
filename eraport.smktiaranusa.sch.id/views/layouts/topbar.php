<header class="flex items-center justify-between h-20 px-6 bg-white border-b border-gray-200 z-40 lg:px-8 shrink-0" 
        x-data="{ dropdownOpen: false, changePasswordOpen: false, oldPassword: '', newPassword: '', confirmPassword: '', showOld: false, showNew: false, showConfirm: false }">
    <div class="flex items-center">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-emerald-600 focus:outline-none lg:hidden mr-4 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h1 class="text-xl font-bold text-slate-800 hidden sm:block"><?= $title ?? 'Dasbor Aplikasi' ?></h1>
    </div>

    <div class="flex items-center space-x-4">
        <!-- Wrapper Dropdown -->
        <div class="relative">

            <!-- Tombol Profile -->
            <button @click="dropdownOpen = !dropdownOpen" @click.outside="dropdownOpen = false" class="flex items-center focus:outline-none cursor-pointer">
                <div class="text-right hidden sm:block mr-3">
                    <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?></div>
                    <div class="text-xs text-gray-500 capitalize">
                        <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                        <?= !empty($_SESSION['is_walikelas']) ? '<span class="text-emerald-600 font-semibold">(Wali Kelas)</span>' : '' ?>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold border border-emerald-200 hover:bg-emerald-200 transition-colors">
                    <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
                </div>
            </button>

            <!-- Menu Dropdown -->
            <div x-show="dropdownOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                style="display: none;"
                class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50 overflow-hidden">

                <!-- Info User (Hanya tampil di versi Mobile) -->
                <div class="block sm:hidden px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <div class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?></div>
                    <div class="text-xs text-gray-500 capitalize mt-0.5">
                        <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                        <?= !empty($_SESSION['is_walikelas']) ? '<span class="text-emerald-600 font-semibold">(Wali Kelas)</span>' : '' ?>
                    </div>
                </div>

                <!-- Tombol Ubah Password (Di atas Logout) -->
                <button @click="dropdownOpen = false; changePasswordOpen = true" class="w-full text-left px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    Ubah Password
                </button>

                <a href="/logout" class="block px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL UBAH PASSWORD -->
    <div x-show="changePasswordOpen" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
        <div @click.outside="changePasswordOpen = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden p-6">
            <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-slate-900">Ubah Password Akun</h3>
                <button @click="changePasswordOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="/auth/update-password" method="POST" class="space-y-4 pt-4">
                <!-- Password Lama -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Password Lama</label>
                    <div class="relative">
                        <input :type="showOld ? 'text' : 'password'" name="old_password" required class="w-full px-4 py-2.5 pr-10 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500">
                        <button type="button" @click="showOld = !showOld" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <!-- Icon Mata Terbuka -->
                            <svg x-show="!showOld" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <!-- Icon Mata Dicoret (Tertutup) -->
                            <svg x-show="showOld" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Password Baru -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Password Baru</label>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" name="new_password" 
                               x-model="newPassword"
                               required 
                               class="w-full px-4 py-2.5 pr-10 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500">
                        <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg x-show="!showNew" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showNew" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Indikator Syarat Password -->
                    <div class="mt-2 space-y-1 text-xs" x-data="{
                        get hasMin() { return this.newPassword.length >= 8; },
                        get hasUpper() { return /[A-Z]/.test(this.newPassword); },
                        get hasLower() { return /[a-z]/.test(this.newPassword); },
                        get hasNum() { return /[0-9]/.test(this.newPassword); },
                        get hasSymbol() { return /[^A-Za-z0-9]/.test(this.newPassword); }
                    }">
                        <p :class="hasMin ? 'text-emerald-600 font-semibold' : 'text-gray-400'">&bull; Minimal 8 karakter</p>
                        <p :class="hasUpper ? 'text-emerald-600 font-semibold' : 'text-gray-400'">&bull; Mengandung huruf besar (A-Z)</p>
                        <p :class="hasLower ? 'text-emerald-600 font-semibold' : 'text-gray-400'">&bull; Mengandung huruf kecil (a-z)</p>
                        <p :class="hasNum ? 'text-emerald-600 font-semibold' : 'text-gray-400'">&bull; Mengandung angka (0-9)</p>
                        <p :class="hasSymbol ? 'text-emerald-600 font-semibold' : 'text-gray-400'">&bull; Mengandung simbol khusus (!@#$%^&*...)</p>
                    </div>
                </div>

                <!-- Konfirmasi Password Baru -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="confirm_password" x-model="confirmPassword" required class="w-full px-4 py-2.5 pr-10 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showConfirm" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <p x-show="confirmPassword && newPassword !== confirmPassword" class="text-xs text-red-500 mt-1">Konfirmasi password tidak cocok.</p>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100" x-data="{
                    get isInvalid() {
                        return this.newPassword.length < 8 || 
                               !/[A-Z]/.test(this.newPassword) || 
                               !/[a-z]/.test(this.newPassword) || 
                               !/[0-9]/.test(this.newPassword) || 
                               !/[^A-Za-z0-9]/.test(this.newPassword) || 
                               this.newPassword !== this.confirmPassword;
                    }
                }">
                    <button type="button" @click="changePasswordOpen = false" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                    <button type="submit" 
                            :disabled="isInvalid"
                            :class="isInvalid ? 'opacity-50 cursor-not-allowed bg-emerald-400' : 'bg-emerald-600 hover:bg-emerald-700'"
                            class="px-4 py-2 text-sm font-semibold text-white rounded-xl transition-colors">
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>