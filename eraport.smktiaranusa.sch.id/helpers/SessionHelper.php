<?php
class SessionHelper {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function checkLogin() {
        self::init();
        
        // Cek Auto Logout karena inaktivitas (1800 detik / 30 menit)
        $inactive = 1800;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
            session_unset();
            session_destroy();
            header("Location: " . ($_ENV['APP_URL'] ?? '') . "/login?timeout=1");
            exit();
        }
        $_SESSION['last_activity'] = time();

        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['APP_URL'] ?? '') . "/login");
            exit();
        }
    }

    public static function hasRole($role) {
        self::init();
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
}