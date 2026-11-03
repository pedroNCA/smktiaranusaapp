<?php

class Auth
{

    /**
     * Memulai session jika belum aktif
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Apakah sudah login?
     */
    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Ambil seluruh data user dari session
     */
    public static function user(): ?array
    {
        self::startSession();

        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'nama' => $_SESSION['nama_lengkap'] ?? '',
            'role' => $_SESSION['role'] ?? ''
        ];
    }

    /**
     * Ambil ID User
     */
    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    /**
     * Ambil Nama
     */
    public static function name(): ?string
    {
        $user = self::user();
        return $user ? $user['nama'] : null;
    }

    /**
     * Ambil Role
     */
    public static function role(): ?string
    {
        $user = self::user();
        return $user ? $user['role'] : null;
    }

    /**
     * Apakah role tertentu?
     */
    public static function is(string $role): bool
    {
        return self::role() === $role;
    }

    /**
     * Wajib Login
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            // PERBAIKAN: Mengarah ke Front Controller Routing
            header("Location: index.php?route=auth/login");
            exit;
        }
    }

    /**
     * Wajib Admin
     */
    public static function admin(): void
    {
        self::requireLogin();

        if (!self::is('admin')) {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }

    /**
     * Wajib Sekretaris
     */
    public static function sekretaris(): void
    {
        self::requireLogin();

        if (!self::is('sekretaris')) {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }

    /**
     * Wajib Guru
     */
    public static function guru(): void
    {
        self::requireLogin();

        if (!self::is('guru')) {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }

    /**
     * Wajib Guru Piket
     */
    public static function piket(): void
    {
        self::requireLogin();

        if (!self::is('piket')) {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }

    /**
     * Logout
     */
    public static function logout(): void
    {
        self::startSession();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        // PERBAIKAN: Mengarah ke Front Controller Routing
        header("Location: index.php?route=auth/login");
        exit;
    }
}
