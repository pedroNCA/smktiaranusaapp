<?php
/**
 * Halaman Profil & Upload Foto Kepala Sekolah
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */

$user_id = $_SESSION['user_id'] ?? 0;
$pesan_sukses = '';
$pesan_error = '';

// Proses update profil, upload foto, dan hapus foto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cek apakah ini aksi hapus foto
    if (isset($_POST['hapus_foto'])) {
        try {
            $stmtOld = $pdo->prepare("SELECT foto FROM tb_users WHERE id_user = :id");
            $stmtOld->execute([':id' => $user_id]);
            $oldFoto = $stmtOld->fetchColumn();
            
            $upload_dir = '../../assets/img/profiles/';
            if ($oldFoto && file_exists($upload_dir . $oldFoto)) {
                unlink($upload_dir . $oldFoto);
            }
            
            $stmtUpdate = $pdo->prepare("UPDATE tb_users SET foto = NULL WHERE id_user = :id");
            $stmtUpdate->execute([':id' => $user_id]);
            
            $pesan_sukses = "Foto profil berhasil dihapus.";
        } catch (Exception $e) {
            $pesan_error = "Gagal menghapus foto: " . $e->getMessage();
        }
    } 
    // 2. Jika bukan hapus foto, berarti aksi update profil
    else {
        $nama_baru = trim($_POST['nama_lengkap'] ?? '');
        $email_baru = trim($_POST['email'] ?? '');
        
        try {
            // Cek apakah ada file foto yang di-upload
            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto_profil']['tmp_name'];
                $file_name = $_FILES['foto_profil']['name'];
                $file_size = $_FILES['foto_profil']['size'];
                
                // Validasi ekstensi file
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($ext, $allowed_exts)) {
                    throw new Exception("Format foto tidak didukung. Harap gunakan JPG, JPEG, PNG, atau WEBP.");
                }
                
                // Validasi ukuran (maksimal 2MB)
                if ($file_size > 2 * 1024 * 1024) {
                    throw new Exception("Ukuran file terlalu besar. Maksimal 2MB.");
                }
                
                // Buat nama file unik
                $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = '../../assets/img/profiles/';
                
                // Pastikan folder tujuan ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $destination = $upload_dir . $new_file_name;
                
                // Ambil foto lama untuk dihapus dari server jika ada
                $stmtOld = $pdo->prepare("SELECT foto FROM tb_users WHERE id_user = :id");
                $stmtOld->execute([':id' => $user_id]);
                $oldFoto = $stmtOld->fetchColumn();
                
                if ($oldFoto && file_exists($upload_dir . $oldFoto)) {
                    unlink($upload_dir . $oldFoto);
                }
                
                // Pindahkan file ke folder tujuan
                if (move_uploaded_file($file_tmp, $destination)) {
                    // Update database beserta foto dan email
                    $stmtUpdate = $pdo->prepare("UPDATE tb_users SET nama_lengkap = :nama, email = :email, foto = :foto WHERE id_user = :id");
                    $stmtUpdate->execute([
                        ':nama'  => $nama_baru,
                        ':email' => $email_baru,
                        ':foto'  => $new_file_name,
                        ':id'    => $user_id
                    ]);
                } else {
                    throw new Exception("Gagal mengunggah file ke server.");
                }
            } else {
                // Update nama dan email saja jika tidak ada foto baru
                $stmtUpdate = $pdo->prepare("UPDATE tb_users SET nama_lengkap = :nama, email = :email WHERE id_user = :id");
                $stmtUpdate->execute([
                    ':nama'  => $nama_baru,
                    ':email' => $email_baru,
                    ':id'    => $user_id
                ]);
            }
            
            // Perbarui sesi
            $_SESSION['nama'] = $nama_baru;
            $pesan_sukses = "Profil berhasil diperbarui!";
            
        } catch (Exception $e) {
            $pesan_error = $e->getMessage();
        }
    }
}

// Ambil data terbaru dari database
try {
    $stmt = $pdo->prepare("SELECT * FROM tb_users WHERE id_user = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $userData = [];
}
?>

<div class="mb-8">
    <h2 class="text-2xl font-display font-bold text-slate-900">Profil Saya</h2>
    <p class="text-sm text-slate-500 mt-1">Kelola informasi akun, identitas, dan foto profil.</p>
</div>

<?php if (!empty($pesan_sukses)): ?>
    <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200"><?= $pesan_sukses ?></div>
<?php endif; ?>

<?php if (!empty($pesan_error)): ?>
    <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200"><?= $pesan_error ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Kartu Informasi Ringkas & Foto (Kiri) -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 text-center">
        <div class="mx-auto w-28 h-28 rounded-full overflow-hidden bg-primary/10 text-primary border-4 border-white shadow-md flex items-center justify-center font-bold text-3xl mb-4">
            <?php if (!empty($userData['foto']) && file_exists('../../assets/img/profiles/' . $userData['foto'])): ?>
                <img src="../../assets/img/profiles/<?= htmlspecialchars($userData['foto']) ?>" alt="Foto Profil" class="w-full h-full object-cover">
            <?php else: ?>
                <span class="uppercase"><?= substr($userData['nama_lengkap'] ?? 'K', 0, 1) ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Tombol Hapus Foto -->
        <?php if (!empty($userData['foto']) && file_exists('../../assets/img/profiles/' . $userData['foto'])): ?>
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto profil?');" class="mb-4">
                <input type="hidden" name="hapus_foto" value="1">
                <button type="submit" class="inline-flex items-center justify-center text-xs text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg border border-transparent hover:border-red-100 transition-colors font-medium">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Hapus Foto
                </button>
            </form>
        <?php endif; ?>

        <h3 class="font-display text-lg font-bold text-slate-900"><?= htmlspecialchars($userData['nama_lengkap'] ?? '-') ?></h3>
        <p class="text-xs font-semibold text-primary mt-1"><?= htmlspecialchars($userData['role'] ?? 'Kepala Sekolah') ?></p>
        
        <div class="mt-6 border-t border-slate-100 pt-6 text-left space-y-3">
            <div class="flex justify-between text-xs">
                <span class="text-slate-400">Bidang:</span>
                <span class="font-medium text-slate-700"><?= htmlspecialchars($userData['bidang'] ?? 'Semua') ?></span>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-slate-400">Status Akun:</span>
                <span class="font-medium text-emerald-600">Aktif & Aman</span>
            </div>
        </div>
    </div>

    <!-- Form Edit Profil & Upload Foto (Kanan) -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 lg:col-span-2">
        <h3 class="font-display text-lg font-semibold text-slate-900 mb-4">Pengaturan & Unggah Foto</h3>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($userData['nama_lengkap'] ?? '') ?>" required class="w-full rounded-lg border border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Alamat Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="contoh@sekolah.sch.id">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Ganti Foto Profil (JPG, PNG, WEBP - Maks 2MB)</label>
                <input type="file" name="foto_profil" accept=".jpg, .jpeg, .png, .webp" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-lg">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Username</label>
                <input type="text" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" disabled class="w-full rounded-lg bg-slate-100 border border-slate-200 text-sm p-2.5 text-slate-500 cursor-not-allowed">
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="bg-primary text-white text-sm font-semibold py-2.5 px-5 rounded-lg hover:bg-blue-800 transition-colors shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>

<?php
require_once '../../includes/kepsek_footer.php';
?>