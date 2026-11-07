<?php

class AuthController
{
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->connect();
    }

    // Proses Login Pengguna
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!empty($username) && !empty($password)) {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Set Sesi Pengguna
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role']; // 'admin', 'guru'
                    $_SESSION['is_walikelas'] = $user['is_walikelas']; // true/false
                    $_SESSION['last_activity'] = time();

                    header("Location: /dashboard");
                    exit();
                } else {
                    // Anda dapat melewatkan variabel error ini ke view login jika diperlukan
                    $error_message = "Username atau password salah!";
                }
            } else {
                $error_message = "Mohon isi semua kolom.";
            }
        }
    }

    // Proses Ubah Password dengan Validasi Keamanan
    public function updatePassword()
    {
        SessionHelper::checkLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'] ?? null;
            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!$userId) {
                die("Sesi tidak valid.");
            }

            if ($newPassword !== $confirmPassword) {
                die("Konfirmasi password baru tidak cocok.");
            }

            // Validasi aturan password (Minimal 8 karakter, Huruf Besar, Huruf Kecil, Angka, Simbol)
            $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/';
            if (!preg_match($pattern, $newPassword)) {
                die("Password harus minimal 8 karakter serta mengandung huruf besar, huruf kecil, angka, dan simbol.");
            }

            // Ambil data user dari database
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($oldPassword, $user['password'])) {
                die("Password lama yang Anda masukkan salah.");
            }

            // Hash password baru dan simpan ke database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $updateStmt->execute(['password' => $hashedPassword, 'id' => $userId]);

            // Redirect kembali dengan indikator sukses
            header("Location: /dashboard?success=password_updated");
            exit();
        }
    }
}
