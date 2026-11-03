<?php
session_start();
require_once '../config/database.php';

/** @var PDO $pdo */

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM tb_users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['bidang'] = $user['bidang'];
            $_SESSION['last_activity'] = time();

            // Redirect berdasarkan role dan bidang
            switch ($user['role']) {
                case 'Kepala Sekolah':
                    header("Location: ../pages/kepsek/dashboard.php");
                    break;
                case 'Wakil Kepala':
                    header("Location: ../pages/wakasek/dashboard.php");
                    break;
                case 'Guru/PIC':
                    header("Location: ../pages/pic/dashboard.php");
                    break;
                case 'Operator':
                    header("Location: ../pages/operator/dashboard.php");
                    break;
                case 'Yayasan':
                    header("Location: ../pages/yayasan/dashboard.php");
                    break;
                default:
                    header("Location: ../auth/login.php?error=1");
                    break;
            }
            exit;
            
        } else {
            header("Location: ../auth/login.php?error=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Terjadi kesalahan: " . $e->getMessage());
    }
} else {
    header("Location: ../auth/login.php");
    exit;
}
?>