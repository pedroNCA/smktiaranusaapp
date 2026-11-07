<?php
require 'config.php';
require 'includes/auth.php';
hanyaAdmin();
$judul_halaman = 'Kelola User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $aksi = $_POST['aksi'] ?? '';
        $username = trim(clean($koneksi, $_POST['username'] ?? ''));
        $nama = trim(clean($koneksi, $_POST['nama_lengkap'] ?? ''));
        $role = trim(clean($koneksi, $_POST['role'] ?? ''));
        $password = $_POST['password'] ?? '';

        // Validasi role yang diizinkan
        $allowed_roles = ['admin', 'guru_bk', 'wali_kelas', 'guru', 'kepala_sekolah', 'osis'];
        if (!in_array($role, $allowed_roles)) {
            $role = 'guru_bk';
        }
        $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null;

        $has_plain_col = false;
        try {
            $c_plain = @mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
            $has_plain_col = ($c_plain && mysqli_num_rows($c_plain) > 0);
        } catch (Throwable $e) {}

        if ($username === '' || $nama === '') {
            redirect('users.php', 'Username dan Nama Lengkap wajib diisi.', 'danger');
        }

        if ($aksi === 'tambah') {
            if ($password === '') {
                redirect('users.php', 'Password wajib diisi untuk user baru.', 'danger');
            }
            $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $res_cek = mysqli_stmt_get_result($stmt);
            if ($res_cek && mysqli_num_rows($res_cek) > 0) {
                redirect('users.php', 'Username sudah digunakan oleh akun lain.', 'danger');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pass_plain = clean($koneksi, $password);

            if ($has_plain_col) {
                if ($kelas_id !== null) {
                    $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, password_plain, nama_lengkap, role, kelas_id) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "sssssi", $username, $hash, $pass_plain, $nama, $role, $kelas_id);
                } else {
                    $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, password_plain, nama_lengkap, role, kelas_id) VALUES (?, ?, ?, ?, ?, NULL)");
                    mysqli_stmt_bind_param($stmt, "sssss", $username, $hash, $pass_plain, $nama, $role);
                }
            } else {
                if ($kelas_id !== null) {
                    $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, nama_lengkap, role, kelas_id) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "ssssi", $username, $hash, $nama, $role, $kelas_id);
                } else {
                    $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, nama_lengkap, role, kelas_id) VALUES (?, ?, ?, ?, NULL)");
                    mysqli_stmt_bind_param($stmt, "ssss", $username, $hash, $nama, $role);
                }
            }

            $exec = mysqli_stmt_execute($stmt);
            if ($exec) {
                redirect('users.php', 'User baru berhasil ditambahkan.');
            } else {
                redirect('users.php', 'Gagal menambahkan user baru: ' . mysqli_error($koneksi), 'danger');
            }
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                redirect('users.php', 'ID pengguna tidak valid.', 'danger');
            }

            // Validasi duplikasi username terhadap user lain
            $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ? AND id != ?");
            mysqli_stmt_bind_param($stmt, "si", $username, $id);
            mysqli_stmt_execute($stmt);
            $res_cek = mysqli_stmt_get_result($stmt);
            if ($res_cek && mysqli_num_rows($res_cek) > 0) {
                redirect('users.php', 'Username sudah digunakan oleh akun lain.', 'danger');
            }

            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pass_plain = clean($koneksi, $password);

                if ($has_plain_col) {
                    if ($kelas_id !== null) {
                        $stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, kelas_id = ?, password = ?, password_plain = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "sssisss", $username, $nama, $role, $kelas_id, $hash, $pass_plain, $id);
                    } else {
                        $stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, kelas_id = NULL, password = ?, password_plain = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "sssssi", $username, $nama, $role, $hash, $pass_plain, $id);
                    }
                } else {
                    if ($kelas_id !== null) {
                        $stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, kelas_id = ?, password = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "sssisi", $username, $nama, $role, $kelas_id, $hash, $id);
                    } else {
                        $stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, kelas_id = NULL, password = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "ssssi", $username, $nama, $role, $hash, $id);
                    }
                }
            } else {
                if ($kelas_id !== null) {
                    $stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, kelas_id = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "sssii", $username, $nama, $role, $kelas_id, $id);
                } else {
                    $stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, kelas_id = NULL WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "sssi", $username, $nama, $role, $id);
                }
            }

            $exec = mysqli_stmt_execute($stmt);
            if ($exec) {
                // Jika user yang diubah adalah akun admin yang sedang login, update sesi agar sinkron
                if ($id === (int)($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['username'] = $username;
                    $_SESSION['nama_lengkap'] = $nama;
                    $_SESSION['role'] = $role;
                }
                redirect('users.php', 'User berhasil diperbarui.');
            } else {
                redirect('users.php', 'Gagal memperbarui user: ' . mysqli_error($koneksi), 'danger');
            }
        }
    } catch (Throwable $e) {
        redirect('users.php', 'Terjadi kesalahan sistem: ' . $e->getMessage(), 'danger');
    }
}

