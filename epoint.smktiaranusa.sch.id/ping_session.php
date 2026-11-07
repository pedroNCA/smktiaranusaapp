<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'expired']);
    exit;
}

$timeout_limit = defined('SESSION_TIMEOUT_SECONDS') ? SESSION_TIMEOUT_SECONDS : 900;

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
    echo json_encode(['status' => 'expired']);
    exit;
}

// Reset dan perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();
echo json_encode([
    'status' => 'active',
    'remaining' => $timeout_limit
]);
