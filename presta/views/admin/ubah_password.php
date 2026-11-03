<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Ubah Password';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordLama = trim($_POST['password_lama']);
    $passwordBaru = trim($_POST['password_baru']);
    $konfirmasi   = trim($_POST['konfirmasi']);

    if (
        empty($passwordLama) ||
        empty($passwordBaru) ||
        empty($konfirmasi)
    ) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($passwordBaru) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($passwordBaru !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sesuai.';
    } else {
        $stmt = $conn->prepare("
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'User tidak ditemukan.';
        } elseif (!password_verify($passwordLama, $user['password'])) {
            $error = 'Password lama salah.';
        } else {
            $passwordHash = password_hash(
                $passwordBaru,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");
            $stmt->bind_param("si", $passwordHash, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $success = 'Password berhasil diubah.';
            } else {
                $error = 'Gagal mengubah password.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | PRESTA</title>
    <?php include '../includes/admin_head.php'; ?>
</head>

<body class="bg-slate-50 antialiased text-gray-800">

    <!-- Wrapper Utama Layout Admin -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>

        <!-- Konten Kanan (Topbar + Isi Halaman) -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Topbar -->
            <?php include '../includes/admin_topbar.php'; ?>

            <!-- Area Konten Utama -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-4xl mx-auto">

                    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-key text-brand-600"></i> Ubah Password
                            </h2>
                            <p class="text-xs text-gray-500 mt-1">
                                Gunakan password yang kuat agar akun administrator tetap aman.
                            </p>
                        </div>

                        <?php if ($success): ?>
                            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
                                <?= $success ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Password Lama</label>
                                <div class="relative w-full">
                                    <input type="password"
                                           name="password_lama"
                                           id="lama"
                                           class="w-full h-12 border border-gray-200 rounded-xl px-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all"
                                           required>
                                    <button type="button"
                                            onclick="togglePassword('lama', this)"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Password Baru</label>
                                <div class="relative w-full">
                                    <input type="password"
                                           name="password_baru"
                                           id="baru"
                                           class="w-full h-12 border border-gray-200 rounded-xl px-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all"
                                           required>
                                    <button type="button"
                                            onclick="togglePassword('baru', this)"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Konfirmasi Password Baru</label>
                                <div class="relative w-full">
                                    <input type="password"
                                           name="konfirmasi"
                                           id="konfirmasi"
                                           class="w-full h-12 border border-gray-200 rounded-xl px-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all"
                                           required>
                                    <button type="button"
                                            onclick="togglePassword('konfirmasi', this)"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm w-full sm:w-auto">
                                    <i class="fas fa-save"></i> Simpan Password
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>

    </div>

    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector("i");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        function toggleUserMenu(event) {
            if (event) {
                event.stopPropagation();
            }
            const dropdown = document.getElementById('userDropdown');
            if (!dropdown) return;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function toggleSidebar(event) {
            if (event) {
                event.stopPropagation();
            }
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const trigger = document.getElementById('profileTrigger');
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');

            if (dropdown && trigger && !trigger.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }

            if (window.innerWidth < 1024 && sidebar && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && (!menuToggle || !menuToggle.contains(event.target))) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>

</body>

</html>