if (($_GET['aksi'] ?? '') === 'hapus') {
    $id = (int)$_GET['id'];
    if ($id === (int)$_SESSION['user_id']) {
        redirect('users.php', 'Tidak bisa menghapus akun sendiri yang sedang login.', 'danger');
    }
    mysqli_query($koneksi, "DELETE FROM users WHERE id=$id");
    redirect('users.php', 'User berhasil dihapus.');
}

$has_plain_col = false;
try {
    $c_plain = @mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
    $has_plain_col = ($c_plain && mysqli_num_rows($c_plain) > 0);
} catch (Throwable $e) {}
$f_plain = $has_plain_col ? 'u.password_plain,' : "'' AS password_plain,";

$data = mysqli_query($koneksi, "SELECT u.id, u.username, $f_plain u.nama_lengkap, u.role, u.kelas_id, u.created_at, k.nama_kelas, k.jurusan FROM users u LEFT JOIN kelas k ON u.kelas_id = k.id ORDER BY u.role, k.nama_kelas, u.nama_lengkap");
$list_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY jurusan, nama_kelas");
$roleBadges = [
    'admin'          => ['Administrator', 'dark'],
    'kepala_sekolah' => ['Kepala Sekolah', 'warning text-dark fw-bold'],
    'guru_bk'        => ['Guru BK', 'primary'],
    'wali_kelas'     => ['Wali Kelas', 'info'],
    'guru'           => ['Guru', 'secondary'],
    'osis'           => ['Petugas OSIS', 'success']
];

