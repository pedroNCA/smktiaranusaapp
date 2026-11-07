<?php
// pages/bk/ganti_password.php

require_once __DIR__ . '/../../config/session.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';

$admin_id = $_SESSION['admin_id'] ?? 0;
$pesan = '';
$tipe_pesan = '';

// Proses jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi Keamanan CSRF Token
    $token_dikirim = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token_dikirim)) {
        $pesan = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
        $tipe_pesan = 'error';
    } else {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        if (!empty($password_lama) && !empty($password_baru) && !empty($konfirmasi_password)) {
            if ($password_baru !== $konfirmasi_password) {
                $pesan = 'Password baru dan konfirmasi password tidak cocok!';
                $tipe_pesan = 'error';
            } elseif (strlen($password_baru) < 6) {
                $pesan = 'Password baru minimal harus terdiri dari 6 karakter.';
                $tipe_pesan = 'error';
            } else {
                try {
                    // Ambil password (hash) saat ini dari database
                    $stmt = $pdo->prepare("SELECT password FROM admin_bk WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    $db_password = $stmt->fetchColumn();

                    // Verifikasi password lama
                    if (password_verify($password_lama, $db_password)) {
                        // Hash password baru dan simpan
                        $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);

                        $stmtUpdate = $pdo->prepare("UPDATE admin_bk SET password = ? WHERE id = ?");
                        $stmtUpdate->execute([$password_hash_baru, $admin_id]);

                        $pesan = 'Password berhasil diubah. Silakan gunakan password baru pada sesi login berikutnya.';
                        $tipe_pesan = 'success';
                    } else {
                        $pesan = 'Password lama yang Anda masukkan salah.';
                        $tipe_pesan = 'error';
                    }
                } catch (PDOException $e) {
                    $pesan = 'Terjadi kesalahan sistem: ' . $e->getMessage();
                    $tipe_pesan = 'error';
                }
            }
        } else {
            $pesan = 'Seluruh kolom wajib diisi.';
            $tipe_pesan = 'error';
        }
    }
}

$page_title = 'Ganti Password';

require_once __DIR__ . '/../../includes/bk_header.php';
require_once __DIR__ . '/../../includes/bk_sidebar.php';
require_once __DIR__ . '/../../includes/bk_navbar.php';
?>

<div class="max-w-2xl mx-auto pb-12">

    <!-- Notifikasi -->
    <?php if ($pesan): ?>
        <div class="mb-6 px-4 py-3 rounded-lg border shadow-sm <?= $tipe_pesan === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
            <p class="font-semibold text-sm"><?= htmlspecialchars($pesan) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 md:p-8">
        <div class="mb-6 border-b border-gray-100 pb-4">
            <h3 class="text-xl font-bold text-gray-900">Perbarui Keamanan Akun</h3>
            <p class="text-sm text-gray-500 mt-1">Pastikan Anda menggunakan kombinasi huruf dan angka agar akun tetap aman.</p>
        </div>

        <!-- Action diubah menjadi "" agar submit ke halaman ini sendiri dengan bersih -->
        <form method="POST" action="" class="space-y-5">
            <!-- INPUT HIDDEN CSRF TOKEN -->
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password Lama</label>
                <input type="password" name="password_lama" required class="w-full border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary p-2.5 bg-gray-50 focus:bg-white transition-colors" placeholder="Masukkan password saat ini">
            </div>

            <div class="pt-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                <input type="password" name="password_baru" required class="w-full border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary p-2.5 bg-gray-50 focus:bg-white transition-colors" placeholder="Minimal 6 karakter">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                <input type="password" name="konfirmasi_password" required class="w-full border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary p-2.5 bg-gray-50 focus:bg-white transition-colors" placeholder="Ulangi password baru">
            </div>

            <div class="pt-6 mt-6 flex items-center justify-end gap-3 border-t border-gray-100">
                <a href="dashboard.php" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</a>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-blue-700 shadow-sm transition">
                    Ubah Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>