<?php
// auth/login.php

// 1. Panggil konfigurasi dan sesi
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// 2. Jika sudah login, tendang langsung ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: /pages/bk/dashboard.php");
    exit();
}

$error = '';

// 3. Proses form jika ada request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF Token terlebih dahulu
    $token_dikirim = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token_dikirim)) {
        $error = "Token keamanan tidak valid. Silakan muat ulang halaman.";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // Password tidak perlu di-sanitize karena akan di-verify dengan hash

        if (empty($username) || empty($password)) {
            $error = "Username dan password wajib diisi!";
        } else {
            // Cari user di database
            $stmt = $pdo->prepare("SELECT id, username, password, nama_lengkap FROM admin_bk WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            // Verifikasi password dengan Hash
            if ($admin && password_verify($password, $admin['password'])) {
                // Hasilkan ulang ID Sesi untuk mencegah Session Fixation
                session_regenerate_id(true);

                // Jika berhasil, set data ke sesi
                $_SESSION['admin_logged_in'] = true; // Wajib ada untuk fungsi requireLogin()
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_nama'] = $admin['nama_lengkap'];
                $_SESSION['last_activity'] = time();

                // Update last_login di database
                $updateStmt = $pdo->prepare("UPDATE admin_bk SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);

                // Redirect ke dashboard (Menggunakan path absolut dari root)
                header("Location: /pages/bk/dashboard.php");
                exit();
            } else {
                $error = "Username atau password salah!";
            }
        }
    }
}

// 4. Tangkap pesan dari URL (misal karena auto-logout atau belum login)
$pesan = $_GET['msg'] ?? $_GET['pesan'] ?? '';
$notif = '';
if ($pesan === 'timeout') {
    $notif = "Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.";
} elseif ($pesan === 'belum_login') {
    $notif = "Anda harus login untuk mengakses halaman tersebut.";
} elseif ($pesan === 'logout') {
    $notif = "Anda berhasil logout.";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login BK | SMK Tiara Nusa</title>

    <!-- Pastikan path aset relatif terhadap root (karena diakses via /gerbang-admin-z12) -->
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">
        @theme {
            --font-fredoka: 'Fredoka', sans-serif;
            --color-feast-blue: #0099ff;
            --color-feast-pink: #ff007f;
            --color-feast-yellow: #ffde00;
        }
        body { font-family: var(--font-fredoka); background-color: var(--color-feast-blue); color: #000; }
        .feast-shadow { box-shadow: 6px 6px 0px 0px rgba(0,0,0,1); }
        .feast-shadow-lg { box-shadow: 10px 10px 0px 0px rgba(0,0,0,1); }
        .hover-feast-press:active { box-shadow: 0px 0px 0px 0px rgba(0,0,0,1); transform: translate(6px, 6px); }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 selection:bg-feast-pink selection:text-white">

    <div class="w-full max-w-md">
        <!-- Tombol Kembali -->
        <a href="/" class="inline-block bg-white font-bold border-4 border-black px-4 py-2 rounded-xl feast-shadow hover-feast-press transition-all mb-6 rotate-[-2deg]">
            ⬅️ Kembali ke Beranda
        </a>

        <!-- Card Login -->
        <div class="bg-white border-8 border-black rounded-3xl p-8 feast-shadow-lg rotate-[1deg]">
            <div class="text-center mb-8">
                <img src="/assets/img/logo.png" alt="Logo" class="w-16 h-16 mx-auto mb-4 object-contain" onerror="this.style.display='none'">
                <h1 class="text-4xl font-extrabold text-feast-pink tracking-wide" style="-webkit-text-stroke: 1px black;">PORTAL BK</h1>
                <p class="font-bold text-gray-600 mt-2">SMK Tiara Nusa</p>
            </div>

            <!-- Area Notifikasi Pesan URL -->
            <?php if ($notif): ?>
                <div class="bg-feast-yellow border-4 border-black p-3 rounded-xl mb-6 font-bold text-center text-sm transform rotate-[-1deg]">
                    ⚠️ <?= htmlspecialchars($notif) ?>
                </div>
            <?php endif; ?>

            <!-- Area Notifikasi Error Login -->
            <?php if ($error): ?>
                <div class="bg-red-400 text-white border-4 border-black p-3 rounded-xl mb-6 font-bold text-center text-sm transform rotate-[1deg]">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- action kosong ("") agar POST menargetkan URL .htaccess saat ini (/gerbang-admin-z12) -->
            <form method="POST" action="" class="space-y-6">
                <!-- CSRF TOKEN -->
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                <div>
                    <label class="block font-bold mb-2 text-lg">Username</label>
                    <input type="text" name="username" required placeholder="Masukkan username..."
                        class="w-full border-4 border-black p-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-feast-blue font-semibold">
                </div>
                <div>
                    <label class="block font-bold mb-2 text-lg">Password</label>
                    <input type="password" name="password" required placeholder="Masukkan password..."
                        class="w-full border-4 border-black p-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-feast-blue font-semibold">
                </div>
                <button type="submit" class="w-full bg-feast-pink text-white text-2xl font-bold py-4 border-4 border-black rounded-full feast-shadow hover-feast-press transition-all mt-4">
                    🚀 MASUK
                </button>
            </form>
        </div>
    </div>

</body>

</html>