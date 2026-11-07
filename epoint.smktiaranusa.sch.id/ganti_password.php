<?php
require 'config.php';
require 'includes/auth.php';

$judul_halaman = 'Pengaturan Akun & Ganti Password';

$user_id = (int)$_SESSION['user_id'];
$q_me = mysqli_query($koneksi, "SELECT u.*, k.nama_kelas, k.jurusan FROM users u LEFT JOIN kelas k ON u.kelas_id = k.id WHERE u.id = $user_id");
$me = mysqli_fetch_assoc($q_me);

$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'update_profil') {
            $username = trim(clean($koneksi, $_POST['username'] ?? ''));
            $nama_lengkap = trim(clean($koneksi, $_POST['nama_lengkap'] ?? ''));

            if ($username === '' || $nama_lengkap === '') {
                $error = 'Username dan Nama Lengkap tidak boleh kosong.';
            } else {
                // Cek duplikasi username
                $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ? AND id != ?");
                mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
                mysqli_stmt_execute($stmt);
                $res_cek = mysqli_stmt_get_result($stmt);
                if ($res_cek && mysqli_num_rows($res_cek) > 0) {
                    $error = "Username '<strong>" . htmlspecialchars($username) . "</strong>' sudah digunakan oleh pengguna lain. Silakan pilih username lain.";
                } else {
                    $stmt_upd = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_upd, "ssi", $username, $nama_lengkap, $user_id);
                    mysqli_stmt_execute($stmt_upd);

                    $_SESSION['username'] = $username;
                    $_SESSION['nama_lengkap'] = $nama_lengkap;
                    $sukses = 'Informasi profil dan username berhasil diperbarui!';
                    // Refresh data
                    $me['username'] = $username;
                    $me['nama_lengkap'] = $nama_lengkap;
                }
            }
        } elseif ($aksi === 'ganti_password') {
            $password_lama = $_POST['password_lama'] ?? '';
            $password_baru = $_POST['password_baru'] ?? '';
            $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

            // Verifikasi password lama (mendukung hash bcrypt, plaintext di kolom password, maupun di kolom password_plain)
            $pass_ok = password_verify($password_lama, $me['password'] ?? '')
                       || ($password_lama === ($me['password'] ?? ''))
                       || (!empty($me['password_plain']) && $password_lama === $me['password_plain']);

            if (!$pass_ok) {
                $error = 'Password saat ini (lama) yang Anda masukkan salah.';
            } elseif (strlen($password_baru) < 3) {
                $error = 'Password baru minimal harus 3 karakter.';
            } elseif ($password_baru !== $konfirmasi_password) {
                $error = 'Konfirmasi password baru tidak cocok dengan password baru.';
            } else {
                $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                $plain_clean = clean($koneksi, $password_baru);

                $has_plain_col = false;
                try {
                    $c_plain = @mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
                    $has_plain_col = ($c_plain && mysqli_num_rows($c_plain) > 0);
                } catch (Throwable $e) {}

                if ($has_plain_col) {
                    $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ?, password_plain = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "ssi", $hash_baru, $plain_clean, $user_id);
                } else {
                    $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "si", $hash_baru, $user_id);
                }

                $q_update = mysqli_stmt_execute($stmt);
                if ($q_update) {
                    $sukses = 'Password Anda berhasil diubah! Gunakan password baru ini untuk login berikutnya.';
                    $me['password'] = $hash_baru;
                    $me['password_plain'] = $plain_clean;
                } else {
                    $error = 'Gagal memperbarui database: ' . mysqli_error($koneksi);
                }
            }
        }
    } catch (Throwable $e) {
        $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row g-4">
    <!-- Informasi Akun Saat Ini -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:14px;">
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-3" style="width:72px; height:72px; font-size:2rem; border:2px solid #e2e8f0;">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($me['nama_lengkap']) ?></h5>
                <div class="text-muted small mb-2">@<?= htmlspecialchars($me['username']) ?></div>
                <span class="badge bg-primary px-3 py-1"><?= roleLabel($me['role']) ?></span>
                <?php if (!empty($me['nama_kelas'])): ?>
                    <div class="mt-2">
                        <span class="badge bg-info text-dark">Wali Kelas <?= htmlspecialchars($me['nama_kelas']) ?></span>
                        <?= badgeJurusan($me['jurusan']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <hr>
            <div class="small text-secondary">
                <div class="d-flex justify-content-between py-1">
                    <span>Role Akun:</span>
                    <strong class="text-dark"><?= roleLabel($me['role']) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Terdaftar Sejak:</span>
                    <span class="text-dark"><?= date('d M Y', strtotime($me['created_at'])) ?></span>
                </div>
                <?php if (!empty($me['nama_kelas'])): ?>
                <div class="d-flex justify-content-between py-1">
                    <span>Kelas Perwalian:</span>
                    <strong class="text-dark"><?= htmlspecialchars($me['nama_kelas']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info py-2 px-3 small mt-auto mb-0" style="font-size:0.78rem;">
                <i class="bi bi-info-circle-fill me-1"></i>
                Anda dapat merubah <strong>Username</strong> dan <strong>Password</strong> akun Anda secara mandiri di halaman ini.
            </div>
        </div>
    </div>

    <!-- Form Perubahan -->
    <div class="col-lg-8">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= $error ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($sukses): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div><?= $sukses ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Kartu 1: Ubah Username & Nama -->
        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:14px;">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <i class="bi bi-person-gear text-primary fs-5"></i>
                <h6 class="fw-bold mb-0">Ubah Username &amp; Nama Lengkap</h6>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="update_profil">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">@</span>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($me['username']) ?>" required>
                        </div>
                        <small class="text-muted" style="font-size:0.75rem;">Digunakan saat masuk ke sistem (bebas huruf kecil/besar).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($me['nama_lengkap']) ?>" required>
                        <small class="text-muted" style="font-size:0.75rem;">Nama yang tampil di laporan &amp; topbar sistem.</small>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-save me-1"></i> Simpan Profil &amp; Username
                    </button>
                </div>
            </form>
        </div>

        <!-- Kartu 2: Ganti Password -->
        <div class="card border-0 shadow-sm p-4" style="border-radius:14px;">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <i class="bi bi-shield-lock-fill text-danger fs-5"></i>
                <h6 class="fw-bold mb-0">Ganti Password</h6>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="ganti_password">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Password Saat Ini (Lama)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password_lama" id="p_lama" class="form-control border-start-0 border-end-0" placeholder="Masukkan password lama Anda" required>
                        <button type="button" class="btn btn-eye-toggle" onclick="toggleLihat('p_lama', this)" tabindex="-1" title="Lihat/Sembunyikan password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" name="password_baru" id="p_baru" class="form-control border-start-0 border-end-0" placeholder="Minimal 3 karakter" required>
                            <button type="button" class="btn btn-eye-toggle" onclick="toggleLihat('p_baru', this)" tabindex="-1" title="Lihat/Sembunyikan password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Ulangi Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-check2-circle"></i></span>
                            <input type="password" name="konfirmasi_password" id="p_konf" class="form-control border-start-0 border-end-0" placeholder="Ketik ulang password baru" required>
                            <button type="button" class="btn btn-eye-toggle" onclick="toggleLihat('p_konf', this)" tabindex="-1" title="Lihat/Sembunyikan password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-dark btn-sm px-3">
                        <i class="bi bi-check-lg me-1"></i> Perbarui Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleLihat(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
