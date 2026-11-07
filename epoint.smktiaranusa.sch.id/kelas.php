<?php
require 'config.php';
require 'includes/auth.php';
hanyaAdmin();
$judul_halaman = 'Data Kelas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'];
    $nama_kelas = clean($koneksi, $_POST['nama_kelas']);
    $jurusan = clean($koneksi, $_POST['jurusan']);
    if (!in_array($jurusan, ['MPLB', 'TJKT'])) $jurusan = 'MPLB';
    $wali = clean($koneksi, $_POST['wali_kelas']);

    if ($aksi === 'tambah') {
        mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas, jurusan, wali_kelas) VALUES ('$nama_kelas','$jurusan','$wali')");
        redirect('kelas.php', 'Kelas berhasil ditambahkan.');
    } elseif ($aksi === 'edit') {
        $id = (int)$_POST['id'];
        mysqli_query($koneksi, "UPDATE kelas SET nama_kelas='$nama_kelas', jurusan='$jurusan', wali_kelas='$wali' WHERE id=$id");
        redirect('kelas.php', 'Kelas berhasil diperbarui.');
    }
}
if (($_GET['aksi'] ?? '') === 'hapus') {
    $id = (int)$_GET['id'];
    $jumlah_siswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM siswa WHERE kelas_id=$id"))['n'];
    if ($jumlah_siswa > 0) {
        redirect('kelas.php', 'Tidak bisa hapus, masih ada siswa di kelas ini.', 'danger');
    }
    mysqli_query($koneksi, "DELETE FROM kelas WHERE id=$id");
    redirect('kelas.php', 'Kelas berhasil dihapus.');
}

$filter_jurusan = clean($koneksi, $_GET['jurusan'] ?? '');
$sql = "SELECT k.*, (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id=k.id) jumlah_siswa FROM kelas k WHERE 1=1";
if ($filter_jurusan !== '') {
    $sql .= " AND k.jurusan = '$filter_jurusan'";
}
$sql .= " ORDER BY k.jurusan, k.nama_kelas";
$data = mysqli_query($koneksi, $sql);

include 'includes/header.php';
?>
<div class="card p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold mb-0">Daftar Kelas</h6>
            <div class="btn-group btn-group-sm ms-2">
                <a href="kelas.php" class="btn <?= $filter_jurusan===''?'btn-dark':'btn-outline-secondary' ?>">Semua</a>
                <a href="kelas.php?jurusan=MPLB" class="btn <?= $filter_jurusan==='MPLB'?'btn-primary':'btn-outline-secondary' ?>">MPLB</a>
                <a href="kelas.php?jurusan=TJKT" class="btn <?= $filter_jurusan==='TJKT'?'btn-info text-white':'btn-outline-secondary' ?>">TJKT</a>
            </div>
        </div>
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalKelas" onclick="modeTambah()">
            <i class="bi bi-plus-lg"></i> Tambah Kelas
        </button>
    </div>
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Nama Kelas</th>
                <th>Jurusan</th>
                <th>Wali Kelas</th>
                <th>Jumlah Siswa</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($data) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data kelas</td></tr>
        <?php endif; ?>
        <?php while ($k = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td><strong><?= htmlspecialchars($k['nama_kelas']) ?></strong></td>
                <td><?= badgeJurusan($k['jurusan']) ?></td>
                <td><?= htmlspecialchars($k['wali_kelas'] ?: '-') ?></td>
                <td><span class="badge bg-secondary"><?= $k['jumlah_siswa'] ?> Siswa</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick='modeEdit(<?= json_encode($k) ?>)' data-bs-toggle="modal" data-bs-target="#modalKelas"><i class="bi bi-pencil"></i></button>
                    <a href="?aksi=hapus&id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kelas ini?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="modalKelas" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title" id="judulModalKelas">Tambah Kelas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="aksi" id="f_aksi" value="tambah">
        <div class="mb-2">
            <label class="form-label">Nama Kelas</label>
            <input type="text" name="nama_kelas" id="f_nama_kelas" class="form-control" placeholder="Contoh: X MPLB 1 atau XI TJKT" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Jurusan</label>
            <select name="jurusan" id="f_jurusan" class="form-select" required>
                <option value="MPLB">MPLB (Manajemen Perkantoran &amp; Layanan Bisnis)</option>
                <option value="TJKT">TJKT (Teknik Jaringan Komputer &amp; Telekomunikasi)</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Wali Kelas</label>
            <input type="text" name="wali_kelas" id="f_wali" class="form-control" placeholder="Nama Guru Wali Kelas">
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
function modeTambah(){
    document.getElementById('judulModalKelas').innerText='Tambah Kelas';
    document.getElementById('f_aksi').value='tambah';
    document.getElementById('f_id').value='';
    document.getElementById('f_nama_kelas').value='';
    document.getElementById('f_jurusan').value='MPLB';
    document.getElementById('f_wali').value='';
}
function modeEdit(k){
    document.getElementById('judulModalKelas').innerText='Edit Kelas';
    document.getElementById('f_aksi').value='edit';
    document.getElementById('f_id').value=k.id;
    document.getElementById('f_nama_kelas').value=k.nama_kelas;
    document.getElementById('f_jurusan').value=k.jurusan || 'MPLB';
    document.getElementById('f_wali').value=k.wali_kelas ?? '';
}
</script>
<?php include 'includes/footer.php'; ?>
