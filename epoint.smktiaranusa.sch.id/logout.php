<?php
require 'config.php';
$pesan = $_GET['pesan'] ?? '';
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
@session_destroy();

if ($pesan === 'timeout') {
    header('Location: login.php?pesan=timeout');
} else {
    header('Location: login.php');
}
exit;