include 'includes/header.php';
?>
<div class="card p-3">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h6 class="fw-bold mb-0">Daftar Akun Pengguna (Users)</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="modeTambahOsis()">
                <i class="bi bi-shield-plus me-1"></i> Tambah Petugas OSIS
            </button>
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="modeTambah()">
                <i class="bi bi-plus-lg"></i> Tambah User
            </button>
        </div>
    </div>
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Username</th><th>Nama Lengkap</th><th>Role / Peran</th><th>Penugasan Kelas</th><th>Password</th><th class="text-center">Aksi</th></tr></thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($data)):
            $badge = $roleBadges[$u['role']] ?? [ucfirst($u['role']), 'secondary'];
            $pwd_show = !empty($u['password_plain']) ? $u['password_plain'] : ($u['username'] . '123');
        ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                <td><span class="badge bg-<?= $badge[1] ?>"><?= $badge[0] ?></span></td>
                <td>
                    <?php if (!empty($u['nama_kelas'])): ?>
                        <span class="fw-semibold text-dark me-1"><?= htmlspecialchars($u['nama_kelas']) ?></span>
                        <?= badgeJurusan($u['jurusan']) ?>
                    <?php else: ?>
                        <span class="text-muted small">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                        <code class="text-dark fw-semibold"><?= htmlspecialchars($pwd_show) ?></code>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($pwd_show) ?>'); alert('Password berhasil disalin!')" title="Salin Password">
                            <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                        </button>
                    </div>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick='modeEdit(<?= htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)' data-bs-toggle="modal" data-bs-target="#modalUser"><i class="bi bi-pencil"></i></button>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <a href="?aksi=hapus&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus user ini?')"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title" id="judulModal">Tambah User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="aksi" id="f_aksi" value="tambah">
        <div class="mb-2">
            <label class="form-label">Username</label>
            <input type="text" name="username" id="f_username" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" id="f_nama" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Role / Peran</label>
            <select name="role" id="f_role" class="form-select">
                <option value="guru_bk">Guru BK (Bimbingan Konseling)</option>
                <option value="wali_kelas">Wali Kelas</option>
                <option value="kepala_sekolah">Kepala Sekolah (Executive Monitoring)</option>
                <option value="admin">Administrator</option>
                <option value="guru">Guru Pengajar (Umum)</option>
                <option value="osis">Siswa / Petugas OSIS (Input Pelanggaran)</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Penugasan Kelas <small class="text-muted">(Khusus Wali Kelas)</small></label>
            <select name="kelas_id" id="f_kelas_id" class="form-select">
                <option value="">-- Bukan Wali Kelas / Tanpa Kelas --</option>
                <?php mysqli_data_seek($list_kelas, 0); while ($kls = mysqli_fetch_assoc($list_kelas)): ?>
                    <option value="<?= $kls['id'] ?>"><?= htmlspecialchars($kls['nama_kelas']) ?> (<?= $kls['jurusan'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Password <small class="text-muted" id="ket_pw"></small></label>
            <div class="input-group">
                <input type="password" name="password" id="f_password" class="form-control" placeholder="Kosongkan jika tidak ingin diubah">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleModalPassword()" title="Lihat/Sembunyikan password">
                    <i class="bi bi-eye" id="toggleModalPwIcon"></i>
                </button>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-dark">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function toggleModalPassword() {
    const inp = document.getElementById('f_password');
    const ico = document.getElementById('toggleModalPwIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'bi bi-eye';
    }
}
function modeTambah(){
    document.getElementById('judulModal').innerText='Tambah User';
    document.getElementById('f_aksi').value='tambah';
    document.getElementById('f_id').value='';
    document.getElementById('f_username').value='';
    document.getElementById('f_username').placeholder='Username unik';
    document.getElementById('f_nama').value='';
    document.getElementById('f_nama').placeholder='Nama lengkap';
    document.getElementById('f_role').value='guru_bk';
    document.getElementById('f_kelas_id').value='';
    document.getElementById('f_password').value='';
    document.getElementById('f_password').type='password';
    document.getElementById('toggleModalPwIcon').className='bi bi-eye';
    document.getElementById('f_password').required = true;
    document.getElementById('f_password').placeholder='Password akun';
    document.getElementById('ket_pw').innerText='';
}
function modeTambahOsis(){
    document.getElementById('judulModal').innerText='Tambah Akun Petugas OSIS';
    document.getElementById('f_aksi').value='tambah';
    document.getElementById('f_id').value='';
    document.getElementById('f_username').value='';
    document.getElementById('f_username').placeholder='Contoh: osis7';
    document.getElementById('f_nama').value='';
    document.getElementById('f_nama').placeholder='Contoh: Petugas OSIS 7 / Nama Siswa';
    document.getElementById('f_role').value='osis';
    document.getElementById('f_kelas_id').value='';
    document.getElementById('f_password').value='';
    document.getElementById('f_password').type='password';
    document.getElementById('toggleModalPwIcon').className='bi bi-eye';
    document.getElementById('f_password').required = true;
    document.getElementById('f_password').placeholder='Masukkan password untuk akun OSIS ini';
    document.getElementById('ket_pw').innerText='(Wajib diisi)';
}
function modeEdit(u){
    document.getElementById('judulModal').innerText='Edit User';
    document.getElementById('f_aksi').value='edit';
    document.getElementById('f_id').value=u.id;
    document.getElementById('f_username').value=u.username;
    document.getElementById('f_nama').value=u.nama_lengkap;
    document.getElementById('f_role').value=u.role;
    document.getElementById('f_kelas_id').value=u.kelas_id || '';
    document.getElementById('f_password').value='';
    document.getElementById('f_password').type='password';
    document.getElementById('toggleModalPwIcon').className='bi bi-eye';
    document.getElementById('f_password').required = false;
    document.getElementById('ket_pw').innerText = u.password_plain ? '(kosongkan jika tidak diubah, saat ini: ' + u.password_plain + ')' : '(kosongkan jika tidak diubah)';
}
</script>
<?php include 'includes/footer.php'; ?>
