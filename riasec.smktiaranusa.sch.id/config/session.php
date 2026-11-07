<?php
// config/session.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================================
// 1. KEAMANAN: SESSION TIMEOUT (AUTO LOGOUT)
// ================================================================
// Atur waktu maksimal sesi (contoh: 30 menit = 1800 detik)
$timeout_duration = 1800; 

// Cek apakah ada aktivitas terakhir
if (isset($_SESSION['last_activity'])) {
    // Hitung selisih waktu sekarang dengan aktivitas terakhir
    $waktu_berlalu = time() - $_SESSION['last_activity'];
    
    // Jika lewat dari 30 menit, paksa logout
    if ($waktu_berlalu > $timeout_duration) {
        session_unset();
        session_destroy();
        // Redirect ke halaman login dengan pesan (Gunakan URL samaran)
        header("Location: /gerbang-admin-z12?msg=timeout");
        exit();
    }
}
// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();


// ================================================================
// 2. FUNGSI CEK LOGIN ADMIN
// ================================================================
function requireLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: /gerbang-admin-z12");
        exit();
    }
}


// ================================================================
// 3. KEAMANAN: CSRF TOKEN (Mencegah Form Spam/Tembakan dari Luar)
// ================================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generateCSRFToken() {
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>