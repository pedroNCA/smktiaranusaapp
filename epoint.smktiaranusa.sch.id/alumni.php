<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Petugas OSIS tidak memiliki akses ke Arsip Alumni
if (isOsis()) {
    redirect('siswa.php', 'Akses ditolak: Petugas OSIS hanya berwenang mengakses data siswa aktif.', 'danger');
}

$judul_halaman = 'Arsip Alumni';

// Filter parameter
$filter_tahun = clean($koneksi, $_GET['tahun'] ?? '');
$filter_jurusan = clean($koneksi, $_GET['jurusan'] ?? '');
$cari = clean($koneksi, $_GET['cari'] ?? '');

// Ambil seluruh daftar tahun kelulusan yang ada di database
$qTahunList = mysqli_query($koneksi, "
    SELECT DISTINCT tahun_lulus 
    FROM siswa 
    WHERE status = 'lulus' AND tahun_lulus IS NOT NULL AND tahun_lulus != ''
    ORDER BY tahun_lulus DESC
");
$daftar_tahun_lulus = [];
while ($t = mysqli_fetch_assoc($qTahunList)) {
    $daftar_tahun_lulus[] = $t['tahun_lulus'];
}

// Query Alumni
$sql = "
    SELECT s.*, k.nama_kelas, k.jurusan,
        (SELECT COALESCE(SUM(kp.poin),0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
        (SELECT COALESCE(SUM(kpr.poin),0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi
    FROM siswa s 
    LEFT JOIN kelas k ON s.kelas_id=k.id 
    WHERE s.status = 'lulus'
";

if ($filter_tahun !== '') {
    $sql .= " AND s.tahun_lulus = '$filter_tahun'";
}
if ($filter_jurusan !== '') {
    $sql .= " AND k.jurusan = '$filter_jurusan'";
}
if ($cari !== '') {
    $sql .= " AND (s.nama LIKE '%$cari%' OR s.nis LIKE '%$cari%' OR s.nisn LIKE '%$cari%')";
}
$sql .= " ORDER BY s.tahun_lulus DESC, k.jurusan, s.nama ASC";

$data_alumni_query = mysqli_query($koneksi, $sql);
$list_alumni = [];
while ($r = mysqli_fetch_assoc($data_alumni_query)) {
    $list_alumni[] = $r;
}
$total_alumni = count($list_alumni);

// Ekspor ke Excel (.xlsx) / CSV jika diminta
if (isset($_GET['export']) && !isOsis()) {
    require_once 'includes/export_helper.php';
    $fmt = strtolower($_GET['export']);
    $tahun_slug = $filter_tahun ? str_replace('/', '-', $filter_tahun) : 'Semua_Tahun';
    $filename = 'data_alumni_smks_tiaranusa_' . $tahun_slug . '_' . date('Ymd_His');
    $sheetTitle = 'Data Alumni ' . ($filter_tahun ?: 'Semua');
    $headers = [
        'NO', 'NISN', 'NIS', 'NAMA LENGKAP SISWA', 'JENIS KELAMIN', 
        'KELAS TERAKHIR', 'JURUSAN', 'TAHUN KELULUSAN', 'TANGGAL KELULUSAN', 
        'TOTAL PELANGGARAN', 'TOTAL PRESTASI', 'SKOR AKHIR', 'PREDIKAT SIKAP'
    ];

    $rows = [];
    $no = 1;
    foreach ($list_alumni as $al) {
        $skor = SKOR_AWAL - (int)$al['total_pelanggaran'] + (int)$al['total_prestasi'];
        [$label] = hitungPredikat($skor);
        $rows[] = [
            $no++,
            $al['nisn'] ?: '-',
            $al['nis'] ?: '-',
            $al['nama'],
            $al['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
            $al['nama_kelas'] ?: '-',
            $al['jurusan'] ?: '-',
            $al['tahun_lulus'] ?: '-',
            $al['tanggal_lulus'] ?: '-',
            $al['total_pelanggaran'],
            $al['total_prestasi'],
            $skor,
            $label
        ];
    }

    if ($fmt === 'csv') {
        downloadCsv($filename, $headers, $rows);
    } else {
        downloadXlsx($filename, $sheetTitle, $headers, $rows);
    }
    exit;
}

include 'includes/header.php';
?>

<!-- Header Banner Halaman -->
<div class="card p-4 mb-4 text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); border-radius: 18px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1 shadow-xs">
                <i class="bi bi-mortarboard-fill me-1"></i> Pangkalan Data Purna Didik
            </div>
            <h4 class="fw-bold mb-1 text-white">Arsip Alumni SMKS Tiara Nusa</h4>
            <p class="text-white-50 mb-0 small">
                Data resmi siswa yang telah dinyatakan lulus, lengkap dengan rekam jejak kedisiplinan dan capaian prestasi selama bersekolah.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (isGuruBK()): ?>
            <a href="kelulusan_kenaikan.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="bi bi-arrow-repeat text-warning"></i>
                <span>Kelola Kenaikan &amp; Kelulusan</span>
            </a>
            <?php endif; ?>
            <a href="siswa.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="bi bi-people-fill text-info"></i>
                <span>Kembali ke Siswa Aktif</span>
            </a>
        </div>
    </div>
</div>

<!-- Card Filter & Pencarian -->
<div class="card p-3 p-md-4 border-0 shadow-sm mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <!-- Filter Tahun Kelulusan -->
        <div class="col-sm-6 col-md-3">
            <label class="form-label small fw-bold text-muted mb-1">Tahun Kelulusan:</label>
            <select name="tahun" class="form-select form-select-sm" style="border-radius:10px;" onchange="this.form.submit()">
                <option value="">Semua Angkatan / Tahun</option>
                <?php foreach ($daftar_tahun_lulus as $th): ?>
                    <option value="<?= htmlspecialchars($th) ?>" <?= $filter_tahun===$th ? 'selected' : '' ?>>
                        Angkatan <?= htmlspecialchars($th) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter Jurusan -->
        <div class="col-sm-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Jurusan:</label>
            <select name="jurusan" class="form-select form-select-sm" style="border-radius:10px;" onchange="this.form.submit()">
                <option value="">Semua Jurusan</option>
                <option value="MPLB" <?= $filter_jurusan==='MPLB' ? 'selected' : '' ?>>MPLB</option>
                <option value="TJKT" <?= $filter_jurusan==='TJKT' ? 'selected' : '' ?>>TJKT</option>
            </select>
        </div>

        <!-- Pencarian -->
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted mb-1">Cari Nama / NISN / NIS:</label>
            <div class="input-group input-group-sm">
                <input type="text" name="cari" class="form-control" placeholder="Ketik nama atau NISN..." value="<?= htmlspecialchars($cari) ?>" style="border-radius:10px 0 0 10px;">
                <button type="submit" class="btn btn-primary" style="border-radius:0 10px 10px 0;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        <!-- Tombol Aksi & Ekspor -->
        <div class="col-md-3 text-md-end pt-md-4">
            <div class="d-flex justify-content-md-end gap-2">
                <?php if ($filter_tahun || $filter_jurusan || $cari): ?>
                    <a href="alumni.php" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;" title="Reset Filter">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                <?php endif; ?>
                <?php if ($total_alumni > 0 && !isOsis()): ?>
                    <a href="alumni.php?export=xlsx<?= $filter_tahun ? '&tahun='.urlencode($filter_tahun) : '' ?><?= $filter_jurusan ? '&jurusan='.urlencode($filter_jurusan) : '' ?><?= $cari ? '&cari='.urlencode($cari) : '' ?>" 
                       class="btn btn-success btn-sm fw-bold shadow-sm d-flex align-items-center gap-1" style="border-radius:10px;">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        <span>Ekspor Excel</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Tabel Daftar Alumni -->
<div class="card p-3 p-md-4 border-0 shadow-sm mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                Daftar Purna Didik / Alumni 
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"><?= $total_alumni ?> Siswa</span>
            </h6>
        </div>
        <?php if ($filter_tahun): ?>
            <span class="badge bg-dark-subtle text-dark">Filter Aktif: Angkatan <?= htmlspecialchars($filter_tahun) ?></span>
        <?php endif; ?>
    </div>

    <?php if ($total_alumni === 0): ?>
        <div class="text-center py-5">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; background: #f1f5f9; color: #64748b;">
                <i class="bi bi-mortarboard fs-1"></i>
            </div>
            <h6 class="fw-bold text-dark">Belum Ada Data Alumni yang Tersimpan</h6>
            <p class="text-muted small mx-auto" style="max-width: 450px;">
                <?php if ($filter_tahun || $filter_jurusan || $cari): ?>
                    Tidak ditemukan data alumni yang sesuai dengan filter pencarian Anda.
                <?php else: ?>
                    Data alumni akan otomatis terisi saat Anda memproses kelulusan siswa Kelas XII melalui menu <strong>Kenaikan &amp; Kelulusan</strong>.
                <?php endif; ?>
            </p>
            <?php if (isGuruBK() && !$filter_tahun && !$filter_jurusan && !$cari): ?>
                <a href="kelulusan_kenaikan.php" class="btn btn-primary btn-sm px-3 py-2 mt-2" style="border-radius:10px;">
                    <i class="bi bi-arrow-repeat me-1"></i> Buka Menu Kelulusan Kelas XII
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="40" class="text-center">No</th>
                        <th>Identitas Alumni</th>
                        <th>Jurusan &amp; Rombel Terakhir</th>
                        <th>Tahun Lulus</th>
                        <th class="text-center">Pelanggaran</th>
                        <th class="text-center">Prestasi</th>
                        <th class="text-center">Skor Akhir</th>
                        <th class="text-center">Predikat</th>
                        <th class="text-center" width="130">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($list_alumni as $al): 
                        $skor = SKOR_AWAL - (int)$al['total_pelanggaran'] + (int)$al['total_prestasi'];
                        [$predikat_label, $predikat_class] = hitungPredikat($skor);
                    ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $no++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs flex-shrink-0" 
                                     style="width:36px; height:36px; background: <?= $al['jenis_kelamin']==='L' ? '#2563eb' : '#db2777' ?>; font-size:0.8rem;">
                                    <?= strtoupper(substr($al['nama'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size:0.92rem;"><?= htmlspecialchars($al['nama']) ?></div>
                                    <div class="small text-muted font-monospace" style="font-size:0.75rem;">
                                        NISN: <?= $al['nisn'] ?: '-' ?> &bull; NIS: <?= $al['nis'] ?: '-' ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($al['nama_kelas'] ?: '-') ?></div>
                            <div class="mt-1"><?= badgeJurusan($al['jurusan']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                <?= htmlspecialchars($al['tahun_lulus'] ?: 'Lulus') ?>
                            </span>
                            <?php if ($al['tanggal_lulus']): ?>
                                <div class="text-muted small mt-1" style="font-size:0.7rem;">
                                    <?= date('d/m/Y', strtotime($al['tanggal_lulus'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($al['total_pelanggaran'] > 0): ?>
                                <span class="badge bg-danger-subtle text-danger fw-bold">-<?= $al['total_pelanggaran'] ?></span>
                            <?php else: ?>
                                <span class="text-muted small">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($al['total_prestasi'] > 0): ?>
                                <span class="badge bg-success-subtle text-success fw-bold">+<?= $al['total_prestasi'] ?></span>
                            <?php else: ?>
                                <span class="text-muted small">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="fs-6 fw-bold <?= $skor < 75 ? 'text-danger' : ($skor >= 90 ? 'text-success' : 'text-primary') ?>">
                                <?= $skor ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $predikat_class ?>"><?= $predikat_label ?></span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="riwayat_siswa.php?id=<?= $al['id'] ?>" class="btn btn-outline-primary" title="Lihat Rekam Jejak & Cetak Rapor">
                                    <i class="bi bi-file-earmark-person"></i> Rapor
                                </a>
                                <?php if (isGuruBK()): ?>
                                    <button type="button" class="btn btn-outline-secondary" title="Batalkan Kelulusan (Kembalikan ke Aktif)" onclick="confirmBatalkan(<?= $al['id'] ?>, '<?= addslashes(htmlspecialchars($al['nama'])) ?>')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal / Form Hidden Batalkan Kelulusan -->
<form method="POST" action="kelulusan_kenaikan.php" id="formBatalkan" style="display:none;">
    <input type="hidden" name="aksi" value="batalkan_kelulusan">
    <input type="hidden" name="siswa_id" id="inputBatalSiswaId" value="">
</form>

<script>
function confirmBatalkan(id, nama) {
    if (confirm('BATALKAN KELULUSAN:\n\nApakah Anda yakin ingin membatalkan status kelulusan untuk "' + nama + '" dan mengembalikannya menjadi Siswa Aktif?')) {
        document.getElementById('inputBatalSiswaId').value = id;
        document.getElementById('formBatalkan').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
