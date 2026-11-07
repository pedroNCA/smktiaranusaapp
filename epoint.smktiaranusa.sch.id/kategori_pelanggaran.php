<?php
require 'config.php';
require 'includes/auth.php';
hanyaAdmin();
$judul_halaman = 'Master Kategori Pelanggaran';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $aksi = $_POST['aksi'];
    $kode = clean($koneksi, $_POST['kode']);
    $kategori = clean($koneksi, $_POST['kategori'] ?? 'Umum');
    $nama = clean($koneksi, $_POST['nama_pelanggaran']);
    $tingkat = clean($koneksi, $_POST['tingkat']);
    $poin = (int)$_POST['poin'];
    if ($aksi === 'tambah') {
        mysqli_query($koneksi, "INSERT INTO kategori_pelanggaran (kode, kategori, nama_pelanggaran, tingkat, poin) VALUES ('$kode','$kategori','$nama','$tingkat',$poin)");
        redirect('kategori_pelanggaran.php', 'Kategori pelanggaran berhasil ditambahkan.');
    } else {
        $id = (int)$_POST['id'];
        mysqli_query($koneksi, "UPDATE kategori_pelanggaran SET kode='$kode', kategori='$kategori', nama_pelanggaran='$nama', tingkat='$tingkat', poin=$poin WHERE id=$id");
        redirect('kategori_pelanggaran.php', 'Kategori pelanggaran berhasil diperbarui.');
    }
}
if (($_GET['aksi'] ?? '') === 'hapus') {
    $id = (int)$_GET['id'];
    mysqli_query($koneksi, "DELETE FROM kategori_pelanggaran WHERE id=$id");
    redirect('kategori_pelanggaran.php', 'Kategori berhasil dihapus.');
}

// Filter kategori jika dipilih via GET
$filter_kat = clean($koneksi, $_GET['filter_kat'] ?? '');
$where_kat = $filter_kat ? "WHERE kategori = '$filter_kat'" : "";

$data = mysqli_query($koneksi, "SELECT * FROM kategori_pelanggaran $where_kat ORDER BY id ASC");
$all_kategori = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM kategori_pelanggaran ORDER BY kategori ASC");

// Ringkasan stat
$total_item = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM kategori_pelanggaran"))['c'];
$total_ringan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM kategori_pelanggaran WHERE tingkat='Ringan'"))['c'];
$total_sedang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM kategori_pelanggaran WHERE tingkat='Sedang'"))['c'];
$total_berat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM kategori_pelanggaran WHERE tingkat='Berat'"))['c'];

include 'includes/header.php';
?>

<!-- Ringkasan Statistik -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm border-start border-4 border-dark">
            <div class="text-muted small">Total Jenis Pelanggaran</div>
            <div class="fs-4 fw-bold text-dark"><?= $total_item ?> Aturan</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm border-start border-4 border-info">
            <div class="text-muted small">Tingkat Ringan</div>
            <div class="fs-4 fw-bold text-info"><?= $total_ringan ?> Jenis</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm border-start border-4 border-warning">
            <div class="text-muted small">Tingkat Sedang</div>
            <div class="fs-4 fw-bold text-warning"><?= $total_sedang ?> Jenis</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm border-start border-4 border-danger">
            <div class="text-muted small">Tingkat Berat</div>
            <div class="fs-4 fw-bold text-danger"><?= $total_berat ?> Jenis</div>
        </div>
    </div>
</div>

<!-- Ketentuan Khusus Tata Tertib Rambut Siswa Putra -->
<div class="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-start gap-3">
    <i class="bi bi-info-circle-fill fs-4 text-info mt-1"></i>
    <div>
        <h6 class="fw-bold mb-1">Ketentuan Khusus Tata Tertib Rambut Putra (SMKS Tiara Nusa)</h6>
        <ul class="mb-0 small ps-3">
            <li>Panjang maksimal 5 cm, samping tidak menyentuh telinga, belakang tidak menyentuh kerah baju.</li>
            <li>Rambut tidak menutupi alis, telinga, dan kerah baju.</li>
            <li>Tidak dicat/diwarnai, tidak bermodel ekstrem/mohawk, tidak bertato, dan tidak bertindik.</li>
        </ul>
    </div>
</div>

