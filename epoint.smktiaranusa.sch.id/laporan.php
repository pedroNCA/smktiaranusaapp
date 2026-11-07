<?php
require 'config.php';
require 'includes/auth.php';
if (isOsis()) {
    redirect('index.php', 'Akses ditolak: Rekapitulasi laporan hanya dapat diakses oleh Dewan Guru dan Pimpinan.', 'danger');
}
$judul_halaman = 'Laporan Rekap Poin';

$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY jurusan, nama_kelas");
$filter_jurusan = clean($koneksi, $_GET['jurusan'] ?? '');
$filter_kelas = (int)($_GET['kelas'] ?? 0);
$urut = $_GET['urut'] ?? 'terendah';

$sql = "SELECT s.id, s.nis, s.nisn, s.nama, k.nama_kelas, k.jurusan,
        (SELECT COALESCE(SUM(kp.poin),0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
        (SELECT COUNT(*) FROM riwayat_pelanggaran rp WHERE rp.siswa_id=s.id) AS jumlah_pelanggaran,
        (SELECT COALESCE(SUM(kpr.poin),0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi,
        (SELECT COUNT(*) FROM riwayat_prestasi rpr WHERE rpr.siswa_id=s.id) AS jumlah_prestasi
    FROM siswa s JOIN kelas k ON s.kelas_id=k.id
    WHERE s.status='aktif'";
if ($filter_jurusan !== '') $sql .= " AND k.jurusan = '$filter_jurusan'";
if ($filter_kelas) $sql .= " AND s.kelas_id=$filter_kelas";
$res = mysqli_query($koneksi, $sql);

$daftar = [];
while ($row = mysqli_fetch_assoc($res)) {
    $row['skor'] = SKOR_AWAL - $row['total_pelanggaran'] + $row['total_prestasi'];
    $daftar[] = $row;
}
usort($daftar, fn($a,$b) => $urut === 'tertinggi' ? $b['skor'] <=> $a['skor'] : $a['skor'] <=> $b['skor']);

// Export Data Laporan ke Excel (.xlsx) / CSV
if (isset($_GET['export'])) {
    require_once 'includes/export_helper.php';
    $fmt = strtolower($_GET['export']);
    $filename = 'laporan_rekap_epoint_' . ($filter_jurusan ? $filter_jurusan . '_' : '') . date('Ymd_His');
    $sheetTitle = 'Rekapitulasi Poin';
    $headers = ['NO', 'NISN / NIS', 'NAMA LENGKAP', 'JURUSAN', 'KELAS', 'JML PELANGGARAN', 'POIN PELANGGARAN', 'JML PRESTASI', 'POIN PRESTASI', 'SKOR AKHIR', 'PREDIKAT'];

    $rows = [];
    $no = 1;
    foreach ($daftar as $d) {
        [$label] = hitungPredikat($d['skor']);
        $rows[] = [
            $no++,
            $d['nisn'] ?: $d['nis'],
            $d['nama'],
            $d['jurusan'],
            $d['nama_kelas'],
            (int)$d['jumlah_pelanggaran'],
            (int)$d['total_pelanggaran'],
            (int)$d['jumlah_prestasi'],
            (int)$d['total_prestasi'],
            $d['skor'],
            $label
        ];
    }

    if ($fmt === 'csv') {
        downloadCsv($filename, $headers, $rows);
    } else {
        downloadXlsx($filename, $sheetTitle, $headers, $rows);
    }
}

include 'includes/header.php';
?>
<div class="card p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <select name="jurusan" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                <option value="">Semua Jurusan</option>
                <option value="MPLB" <?= $filter_jurusan==='MPLB'?'selected':'' ?>>MPLB</option>
                <option value="TJKT" <?= $filter_jurusan==='TJKT'?'selected':'' ?>>TJKT</option>
            </select>

            <select name="kelas" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                <option value="0">Semua Kelas</option>
                <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                    <?php if ($filter_jurusan === '' || $k['jurusan'] === $filter_jurusan): ?>
                        <option value="<?= $k['id'] ?>" <?= $filter_kelas==$k['id']?'selected':'' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endif; ?>
                <?php endwhile; ?>
            </select>

            <select name="urut" class="form-select form-select-sm" style="width:170px" onchange="this.form.submit()">
                <option value="terendah" <?= $urut=='terendah'?'selected':'' ?>>Skor Terendah Dulu</option>
                <option value="tertinggi" <?= $urut=='tertinggi'?'selected':'' ?>>Skor Tertinggi Dulu</option>
            </select>
        </form>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export Rekap
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="?jurusan=<?= urlencode($filter_jurusan) ?>&kelas=<?= $filter_kelas ?>&urut=<?= $urut ?>&export=xlsx">
                            <i class="bi bi-file-earmark-spreadsheet text-success fs-6"></i> Export Excel (.xlsx)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="?jurusan=<?= urlencode($filter_jurusan) ?>&kelas=<?= $filter_kelas ?>&urut=<?= $urut ?>&export=csv">
                            <i class="bi bi-file-earmark-text text-primary fs-6"></i> Export CSV (.csv)
                        </a>
                    </li>
                </ul>
            </div>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr>
            <th>NISN</th>
            <th>Nama</th>
            <th>Jurusan</th>
            <th>Kelas</th>
            <th class="text-center">Jml Pelanggaran</th>
            <th class="text-center">Poin Pelanggaran</th>
            <th class="text-center">Jml Prestasi</th>
            <th class="text-center">Poin Prestasi</th>
            <th class="text-center">Skor Akhir</th>
            <th>Predikat</th>
        </tr></thead>
        <tbody>
        <?php if (empty($daftar)): ?>
            <tr><td colspan="10" class="text-center text-muted py-3">Tidak ada data</td></tr>
        <?php endif; ?>
        <?php foreach ($daftar as $d): [$label, $warna] = hitungPredikat($d['skor']); ?>
            <tr>
                <td><strong class="font-monospace text-dark"><?= htmlspecialchars($d['nisn'] ?: $d['nis']) ?></strong></td>
                <td><?= htmlspecialchars($d['nama']) ?></td>
                <td><?= badgeJurusan($d['jurusan']) ?></td>
                <td><?= htmlspecialchars($d['nama_kelas']) ?></td>
                <td class="text-center"><?= $d['jumlah_pelanggaran'] ?></td>
                <td class="text-center text-danger fw-semibold">-<?= $d['total_pelanggaran'] ?></td>
                <td class="text-center"><?= $d['jumlah_prestasi'] ?></td>
                <td class="text-center text-success fw-semibold">+<?= $d['total_prestasi'] ?></td>
                <td class="text-center fw-bold fs-6"><?= $d['skor'] ?></td>
                <td><span class="badge bg-<?= $warna ?>"><?= $label ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<style>
@media print {
    #sidebar, .topbar, .btn, form, .btn-group { display:none !important; }
    #content { margin-left:0 !important; padding:0 !important; }
    .card { border:none !important; box-shadow:none !important; }
}
</style>
<?php include 'includes/footer.php'; ?>
