<?php
require 'config.php';
require 'includes/auth.php';
hanyaAdmin();
$judul_halaman = 'Master Kategori Prestasi';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'];
    $kode = clean($koneksi, $_POST['kode']);
    $nama = clean($koneksi, $_POST['nama_prestasi']);
    $tingkat = clean($koneksi, $_POST['tingkat']);
    $poin = (int)$_POST['poin'];
    if ($aksi === 'tambah') {
        mysqli_query($koneksi, "INSERT INTO kategori_prestasi (kode, nama_prestasi, tingkat, poin) VALUES ('$kode','$nama','$tingkat',$poin)");
        redirect('kategori_prestasi.php', 'Kategori prestasi berhasil ditambahkan.');
    } else {
        $id = (int)$_POST['id'];
        mysqli_query($koneksi, "UPDATE kategori_prestasi SET kode='$kode', nama_prestasi='$nama', tingkat='$tingkat', poin=$poin WHERE id=$id");
        redirect('kategori_prestasi.php', 'Kategori prestasi berhasil diperbarui.');
    }
}
if (($_GET['aksi'] ?? '') === 'hapus') {
    $id = (int)$_GET['id'];
    mysqli_query($koneksi, "DELETE FROM kategori_prestasi WHERE id=$id");
    redirect('kategori_prestasi.php', 'Kategori berhasil dihapus.');
}

$data = mysqli_query($koneksi, "SELECT * FROM kategori_prestasi ORDER BY poin");
include 'includes/header.php';
?>
<div class="card p-3">
    <div class="d-flex justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Master Kategori Prestasi</h6>
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="modeTambah()"><i class="bi bi-plus-lg"></i> Tambah</button>
    </div>
    <table class="table table-hover align-middle">
        <thead><tr><th>Kode</th><th>Nama Prestasi</th><th>Tingkat</th><th>Poin</th><th class="text-center">Aksi</th></tr></thead>
        <tbody>
        <?php while ($d = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td><?= htmlspecialchars($d['kode']) ?></td>
                <td><?= htmlspecialchars($d['nama_prestasi']) ?></td>
                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($d['tingkat']) ?></span></td>
                <td class="fw-bold text-success">+<?= $d['poin'] ?></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick='modeEdit(<?= json_encode($d) ?>)' data-bs-toggle="modal" data-bs-target="#modalKategori"><i class="bi bi-pencil"></i></button>
                    <a href="?aksi=hapus&id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalKategori" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <div class="modal-header"><h5 class="modal-title" id="judulModal">Tambah Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="aksi" id="f_aksi" value="tambah">
        <div class="mb-2">
            <label class="form-label">Kode</label>
            <input type="text" name="kode" id="f_kode" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Nama Prestasi</label>
            <input type="text" name="nama_prestasi" id="f_nama" class="form-control" required>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label">Tingkat</label>
                <select name="tingkat" id="f_tingkat" class="form-select">
                    <option>Sekolah</option>
                    <option>Kecamatan</option>
                    <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                    <option>Provinsi</option>
                    <option>Nasional</option>
                    <option>Internasional</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Poin (ditambah)</label>
                <input type="number" name="poin" id="f_poin" class="form-control" min="0" required>
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
function modeTambah(){
    document.getElementById('judulModal').innerText='Tambah Kategori Prestasi';
    document.getElementById('f_aksi').value='tambah';
    document.getElementById('f_id').value='';
    document.getElementById('f_kode').value='';
    document.getElementById('f_nama').value='';
    document.getElementById('f_poin').value='';
    document.getElementById('f_tingkat').value='Sekolah';
}
function modeEdit(d){
    document.getElementById('judulModal').innerText='Edit Kategori Prestasi';
    document.getElementById('f_aksi').value='edit';
    document.getElementById('f_id').value=d.id;
    document.getElementById('f_kode').value=d.kode;
    document.getElementById('f_nama').value=d.nama_prestasi;
    document.getElementById('f_poin').value=d.poin;
    document.getElementById('f_tingkat').value=d.tingkat;
}
</script>
<?php include 'includes/footer.php'; ?>
