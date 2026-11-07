<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - e-Raport SMK Tiara Nusa</title>
    <!-- Favicon -->
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Flowbite v2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md p-6 mx-auto">
        <!-- Card Login -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">

            <!-- Header Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-white border border-gray-200 rounded-xl mb-4 shadow-sm">
                    <img src="/assets/img/logo.png" alt="Logo SMK Tiara Nusa" class="w-8 h-8 object-contain">
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Masuk Aplikasi</h1>
                <p class="text-sm text-gray-500 mt-1">e-Raport SMK Tiara Nusa</p>
            </div>

            <!-- Alert Notifikasi Timeout / Error -->
            <?php if (isset($_GET['timeout'])): ?>
                <div class="mb-4 p-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl" role="alert">
                    Sesi Anda telah habis karena tidak ada aktivitas. Silakan login kembali.
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="mb-4 p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="/login" method="POST" class="space-y-5">
                <div>
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-3"
                        placeholder="Masukkan username">
                </div>

                <div x-data="{ showPassword: false }">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-3 pr-10"
                            placeholder="••••••••">
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <!-- Icon Mata Terbuka -->
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <!-- Icon Mata Dicoret (Tertutup) -->
                            <svg x-show="showPassword" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="remember" type="checkbox" name="remember" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-emerald-300 text-emerald-600">
                        </div>
                        <label for="remember" class="ml-2 text-gray-600">Ingat saya</label>
                    </div>
                </div>

                <button type="submit" class="w-full text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-semibold rounded-xl text-sm px-5 py-3 text-center transition-all shadow-sm">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Back to Landing Page -->
            <div class="mt-6 text-center">
                <a href="/" class="text-sm font-medium text-gray-600 hover:text-emerald-600 transition">
                    &larr; Kembali ke Beranda
                </a>
            </div>

        </div>

        <!-- Footer Info -->
        <div class="mt-6 text-center text-xs text-gray-500">
            &copy; <?= date('Y') ?> e-Raport SMK Tiara Nusa. Hak Cipta Dilindungi.
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>

</html>