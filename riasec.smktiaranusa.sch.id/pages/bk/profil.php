<?php
// pages/bk/profil.php

require_once __DIR__ . '/../../config/session.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$admin_id = $_SESSION['admin_id'] ?? 0;
$pesan = '';
$tipe_pesan = '';

// Proses jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = sanitizeInput($_POST['nama_lengkap'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');

    if (!empty($nama_lengkap) && !empty($username)) {
        try {
            // Cek apakah username sudah dipakai oleh admin lain
            $stmtCek = $pdo->prepare("SELECT id FROM admin_bk WHERE username = ? AND id != ?");
            $stmtCek->execute([$username, $admin_id]);
            
            if ($stmtCek->rowCount() > 0) {
                $pesan = 'Username sudah digunakan oleh akun lain. Silakan gunakan username yang berbeda.';
                $tipe_pesan = 'error';
            } else {
                // Update tabel admin_bk
                $stmtUpdate = $pdo->prepare("UPDATE admin_bk SET nama_lengkap = ?, username = ? WHERE id = ?");
                $stmtUpdate->execute([$nama_lengkap, $username, $admin_id]);
                
                // Perbarui session agar navbar langsung menyesuaikan
                $_SESSION['admin_nama'] = $nama_lengkap;
                
                $pesan = 'Profil berhasil diperbarui!';
                $tipe_pesan = 'success';
            }
        } catch (PDOException $e) {
            $pesan = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            $tipe_pesan = 'error';
        }
    } else {
        $pesan = 'Nama Lengkap dan Username wajib diisi.';
        $tipe_pesan = 'error';
    }
}

// Ambil data admin saat ini dari database untuk mengisi form
$stmt = $pdo->prepare("SELECT username, nama_lengkap FROM admin_bk WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

$page_title = 'Profil Saya';

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

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Header Card -->
        <div class="bg-blue-50/50 p-6 border-b border-gray-100 flex flex-col items-center justify-center">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin['nama_lengkap']) ?>&background=0D8ABC&color=fff&size=128&bold=true" alt="Profile" class="h-24 w-24 rounded-full border-4 border-white shadow-md object-cover mb-4">
            <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($admin['nama_lengkap']) ?></h3>
            <p class="text-sm text-gray-500 font-medium">Administrator Bimbingan Konseling</p>
        </div>

        <!-- Form Edit Profil -->
        <div class="p-6 md:p-8">
            <form method="POST" action="profil.php" class="space-y-6">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($admin['nama_lengkap']) ?>" required class="w-full border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary p-2.5 bg-gray-50 focus:bg-white transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Username (Untuk Login)</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required class="w-full border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary p-2.5 bg-gray-50 focus:bg-white transition-colors">
                    <p class="text-xs text-gray-500 mt-1">Username digunakan untuk mengakses sistem, pastikan Anda mengingatnya.</p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                    <a href="dashboard.php" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</a>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-blue-700 shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>