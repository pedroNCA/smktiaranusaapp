<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$timeout_limit = defined('SESSION_TIMEOUT_SECONDS') ? SESSION_TIMEOUT_SECONDS : 900;

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek apakah sesi telah kedaluwarsa karena tidak ada aktivitas
if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity'] > $timeout_limit)) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    @session_destroy();
    header('Location: login.php?pesan=timeout');
    exit;
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

/**
 * Batasi halaman hanya untuk admin
 */
if (!function_exists('hanyaAdmin')) {
    function hanyaAdmin() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            die('<div style="font-family:sans-serif;padding:50px;text-align:center">
                <h2 style="color:#ef4444">403 - Akses Ditolak</h2>
                <p style="color:#64748b">Halaman ini hanya dapat diakses oleh Administrator.</p>
                <a href="index.php" style="display:inline-block;padding:8px 16px;background:#0f172a;color:#fff;text-decoration:none;border-radius:6px">&larr; Kembali ke Dashboard</a>
                </div>');
        }
    }
}

/**
 * Helper cek role
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return ($_SESSION['role'] ?? '') === 'admin';
    }
}

if (!function_exists('isGuruBK')) {
    function isGuruBK() {
        return in_array($_SESSION['role'] ?? '', ['admin', 'guru_bk']);
    }
}

if (!function_exists('isKepalaSekolah')) {
    function isKepalaSekolah() {
        return ($_SESSION['role'] ?? '') === 'kepala_sekolah';
    }
}

if (!function_exists('isOsis')) {
    function isOsis() {
        return ($_SESSION['role'] ?? '') === 'osis';
    }
}

if (!function_exists('roleLabel')) {
    function roleLabel($role = null) {
        $r = $role ?? ($_SESSION['role'] ?? '');
        $labels = [
            'admin'          => 'Administrator',
            'kepala_sekolah' => 'Kepala Sekolah',
            'guru_bk'        => 'Guru BK',
            'wali_kelas'     => 'Wali Kelas',
            'guru'           => 'Guru',
            'osis'           => 'Petugas OSIS'
        ];
        return $labels[$r] ?? ucfirst($r);
    }
}

