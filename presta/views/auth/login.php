<?php
// WAJIB ADA: Mulai sesi sebelum mengecek atau menyimpan data session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

/*
|--------------------------------------------------------------------------
| Redirect jika sudah login
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header("Location: index.php?route=" . $_SESSION['role'] . "/dashboard");
    exit;
}

/*
|--------------------------------------------------------------------------
| Proses Login
|--------------------------------------------------------------------------
*/
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, password, nama_lengkap, role
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verifikasi Password Hash
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Cegah Session Fixation

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            // PERBAIKAN: Mengarah ke Front Controller Routing
            header("Location: index.php?route=" . $user['role'] . "/dashboard");
            exit;
        }

        $error_message = "Kata sandi yang Anda masukkan salah.";
    } else {
        $error_message = "Username tidak ditemukan.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PRESTA SMK Tiara Nusa</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.ico">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Tailwind Configuration & Keyframes -->
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Poppins', sans-serif;
            --color-brand-50: #f0f9ff;
            --color-brand-100: #e0f2fe;
            --color-brand-500: #0ea5e9;
            --color-brand-600: #0284c7;
            --color-brand-800: #075985;
            --color-brand-900: #0c4a6e;
        }
        body { font-family: var(--font-sans); }
        
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        @keyframes float-fast {
            0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
            50% { transform: translateY(-15px) rotate(-3deg) scale(1.05); }
        }
        .animate-float-slow { animation: float-slow 6s ease-in-out infinite; }
        .animate-float-fast { animation: float-fast 4s ease-in-out infinite; }

        .bg-grid-pattern {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(0, 0, 0, 0.03) 1px, transparent 1px);
        }
    </style>
</head>

<body class="antialiased bg-gray-50 text-gray-700 min-h-screen flex flex-col py-8 px-4 sm:px-6 relative overflow-x-hidden selection:bg-brand-500 selection:text-white">

    <!-- Background Pattern & Blobs -->
    <div class="absolute inset-0 bg-grid-pattern z-0 pointer-events-none fixed"></div>
    <div class="absolute top-[-10%] left-[-10%] w-[40vw] h-[40vw] bg-brand-500 rounded-full mix-blend-multiply filter blur-[100px] opacity-20 animate-float-slow z-0 fixed"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[30vw] h-[30vw] bg-cyan-400 rounded-full mix-blend-multiply filter blur-[80px] opacity-20 animate-float-fast z-0 fixed"></div>

    <!-- Main Wrapper (Card) -->
    <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)"
        class="m-auto w-full max-w-5xl bg-white/90 backdrop-blur-xl rounded-[2rem] lg:rounded-[2.5rem] shadow-2xl border border-white/50 overflow-hidden flex flex-col lg:flex-row relative z-10 transform transition-all duration-1000 ease-out"
        :class="show ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-12 scale-95'">

        <!-- ================= LEFT: BRANDING ================= -->
        <section class="lg:w-5/12 bg-gradient-to-br from-brand-800 to-brand-900 p-8 lg:p-14 text-white relative overflow-hidden flex flex-col justify-center min-h-[300px] lg:min-h-full">
            <div class="absolute top-0 right-0 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMDUiLz4KPC9zdmc+')] opacity-20 pointer-events-none"></div>
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-brand-500 rounded-full mix-blend-overlay filter blur-3xl opacity-50 animate-float-slow pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-cyan-400 rounded-full mix-blend-overlay filter blur-3xl opacity-50 animate-float-fast pointer-events-none"></div>

            <div class="relative z-10">
                <a href="index.php" class="inline-block group mb-6 lg:mb-10 transform transition-transform hover:scale-110">
                    <div class="w-14 h-14 lg:w-16 lg:h-16 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center shadow-lg group-hover:bg-white/20 transition-all duration-300">
                        <img src="assets/img/logo.png" alt="PRESTA" class="w-8 h-8 lg:w-10 lg:h-10 object-contain drop-shadow-md">
                    </div>
                </a>

                <h1 class="text-3xl lg:text-5xl font-extrabold tracking-tight mb-3 lg:mb-4 leading-tight">
                    Welcome to <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-brand-100">PRESTA</span>
                </h1>

                <p class="text-brand-100/80 text-base lg:text-lg font-light mb-6 lg:mb-8 max-w-sm leading-relaxed">
                    Sistem Manajemen Presensi Digital Cerdas untuk ekosistem pendidikan SMK Tiara Nusa
                </p>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 lg:p-5 rounded-xl lg:rounded-2xl shadow-xl animate-float-slow max-w-[280px]">
                    <div class="text-sm text-white/90 font-semibold mb-1">HUMANIS TINUS</div>
                    <div class="text-sm text-white/70 font-medium">SWA KARAKTER TINUS</div>
                </div>
            </div>
        </section>

        <!-- ================= RIGHT: LOGIN FORM ================= -->
        <section class="lg:w-7/12 p-6 sm:p-10 lg:p-16 flex flex-col justify-center bg-white relative">
            <div class="max-w-md w-full mx-auto relative z-10" x-data="{ mounted: false }" x-init="setTimeout(() => mounted = true, 400)">

                <div class="mb-8 lg:mb-10 transform transition-all duration-700 delay-100" :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'">
                    <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">Masuk Akun</h2>
                    <p class="text-gray-500 text-sm">Silakan masukkan kredensial Anda untuk melanjutkan ke dashboard.</p>
                </div>

                <!-- Alert Error -->
                <?php if (!empty($error_message)): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-start gap-3 animate-pulse">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm font-medium text-red-700">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5 lg:space-y-6">
                    <!-- Input Username -->
                    <div class="transform transition-all duration-700 delay-200" :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <input type="text" name="username" placeholder="Masukkan username" autocomplete="off" required
                                class="block w-full pl-11 lg:pl-12 pr-4 py-3 lg:py-3.5 bg-gray-50 border-2 border-transparent rounded-xl text-sm focus:bg-white focus:ring-0 focus:border-brand-500 transition-all outline-none placeholder-gray-400 hover:bg-gray-100">
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div x-data="{ showPassword: false }" class="transform transition-all duration-700 delay-300" :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kata Sandi</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4" />
                                </svg>
                            </div>
                            <input :type="showPassword ? 'text' : 'password'" name="password" placeholder="Masukkan kata sandi" required
                                class="block w-full pl-11 lg:pl-12 pr-12 py-3 lg:py-3.5 bg-gray-50 border-2 border-transparent rounded-xl text-sm focus:bg-white focus:ring-0 focus:border-brand-500 transition-all outline-none placeholder-gray-400 hover:bg-gray-100">

                            <!-- Toggle Button -->
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-brand-600 focus:outline-none transition-colors">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2 transform transition-all duration-700 delay-400" :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                        <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 flex justify-center items-center gap-2 group">
                            Masuk ke Sistem
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-5 border-t border-gray-100 flex justify-center transform transition-all duration-700 delay-500" :class="mounted ? 'opacity-100' : 'opacity-0'">
                    <a href="index.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-brand-600 transition-colors group">
                        <div class="p-1.5 rounded-full bg-gray-50 group-hover:bg-brand-50 transition-colors">
                            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="12" x2="5" y2="12" />
                                <polyline points="12 19 5 12 12 5" />
                            </svg>
                        </div>
                        Kembali ke Beranda
                    </a>
                </div>

            </div>
        </section>
    </div>
</body>

</html>