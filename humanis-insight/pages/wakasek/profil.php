<?php

/**
 * Halaman Profil & Upload Foto Wakil Kepala Sekolah
 */
require_once '../../includes/wakasek_header.php';
require_once '../../includes/wakasek_sidebar.php';
require_once '../../includes/wakasek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */
$user_id = $_SESSION['user_id'] ?? 0;
$pesan_sukses = '';
$pesan_error = '';

// Proses update profil dan upload foto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_baru = trim($_POST['nama_lengkap'] ?? '');
    $email_baru = trim($_POST['email'] ?? '');

    try {
        $data_update = [':nama' => $nama_baru, ':email' => $email_baru, ':id' => $user_id];
        $sql = "UPDATE tb_users SET nama_lengkap = :nama, email = :email";

        // Cek jika ada file foto yang di-upload
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto_profil']['tmp_name'];
            $file_name = $_FILES['foto_profil']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new Exception("Format foto tidak didukung.");
            }
            if ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran file maksimal 2MB.");
            }

            $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $upload_dir = '../../assets/img/profiles/';

            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Hapus foto lama
            $stmtOld = $pdo->prepare("SELECT foto FROM tb_users WHERE id_user = :id");
            $stmtOld->execute([':id' => $user_id]);
            $oldFoto = $stmtOld->fetchColumn();
            if ($oldFoto && file_exists($upload_dir . $oldFoto)) unlink($upload_dir . $oldFoto);

            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $sql .= ", foto = :foto";
                $data_update[':foto'] = $new_file_name;
            } else {
                throw new Exception("Gagal mengunggah foto.");
            }
        }

        $sql .= " WHERE id_user = :id";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($data_update);

        $_SESSION['nama'] = $nama_baru;
        $pesan_sukses = "Profil dan foto berhasil diperbarui!";
    } catch (Exception $e) {
        $pesan_error = $e->getMessage();
    }
}

// Ambil data terbaru
$stmt = $pdo->prepare("SELECT * FROM tb_users WHERE id_user = :id LIMIT 1");
$stmt->execute([':id' => $user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-5xl mx-auto pb-12">
    <div class="mb-8">
        <h2 class="text-2xl font-display font-bold text-slate-900">Profil Saya</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola informasi akun dan foto profil Anda.</p>
    </div>

    <?php if ($pesan_sukses): ?>
        <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200"><?= $pesan_sukses ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200"><?= $pesan_error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Kartu Informasi (Kiri) -->
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 text-center h-fit">
            <div class="mx-auto w-28 h-28 rounded-full overflow-hidden bg-primary/10 border-4 border-white shadow-md flex items-center justify-center font-bold text-3xl mb-4">
                <?php if (!empty($userData['foto']) && file_exists('../../assets/img/profiles/' . $userData['foto'])): ?>
                    <img src="../../assets/img/profiles/<?= htmlspecialchars($userData['foto']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-primary uppercase"><?= substr($userData['nama_lengkap'] ?? 'W', 0, 1) ?></span>
                <?php endif; ?>
            </div>
            <h3 class="font-display text-lg font-bold text-slate-900"><?= htmlspecialchars($userData['nama_lengkap'] ?? '-') ?></h3>
            <p class="text-xs font-semibold text-primary mt-1"><?= htmlspecialchars($userData['role'] ?? 'Wakil Kepala') ?></p>
            <p class="text-xs text-slate-500 mt-0.5">Bidang: <?= htmlspecialchars($userData['bidang'] ?? '-') ?></p>
        </div>

        <!-- Form Edit (Kanan) -->
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 lg:col-span-2">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">

                <!-- Username (Tidak Bisa Diubah) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Username</label>
                    <input type="text" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" disabled class="w-full rounded-lg border border-slate-300 bg-slate-100 text-slate-500 text-sm p-2.5 cursor-not-allowed">
                </div>

                <!-- Nama Lengkap (Bisa Diubah) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($userData['nama_lengkap'] ?? '') ?>" required class="w-full rounded-lg border border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                </div>

                <!-- Email (Bisa Diubah) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                </div>

                <!-- Role (Tidak Bisa Diubah) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Role / Hak Akses</label>
                    <input type="text" value="<?= htmlspecialchars($userData['role'] ?? '') ?>" disabled class="w-full rounded-lg border border-slate-300 bg-slate-100 text-slate-500 text-sm p-2.5 cursor-not-allowed">
                </div>

                <!-- Bidang (Tidak Bisa Diubah) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Bidang</label>
                    <input type="text" value="<?= htmlspecialchars($userData['bidang'] ?? '-') ?>" disabled class="w-full rounded-lg border border-slate-300 bg-slate-100 text-slate-500 text-sm p-2.5 cursor-not-allowed">
                </div>

                <!-- Ganti Foto Profil -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Ganti Foto Profil (Opsional)</label>
                    <input type="file" name="foto_profil" accept=".jpg, .jpeg, .png, .webp" class="w-full text-sm border border-slate-300 rounded-lg p-2 file:mr-4 file:py-1 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <p class="text-[11px] text-slate-500 mt-1">Format: JPG, JPEG, PNG, WEBP. Maksimal ukuran 2MB.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-primary text-white text-sm font-semibold py-2.5 px-6 rounded-xl hover:bg-blue-800 transition-colors shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/wakasek_footer.php'; ?>