<div class="card border-0 shadow-sm p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="fw-bold mb-0">Daftar Jenis Pelanggaran & Poin</h6>
            <small class="text-muted">Master data tata tertib dan sanksi poin SMKS Tiara Nusa</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="modeTambah()">
                <i class="bi bi-plus-lg"></i> Tambah Pelanggaran
            </button>
        </div>
    </div>

    <!-- Filter dan Pencarian -->
    <div class="row g-2 mb-3">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control bg-light" placeholder="Cari kode atau nama pelanggaran...">
            </div>
        </div>
        <div class="col-md-4">
            <select id="filterKategori" class="form-select form-select-sm" onchange="filterTabel()">
                <option value="">-- Semua Kategori --</option>
                <?php while ($kat = mysqli_fetch_assoc($all_kategori)): ?>
                    <option value="<?= htmlspecialchars($kat['kategori']) ?>" <?= $filter_kat === $kat['kategori'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['kategori']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterTingkat" class="form-select form-select-sm" onchange="filterTabel()">
                <option value="">-- Semua Tingkat --</option>
                <option value="Ringan">Ringan</option>
                <option value="Sedang">Sedang</option>
                <option value="Berat">Berat</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tabelPelanggaran">
            <thead class="table-light">
                <tr>
                    <th style="width: 90px;">Kode</th>
                    <th>Kategori / Bidang</th>
                    <th>Nama Pelanggaran</th>
                    <th style="width: 100px;">Tingkat</th>
                    <th style="width: 90px;" class="text-end">Poin</th>
                    <th style="width: 90px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            mysqli_data_seek($data, 0);
            while ($d = mysqli_fetch_assoc($data)):
                $badgeClass = [
                    'Ringan' => 'bg-info text-dark',
                    'Sedang' => 'bg-warning text-dark',
                    'Berat'  => 'bg-danger text-white'
                ][$d['tingkat']] ?? 'bg-secondary';
            ?>
                <tr class="row-pelanggaran" data-kategori="<?= htmlspecialchars($d['kategori']) ?>" data-tingkat="<?= htmlspecialchars($d['tingkat']) ?>">
                    <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($d['kode']) ?></span></td>
                    <td><span class="text-muted small fw-semibold"><?= htmlspecialchars($d['kategori']) ?></span></td>
                    <td class="fw-semibold text-dark nama-pelanggaran"><?= htmlspecialchars($d['nama_pelanggaran']) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $d['tingkat'] ?></span></td>
                    <td class="text-end fw-bold text-danger">-<?= $d['poin'] ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick='modeEdit(<?= json_encode($d) ?>)' data-bs-toggle="modal" data-bs-target="#modalKategori" title="Edit"><i class="bi bi-pencil"></i></button>
                        <a href="?aksi=hapus&id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalKategori" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title" id="judulModal">Tambah Kategori Pelanggaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="aksi" id="f_aksi" value="tambah">
        
        <div class="mb-2">
            <label class="form-label">Kategori / Bidang</label>
            <input type="text" name="kategori" id="f_kategori" list="listKategori" class="form-control" placeholder="Pilih atau ketik kategori..." required>
            <datalist id="listKategori">
                <option value="Kehadiran (KH)">
                <option value="Seragam, Penampilan dan Kerapihan (SPK)">
                <option value="Ketertiban Pembelajaran (KP)">
                <option value="Kebersihan dan Tanggung Jawab (KTJ)">
                <option value="Etika dan Perilaku (EP)">
                <option value="Keamanan dan Ketertiban Sekolah (KKS)">
                <option value="Kendaraan (KD)">
                <option value="Pelanggaran Berat (PB)">
            </datalist>
        </div>

        <div class="mb-2">
            <label class="form-label">Kode Pelanggaran</label>
            <input type="text" name="kode" id="f_kode" class="form-control" placeholder="Misal: KH01, SPK01, PB01" required>
        </div>
        
        <div class="mb-2">
            <label class="form-label">Nama Pelanggaran</label>
            <input type="text" name="nama_pelanggaran" id="f_nama" class="form-control" placeholder="Deskripsi jenis pelanggaran..." required>
        </div>
        
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label">Tingkat</label>
                <select name="tingkat" id="f_tingkat" class="form-select">
                    <option value="Ringan">Ringan</option>
                    <option value="Sedang">Sedang</option>
                    <option value="Berat">Berat</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Poin Pengurang</label>
                <div class="input-group">
                    <span class="input-group-text text-danger">-</span>
                    <input type="number" name="poin" id="f_poin" class="form-control" min="1" placeholder="Poin" required>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-dark">Simpan Pelanggaran</button>
      </div>
    </form>
  </div>
</div>

<script>
function modeTambah(){
    document.getElementById('judulModal').innerText='Tambah Kategori Pelanggaran';
    document.getElementById('f_aksi').value='tambah';
    document.getElementById('f_id').value='';
    document.getElementById('f_kode').value='';
    document.getElementById('f_kategori').value='Kehadiran (KH)';
    document.getElementById('f_nama').value='';
    document.getElementById('f_poin').value='';
    document.getElementById('f_tingkat').value='Ringan';
}

function modeEdit(d){
    document.getElementById('judulModal').innerText='Edit Kategori Pelanggaran';
    document.getElementById('f_aksi').value='edit';
    document.getElementById('f_id').value=d.id;
    document.getElementById('f_kode').value=d.kode;
    document.getElementById('f_kategori').value=d.kategori || '';
    document.getElementById('f_nama').value=d.nama_pelanggaran;
    document.getElementById('f_poin').value=d.poin;
    document.getElementById('f_tingkat').value=d.tingkat;
}

function filterTabel(){
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const katVal = document.getElementById('filterKategori').value;
    const tingVal = document.getElementById('filterTingkat').value;
    const rows = document.querySelectorAll('.row-pelanggaran');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const kat = row.getAttribute('data-kategori');
        const ting = row.getAttribute('data-tingkat');

        const matchSearch = text.includes(searchVal);
        const matchKat = !katVal || kat === katVal;
        const matchTing = !tingVal || ting === tingVal;

        if (matchSearch && matchKat && matchTing) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

document.getElementById('searchInput').addEventListener('keyup', filterTabel);
</script>

<?php include 'includes/footer.php'; ?>
