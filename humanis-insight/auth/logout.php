<?php
// Mulai sesi
session_start();

// Kosongkan semua variabel sesi
$_SESSION = array();

// Jika ingin menghapus cookie sesi, hapus juga cookie tersebut
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi secara total di server
session_destroy();

// Arahkan kembali ke halaman login dengan parameter pesan keluar
header("Location: login.php?msg=logged_out");
exit;
?>