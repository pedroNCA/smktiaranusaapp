<?php
require 'config.php';
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$info_timeout = '';
if (($_GET['pesan'] ?? '') === 'timeout') {
    $info_timeout = 'Sesi Anda telah berakhir, silakan login kembali.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(clean($koneksi, $_POST['username']));
    $password = $_POST['password'] ?? '';

    // Cek batas percobaan login (brute-force protection)
    $lockout_time = 60; // Kunci sementara 60 detik jika 5x gagal
    if (isset($_SESSION['login_lockout']) && time() < $_SESSION['login_lockout']) {
        $sisa_waktu = $_SESSION['login_lockout'] - time();
        $error = "Terlalu banyak percobaan login gagal. Demi keamanan, silakan tunggu $sisa_waktu detik lagi.";
    } else {
        try {
            $has_plain_col = false;
            try {
                $c_plain = @mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
                $has_plain_col = ($c_plain && mysqli_num_rows($c_plain) > 0);
            } catch (Throwable $e) {}
            $f_plain = $has_plain_col ? 'u.password_plain,' : "'' AS password_plain,";

            $stmt = mysqli_prepare($koneksi, "SELECT u.id, u.username, u.password, $f_plain u.nama_lengkap, u.role, u.kelas_id, k.nama_kelas, k.jurusan 
                                               FROM users u 
                                               LEFT JOIN kelas k ON u.kelas_id = k.id 
                                               WHERE u.username = ?");
            if (!$stmt) {
                throw new Exception("Koneksi query database gagal: " . mysqli_error($koneksi));
            }
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            $is_valid = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $is_valid = true;
                    if ($has_plain_col && empty($user['password_plain'])) {
                        $p_clean = clean($koneksi, $password);
                        @mysqli_query($koneksi, "UPDATE users SET password_plain='$p_clean' WHERE id={$user['id']}");
                    }
                } elseif (!empty($user['password_plain']) && $password === $user['password_plain']) {
                    // Fallback: jika password cocok dengan teks di password_plain (misal diedit manual di phpMyAdmin)
                    $is_valid = true;
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    @mysqli_query($koneksi, "UPDATE users SET password='$new_hash' WHERE id={$user['id']}");
                } elseif ($password === $user['password']) {
                    // Fallback aman: jika diubah manual di database sebagai teks biasa di kolom password
                    $is_valid = true;
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $p_clean = clean($koneksi, $password);
                    if ($has_plain_col) {
                        @mysqli_query($koneksi, "UPDATE users SET password='$new_hash', password_plain='$p_clean' WHERE id={$user['id']}");
                    } else {
                        @mysqli_query($koneksi, "UPDATE users SET password='$new_hash' WHERE id={$user['id']}");
                    }
                }
            }

            if ($is_valid) {
                // Reset counter percobaan saat login berhasil
                unset($_SESSION['login_attempts'], $_SESSION['login_lockout']);

                // Regenerasi session ID untuk cegah session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['kelas_id'] = $user['kelas_id'];
                $_SESSION['nama_kelas'] = $user['nama_kelas'] ?? '';
                $_SESSION['jurusan'] = $user['jurusan'] ?? '';
                $_SESSION['last_activity'] = time();
                
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_lockout'] = time() + $lockout_time;
                    $error = 'Terlalu banyak percobaan login gagal (5x). Sistem dikunci sementara selama 60 detik demi keamanan.';
                } else {
                    $sisa = 5 - $_SESSION['login_attempts'];
                    $error = "Username atau password yang Anda masukkan salah. (Sisa percobaan: $sisa kali)";
                }
            }
        } catch (Throwable $e) {
            $error = 'Terjadi kendala pada sistem database saat login: ' . htmlspecialchars($e->getMessage());
        }
    }
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - E-Point SMKS Tiara Nusa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root {
        --primary-teal: #0d9488;
        --primary-dark: #0f766e;
        --accent-sky: #0284c7;
        --dark-slate: #0f172a;
    }
    * {
        box-sizing: border-box;
    }
    body {
        background: radial-gradient(circle at 10% 20%, rgba(13, 148, 136, 0.18) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(2, 132, 199, 0.18) 0%, transparent 40%),
                    linear-gradient(135deg, #0b1329 0%, #0f172a 50%, #04252a 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        padding: 1.5rem 1rem;
        position: relative;
        overflow-x: hidden;
    }

    /* Ambient background glowing orbs */
    .bg-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
        z-index: 0;
        opacity: 0.6;
        animation: floatOrb 10s ease-in-out infinite alternate;
    }
    .bg-orb-1 {
        width: 320px;
        height: 320px;
        background: rgba(13, 148, 136, 0.35);
        top: -60px;
        left: -60px;
    }
    .bg-orb-2 {
        width: 380px;
        height: 380px;
        background: rgba(2, 132, 199, 0.28);
        bottom: -80px;
        right: -80px;
        animation-delay: -5s;
    }
    @keyframes floatOrb {
        0% { transform: translateY(0) scale(1); }
        100% { transform: translateY(30px) scale(1.08); }
    }

    /* Main Split Card */
    .split-container {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 1060px;
        background: #ffffff;
        border-radius: 28px;
        box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.45),
                    0 0 0 1px rgba(255, 255, 255, 0.15);
        overflow: hidden;
        display: grid;
        grid-template-columns: 1fr;
        animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @media (min-width: 992px) {
        .split-container {
            grid-template-columns: 1.15fr 1fr;
            min-height: 620px;
        }
    }
    @keyframes cardAppear {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Left Side: Branding Hero */
    .hero-side {
        background: linear-gradient(145deg, #094843 0%, #0d9488 45%, #0369a1 100%);
        color: #ffffff;
        padding: 3rem 2.75rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    /* Subtle decorative geometric overlay */
    .hero-side::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
        background-size: 24px 24px;
        opacity: 0.45;
        pointer-events: none;
    }
    .hero-glow-circle {
        position: absolute;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        top: -100px;
        right: -100px;
        pointer-events: none;
    }

    .hero-header {
        position: relative;
        z-index: 2;
    }
    .brand-logo-frame {
        width: 76px;
        height: 76px;
        background: #ffffff;
        border-radius: 20px;
        padding: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.22),
                    0 0 0 3px rgba(255, 255, 255, 0.25);
        margin-bottom: 1.25rem;
        transition: transform 0.3s ease;
    }
    .brand-logo-frame:hover {
        transform: rotate(-3deg) scale(1.05);
    }
    .brand-logo-frame img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 16px;
    }
    .school-title {
        font-size: 1.65rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        line-height: 1.2;
        margin-bottom: 0.4rem;
        color: #ffffff;
    }
    .badge-humanis {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        padding: 5px 14px;
        border-radius: 50px;
        margin-bottom: 0.85rem;
    }
    .hero-tagline {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.55;
        max-width: 420px;
        margin-bottom: 1.5rem;
    }

    /* Floating Showcase Feature Cards */
    .hero-features {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        margin: 1.5rem 0;
    }
    .feature-pill {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.85rem 1.15rem;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.25s ease;
    }
    .feature-pill:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateX(6px);
        border-color: rgba(255, 255, 255, 0.35);
    }
    .feature-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        color: #ffffff;
    }
    .feature-text h6 {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: #ffffff;
    }
    .feature-text p {
        margin: 0;
        font-size: 0.775rem;
        color: rgba(255, 255, 255, 0.82);
    }

    .hero-footer {
        position: relative;
        z-index: 2;
        font-size: 0.78rem;
        color: rgba(255, 255, 255, 0.75);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Right Side: Modern Form */
    .form-side {
        padding: 3rem 2.75rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: #ffffff;
    }
    /* Mobile & Tablet Optimizations (Responsive Bootstrap Standards) */
    @media (max-width: 991.98px) {
        body {
            padding: 1rem 0.75rem;
            align-items: center;
        }
        .split-container {
            border-radius: 20px;
            margin: auto 0;
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.4);
        }
        .hero-side {
            padding: 1.75rem 1.25rem 1.5rem 1.25rem;
            text-align: center;
            align-items: center;
        }
        .hero-header {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .brand-logo-frame {
            width: 64px;
            height: 64px;
            margin-bottom: 0.75rem;
            border-radius: 16px;
        }
        .school-title {
            font-size: 1.35rem;
            margin-bottom: 0.25rem;
        }
        .badge-humanis {
            font-size: 0.68rem;
            padding: 4px 12px;
            margin-bottom: 0.5rem;
        }
        .hero-tagline {
            font-size: 0.825rem;
            margin-bottom: 0;
            line-height: 1.45;
        }
        /* Sembunyikan feature cards di HP agar user tidak perlu scroll panjang untuk menemukan form */
        .hero-features, .hero-footer {
            display: none !important;
        }
        .form-side {
            padding: 1.75rem 1.25rem 2rem 1.25rem;
        }
        .form-title {
            font-size: 1.4rem;
        }
        .form-subtitle {
            font-size: 0.825rem;
            margin-bottom: 1.25rem;
        }
    }
    @media (max-width: 576px) {
        body {
            padding: 0.5rem;
        }
        .split-container {
            border-radius: 16px;
        }
    }
    .form-greeting-badge {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--primary-teal);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.35rem;
    }
    .form-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--dark-slate);
        letter-spacing: -0.5px;
        margin-bottom: 0.35rem;
    }
    .form-subtitle {
        font-size: 0.875rem;
        color: #64748b;
        margin-bottom: 1.75rem;
    }

    .form-group-custom {
        margin-bottom: 1.25rem;
    }
    .form-label-custom {
        font-size: 0.8rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .input-box {
        display: flex;
        align-items: stretch;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        transition: all 0.2s ease;
        overflow: hidden;
    }
    .input-box:focus-within {
        border-color: var(--primary-teal);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
    }
    .input-icon {
        background: transparent;
        border: none;
        padding: 0 0.95rem;
        color: #94a3b8;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease;
    }
    .input-box:focus-within .input-icon {
        color: var(--primary-teal);
    }
    .input-field {
        border: none;
        background: transparent;
        padding: 0.8rem 0.85rem 0.8rem 0;
        font-size: 0.925rem;
        color: #1e293b;
        width: 100%;
        outline: none;
        font-family: inherit;
    }
    .input-field::placeholder {
        color: #94a3b8;
        font-size: 0.875rem;
    }

    /* Seamless Eye Toggle Button (No Thick Border) */
    .btn-eye-toggle {
        background: transparent;
        border: none;
        color: #94a3b8;
        padding: 0 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: color 0.15s ease;
        font-size: 1.1rem;
    }
    .btn-eye-toggle:hover {
        color: var(--primary-teal);
        background: transparent;
    }
    .btn-eye-toggle:focus {
        outline: none;
        box-shadow: none;
        color: var(--primary-teal);
    }

    /* Primary Login Button */
    .btn-submit-login {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        border: none;
        color: #ffffff;
        padding: 0.85rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 700;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 8px 20px -4px rgba(13, 148, 136, 0.45);
        cursor: pointer;
        width: 100%;
        margin-top: 1rem;
    }
    .btn-submit-login:hover {
        background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -4px rgba(13, 148, 136, 0.55);
        color: #ffffff;
    }
    .btn-submit-login:active {
        transform: translateY(0);
    }
</style>
</head>
<body>

<!-- Ambient Glowing Orbs -->
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>

<!-- Main Split Screen Container -->
<div class="split-container">
    
    <!-- LEFT SIDE: Branding Showcase -->
    <div class="hero-side">
        <div class="hero-glow-circle"></div>
        
        <div class="hero-header">
            <div class="brand-logo-frame">
                <img src="assets/logo_tinus.jpg" alt="Logo SMKS Tiara Nusa">
            </div>
            <div class="badge-humanis">
                <i class="bi bi-stars"></i> SISTEM HUMANIS
            </div>
            <h1 class="school-title">SMKS TIARA NUSA</h1>
            <p class="hero-tagline">
                Sistem Terpadu Pencatatan Pelanggaran Tata Tertib &amp; Apresiasi Prestasi Karakter Siswa.
            </p>
        </div>

        <div class="hero-features">
            <div class="feature-pill">
                <div class="feature-icon-box">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div class="feature-text">
                    <h6>Apresiasi &amp; Prestasi</h6>
                    <p>Penghargaan keaktifan, kepemimpinan &amp; integritas siswa</p>
                </div>
            </div>
            <div class="feature-pill">
                <div class="feature-icon-box">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="feature-text">
                    <h6>Penilaian Karakter Adil</h6>
                    <p>Pembinaan kedisiplinan objektif sesuai tata tertib resmi</p>
                </div>
            </div>
            <div class="feature-pill">
                <div class="feature-icon-box">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="feature-text">
                    <h6>Sinergi Terintegrasi</h6>
                    <p>Wali Kelas, Guru BK, Kepala Sekolah &amp; Petugas OSIS</p>
                </div>
            </div>
        </div>

        <div class="hero-footer">
            <i class="bi bi-shield-lock-fill text-warning"></i>
            <span>Sistem E-Point Kedisiplinan Terpadu • &copy; <?= date('Y') ?> SMKS Tiara Nusa</span>
        </div>
    </div>

    <!-- RIGHT SIDE: Clean & Sleek Login Form -->
    <div class="form-side">
        <div>
            <span class="form-greeting-badge">Selamat Datang</span>
            <h2 class="form-title">Masuk ke Sistem</h2>
            <p class="form-subtitle">Silakan masukkan username &amp; password Anda untuk melanjutkan.</p>

            <?php if (!empty($info_timeout)): ?>
                <div class="alert alert-warning py-2.5 px-3 small d-flex align-items-center gap-2 mb-3 shadow-sm" style="border-radius:12px; border:1px solid #fde68a; background:#fffbeb;">
                    <i class="bi bi-clock-history text-warning fs-5"></i>
                    <div class="text-dark fw-semibold"><?= htmlspecialchars($info_timeout) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3" style="border-radius:12px; border:1px solid #fecaca; background:#fef2f2;">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-6"></i>
                    <div class="text-danger fw-semibold"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <?php if (!empty($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
                <?php endif; ?>

                <div class="form-group-custom">
                    <label class="form-label-custom">
                        <span>Username</span>
                    </label>
                    <div class="input-box">
                        <span class="input-icon"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" id="usernameInput" class="input-field" placeholder="Masukkan username Anda" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group-custom">
                    <label class="form-label-custom">
                        <span>Password</span>
                    </label>
                    <div class="input-box">
                        <span class="input-icon"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" id="passwordInput" class="input-field" placeholder="Masukkan password Anda" required autocomplete="current-password">
                        <button type="button" class="btn-eye-toggle" onclick="togglePassword()" tabindex="-1" title="Lihat/Sembunyikan password">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit-login" id="btnSubmit">
                    <span>Masuk Sekarang</span>
                    <i class="bi bi-arrow-right-circle-fill"></i>
                </button>
            </form>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const p = document.getElementById('passwordInput');
    const ic = document.getElementById('toggleIcon');
    if (p.type === 'password') {
        p.type = 'text';
        ic.className = 'bi bi-eye-slash';
    } else {
        p.type = 'password';
        ic.className = 'bi bi-eye';
    }
}

// Add loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memverifikasi...';
    btn.disabled = true;
});
</script>
</body>
</html>

