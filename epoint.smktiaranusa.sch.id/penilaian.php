<?php
require 'config.php';
require 'includes/auth.php';

$judul_halaman = 'Penilaian Karakter';
$user_id = (int)$_SESSION['user_id'];
$role_user = $_SESSION['role'] ?? '';

// --- 1. PROSES FORM INPUT PENILAIAN (APRESIASI / PELANGGARAN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $tanggal = clean($koneksi, $_POST['tanggal'] ?? date('Y-m-d'));
    $keterangan = clean($koneksi, $_POST['keterangan'] ?? '');

    if (!$siswa_id || !$kategori_id) {
        redirect("penilaian.php?siswa_id=$siswa_id", 'Data penilaian belum lengkap.', 'danger');
    }

    if ($aksi === 'simpan_apresiasi') {
        if (isOsis() || isKepalaSekolah()) {
            redirect("penilaian.php?siswa_id=$siswa_id", 'Akses ditolak: Hanya Guru/Admin yang dapat mencatat apresiasi.', 'danger');
        }
        mysqli_query($koneksi, "INSERT INTO riwayat_prestasi (siswa_id, kategori_id, tanggal, keterangan, petugas_id) 
            VALUES ($siswa_id, $kategori_id, '$tanggal', '$keterangan', $user_id)");
        redirect("penilaian.php?siswa_id=$siswa_id", 'Apresiasi berhasil ditambahkan untuk siswa ini.');
    }

    if ($aksi === 'simpan_pelanggaran') {
        if (isKepalaSekolah()) {
            redirect("penilaian.php?siswa_id=$siswa_id", 'Akses ditolak: Kepala Sekolah hanya memiliki hak pemantauan.', 'danger');
        }
        mysqli_query($koneksi, "INSERT INTO riwayat_pelanggaran (siswa_id, kategori_id, tanggal, keterangan, petugas_id) 
            VALUES ($siswa_id, $kategori_id, '$tanggal', '$keterangan', $user_id)");
        redirect("penilaian.php?siswa_id=$siswa_id", 'Catatan pelanggaran berhasil disimpan.');
    }
}

// --- 2. AMBIL MASTER KELAS UNTUK DROPDOWN FILTER ---
$kelas_query = mysqli_query($koneksi, "
    SELECT k.*, COUNT(s.id) AS jumlah_siswa 
    FROM kelas k 
    LEFT JOIN siswa s ON k.id=s.kelas_id AND s.status='aktif' 
    GROUP BY k.id 
    ORDER BY k.jurusan, k.nama_kelas
");
$daftar_kelas = [];
$kelas_map = [];
$mplb_kelas = [];
$tjkt_kelas = [];
$total_siswa_sekolah = 0;

while ($k = mysqli_fetch_assoc($kelas_query)) {
    $daftar_kelas[] = $k;
    $kelas_map[$k['id']] = $k;
    $total_siswa_sekolah += (int)$k['jumlah_siswa'];
    if ($k['jurusan'] === 'MPLB') {
        $mplb_kelas[] = $k;
    } else {
        $tjkt_kelas[] = $k;
    }
}

// Cek apakah ada siswa yang sedang dipilih
$siswa_terpilih_id = (int)($_GET['siswa_id'] ?? 0);
$siswa_terpilih = null;
$riwayat_terbaru = [];

if ($siswa_terpilih_id > 0) {
    // Ambil data detail siswa terpilih beserta total poin
    $qSiswa = mysqli_query($koneksi, "
        SELECT s.*, k.nama_kelas, k.jurusan, k.wali_kelas,
            (SELECT COALESCE(SUM(kp.poin), 0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
            (SELECT COALESCE(SUM(kpr.poin), 0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi
        FROM siswa s
        JOIN kelas k ON s.kelas_id=k.id
        WHERE s.id=$siswa_terpilih_id AND s.status='aktif'
        LIMIT 1
    ");
    $siswa_terpilih = mysqli_fetch_assoc($qSiswa);

    if ($siswa_terpilih) {
        // Ambil 5 riwayat terbaru (gabungan pelanggaran & prestasi)
        $qRiwayat = mysqli_query($koneksi, "
            (SELECT rp.id, 'pelanggaran' AS jenis, kp.nama_pelanggaran AS nama, kp.poin, rp.tanggal, rp.keterangan, rp.created_at
             FROM riwayat_pelanggaran rp
             JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id
             WHERE rp.siswa_id=$siswa_terpilih_id)
            UNION ALL
            (SELECT rpr.id, 'prestasi' AS jenis, kpr.nama_prestasi AS nama, kpr.poin, rpr.tanggal, rpr.keterangan, rpr.created_at
             FROM riwayat_prestasi rpr
             JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id
             WHERE rpr.siswa_id=$siswa_terpilih_id)
            ORDER BY tanggal DESC, created_at DESC
            LIMIT 5
        ");
        while ($r = mysqli_fetch_assoc($qRiwayat)) {
            $riwayat_terbaru[] = $r;
        }

        // Ambil master kategori prestasi (untuk form Apresiasi) terkelompok
        $kategori_prestasi_list = [];
        $qp = mysqli_query($koneksi, "SELECT * FROM kategori_prestasi ORDER BY FIELD(SUBSTRING(kode,1,2),'PR','PK'), poin DESC, nama_prestasi ASC");
        while ($p = mysqli_fetch_assoc($qp)) {
            $groupName = (strpos($p['kode'], 'PK') === 0) ? 'Penguatan Karakter & Kontribusi Sekolah' : 'Prestasi Kejuaraan & Vokasi';
            $kategori_prestasi_list[$groupName][] = $p;
        }

        // Ambil master kategori pelanggaran (untuk form Pelanggaran)
        $kategori_pelanggaran_list = [];
        $qpel = mysqli_query($koneksi, "SELECT * FROM kategori_pelanggaran ORDER BY kategori ASC, poin ASC");
        while ($pel = mysqli_fetch_assoc($qpel)) {
            $kategori_pelanggaran_list[$pel['kategori']][] = $pel;
        }
    }
}

// Jika belum ada siswa terpilih, muat daftar kartu murid
// Default kelas: jika wali kelas, otomatis arahkan ke kelas perwaliannya
$default_kelas_awal = (!empty($_SESSION['kelas_id']) && isset($kelas_map[$_SESSION['kelas_id']])) 
    ? (string)$_SESSION['kelas_id'] 
    : 'semua';
$filter_kelas = $_GET['kelas'] ?? $default_kelas_awal;
$sqlSiswa = "
    SELECT s.id, s.nama, s.nis, s.nisn, k.nama_kelas,
        (SELECT COALESCE(SUM(kp.poin), 0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
        (SELECT COALESCE(SUM(kpr.poin), 0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi
    FROM siswa s
    JOIN kelas k ON s.kelas_id=k.id
    WHERE s.status='aktif'
";
if ($filter_kelas !== 'semua' && (int)$filter_kelas > 0) {
    $sqlSiswa .= " AND s.kelas_id=" . (int)$filter_kelas;
}
$sqlSiswa .= " ORDER BY k.jurusan, k.nama_kelas, s.nama";
$qAllSiswa = mysqli_query($koneksi, $sqlSiswa);
$daftar_semua_siswa = [];
while ($row = mysqli_fetch_assoc($qAllSiswa)) {
    $daftar_semua_siswa[] = $row;
}
$total_tampil = count($daftar_semua_siswa);

include 'includes/header.php';
?>

<style>
/* CSS Desain Persis Screenshot */
.card-student-box {
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    background: #ffffff;
    padding: 24px 18px 20px 18px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    height: 100%;
    display: flex;
    flex-direction: column;
}
.card-student-box:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.08);
    border-color: #93c5fd;
}
.avatar-circle-soft {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: #e0edfd;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    font-weight: 800;
    margin: 0 auto 16px auto;
}
.student-name-bold {
    font-size: 0.96rem;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: -0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 5px;
}
.student-class-sub {
    font-size: 0.82rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.student-divider {
    border-top: 1px solid #f1f5f9;
    margin: 0 6px 14px 6px;
}
.score-row-minimal {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-weight: 800;
    font-size: 0.95rem;
}
.score-green {
    color: #059669;
}
.score-pipe {
    color: #cbd5e1;
    font-weight: 400;
}
.score-red {
    color: #dc2626;
}

/* Scroll Container untuk Grid Kartu Murid */
.students-scroll-container {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding-right: 6px;
}
.students-scroll-container::-webkit-scrollbar {
    width: 6px;
}
.students-scroll-container::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 6px;
}

/* Styling Layar 2: Penilaian Murid Terpilih */
.selected-student-banner {
    border: 1px solid #dbeafe;
    border-radius: 18px;
    background: #f0f7ff;
    padding: 20px 24px;
}
.badge-pill-apresiasi {
    border: 1.5px solid #86efac;
    background: #f0fdf4;
    border-radius: 12px;
    padding: 6px 16px;
    text-align: center;
    min-width: 100px;
}
.badge-pill-pelanggaran {
    border: 1.5px solid #fca5a5;
    background: #fef2f2;
    border-radius: 12px;
    padding: 6px 16px;
    text-align: center;
    min-width: 100px;
}
.type-action-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    padding: 22px 20px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    height: 100%;
}
.type-action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -4px rgba(0,0,0,0.06);
}
.type-action-card.card-apresiasi:hover {
    border-color: #86efac;
    background: #fcfdfc;
}
.type-action-card.card-pelanggaran:hover {
    border-color: #fca5a5;
    background: #fdfcfc;
}
.icon-box-round {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.icon-apresiasi {
    background: #dcfce7;
    color: #16a34a;
}
.icon-pelanggaran {
    background: #fee2e2;
    color: #dc2626;
}

/* Panel Riwayat Terbaru di Kanan */
.history-item-box {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #ffffff;
    padding: 14px 16px;
    margin-bottom: 10px;
    transition: all 0.15s ease;
}
.history-item-box:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.history-scroll-container {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
    padding-right: 4px;
}
.history-scroll-container::-webkit-scrollbar {
    width: 5px;
}
.history-scroll-container::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 5px;
}
</style>

<div class="container-fluid px-0">

<?php if ($siswa_terpilih): 
    $initialTerpilih = mb_strtoupper(mb_substr(trim($siswa_terpilih['nama']), 0, 1, 'UTF-8'), 'UTF-8');
?>
    <!-- ========================================================================= -->
    <!-- SCREENSHOT 2: LAYAR PENILAIAN KARAKTER (MURID TERPILIH) -->
    <!-- ========================================================================= -->
    <div class="row g-4">
        <!-- Kolom Kiri: Pilih Murid & Indikator Perilaku -->
        <div class="col-12 col-lg-7 col-xl-7">
            <!-- Header Judul -->
            <div class="mb-3">
                <h4 class="fw-bold mb-1 text-dark">Pilih Murid</h4>
                <p class="text-secondary small mb-0">
                    Pilih murid dari daftar untuk memberikan penilaian karakter, atau gunakan filter kelas dan pencarian di bawah
                </p>
            </div>

            <!-- Kartu Murid Terpilih -->
            <div class="selected-student-banner mb-4 shadow-xs">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle-soft m-0" style="width: 56px; height: 56px; font-size: 1.3rem; background:#2563eb; color:#ffffff;">
                            <?= htmlspecialchars($initialTerpilih) ?>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">
                                <?= htmlspecialchars($siswa_terpilih['nama']) ?>
                            </h5>
                            <div class="text-secondary small fw-semibold">
                                <?= htmlspecialchars($siswa_terpilih['nama_kelas']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Badges Skor Murid -->
                    <div class="d-flex gap-2">
                        <div class="badge-pill-apresiasi">
                            <div class="text-uppercase fw-bold text-success" style="font-size: 0.68rem; letter-spacing: 0.5px;">Apresiasi</div>
                            <div class="fw-bold text-success" style="font-size: 1.15rem; line-height: 1.2;">
                                +<?= (int)$siswa_terpilih['total_prestasi'] ?>
                            </div>
                        </div>
                        <div class="badge-pill-pelanggaran">
                            <div class="text-uppercase fw-bold text-danger" style="font-size: 0.68rem; letter-spacing: 0.5px;">Pelanggaran</div>
                            <div class="fw-bold text-danger" style="font-size: 1.15rem; line-height: 1.2;">
                                -<?= (int)$siswa_terpilih['total_pelanggaran'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Ganti Murid -->
                <div class="mt-3 pt-2 border-top border-primary-subtle">
                    <a href="penilaian.php?kelas=<?= $siswa_terpilih['kelas_id'] ?>" class="text-decoration-none fw-bold small text-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-counterclockwise fs-6"></i> Ganti Murid
                    </a>
                </div>
            </div>

            <!-- Bagian: Indikator Perilaku (PILIH TIPE PENILAIAN) -->
            <div class="mb-2">
                <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">Indikator Perilaku</h5>
                <div class="text-uppercase fw-bold text-muted small mb-3" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <?= isKepalaSekolah() ? 'HAK AKSES PEMANTAUAN (VIEW ONLY)' : 'PILIH TIPE PENILAIAN' ?>
                </div>
            </div>

            <?php if (isKepalaSekolah()): ?>
                <!-- Tampilan Khusus Kepala Sekolah (Hanya Memantau / Eksekutif) -->
                <div class="p-3 rounded-3 border bg-light text-secondary mb-3 small d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary fs-4"></i>
                    <div>
                        <strong>Mode Monitoring Eksekutif:</strong> Sebagai Kepala Sekolah, Anda memiliki hak pemantauan penuh terhadap skor dan riwayat penilaian karakter siswa tanpa melakukan input transaksi harian.
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php if (!isOsis()): ?>
                    <!-- Kartu Tipe: Apresiasi (Hanya untuk Guru / Wali Kelas / Admin / Guru BK) -->
                    <div class="col-12 <?= isOsis() ? 'd-none' : 'col-sm-6' ?>">
                        <div class="type-action-card card-apresiasi" data-bs-toggle="modal" data-bs-target="#modalApresiasi">
                            <div class="d-flex align-items-start gap-3">
                                <div class="icon-box-round icon-apresiasi">
                                    <i class="bi bi-patch-check-fill fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">Apresiasi</h5>
                                    <p class="text-secondary small mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                                        Penghargaan untuk perilaku terpuji, prestasi, dan pembiasaan karakter positif.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Kartu Tipe: Pelanggaran (Bisa diakses Guru, Wali Kelas, Admin, & Petugas OSIS) -->
                    <div class="col-12 <?= isOsis() ? 'col-12' : 'col-sm-6' ?>">
                        <div class="type-action-card card-pelanggaran" data-bs-toggle="modal" data-bs-target="#modalPelanggaran">
                            <div class="d-flex align-items-start gap-3">
                                <div class="icon-box-round icon-pelanggaran">
                                    <i class="bi bi-exclamation-circle-fill fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">Pelanggaran</h5>
                                    <p class="text-secondary small mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                                        Pencatatan untuk tindakan indisipliner, pelanggaran aturan, atau kelalaian.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kolom Kanan: Riwayat Terbaru (5 Penilaian Terakhir) -->
        <div class="col-12 col-lg-5 col-xl-5">
            <div class="ps-lg-3">
                <div class="mb-3">
                    <h5 class="fw-bold text-dark mb-1" style="font-size: 1.15rem;">Riwayat Terbaru</h5>
                    <p class="text-secondary small mb-0">5 penilaian terakhir untuk murid terpilih</p>
                </div>

                <div class="history-scroll-container">
                    <?php if (empty($riwayat_terbaru)): ?>
                        <div class="p-4 rounded-4 text-center border bg-light text-muted">
                            <i class="bi bi-clock-history fs-3 d-block mb-2 text-secondary"></i>
                            <span class="small">Belum ada riwayat penilaian untuk murid ini.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($riwayat_terbaru as $r): 
                            $isPrestasi = ($r['jenis'] === 'prestasi');
                            $tglFmt = date('j/n/Y', strtotime($r['tanggal']));
                        ?>
                            <div class="history-item-box">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px; color: <?= $isPrestasi ? '#16a34a' : '#dc2626' ?>;">
                                        <?= $isPrestasi ? 'APRESIASI' : 'PELANGGARAN' ?>
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge fw-bold px-2 py-1" style="background: <?= $isPrestasi ? '#f0fdf4' : '#fef2f2' ?>; color: <?= $isPrestasi ? '#16a34a' : '#dc2626' ?>; border: 1px solid <?= $isPrestasi ? '#bbf7d0' : '#fecaca' ?>; font-size: 0.78rem;">
                                            <?= $isPrestasi ? '+' : '-' ?><?= (int)$r['poin'] ?>
                                        </span>
                                        <?php if (isGuruBK() || isAdmin()): ?>
                                            <a href="hapus_riwayat.php?id=<?= $r['id'] ?>&jenis=<?= $r['jenis'] ?>&siswa_id=<?= $siswa_terpilih_id ?>&redirect=<?= urlencode('penilaian.php?siswa_id='.$siswa_terpilih_id) ?>" 
                                               class="text-secondary text-hover-danger" 
                                               title="Hapus penilaian ini"
                                               onclick="return confirm('Hapus catatan riwayat ini?')">
                                                <i class="bi bi-trash" style="font-size: 0.88rem;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.88rem; line-height: 1.4;">
                                    <?= htmlspecialchars($r['nama']) ?>
                                </div>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    <i class="bi bi-calendar3 me-1"></i> <?= $tglFmt ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL INPUT APRESIASI -->
    <div class="modal fade" id="modalApresiasi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow" method="POST" action="penilaian.php">
                <input type="hidden" name="aksi" value="simpan_apresiasi">
                <input type="hidden" name="siswa_id" value="<?= $siswa_terpilih_id ?>">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-round icon-apresiasi" style="width:40px; height:40px; border-radius:12px;">
                            <i class="bi bi-patch-check-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Catat Apresiasi</h5>
                            <small class="text-muted"><?= htmlspecialchars($siswa_terpilih['nama']) ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Kategori Apresiasi / Karakter Positif <span class="text-danger">*</span></label>
                        <select name="kategori_id" class="form-select" required style="border-radius:10px;">
                            <option value="">-- Pilih Indikator Apresiasi --</option>
                            <?php foreach ($kategori_prestasi_list as $grpName => $items): ?>
                                <optgroup label="<?= htmlspecialchars($grpName) ?>">
                                    <?php foreach ($items as $kp): ?>
                                        <option value="<?= $kp['id'] ?>">
                                            [<?= $kp['kode'] ?>] <?= htmlspecialchars($kp['nama_prestasi']) ?> (+<?= $kp['poin'] ?> Poin)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required style="border-radius:10px;">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-secondary small">Catatan Tambahan (Opsional)</label>
                        <textarea name="keterangan" rows="2" class="form-control" placeholder="Contoh: Berperan aktif membantu teman kelas..." style="border-radius:10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4" style="border-radius:10px;">Simpan Apresiasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL INPUT PELANGGARAN -->
    <div class="modal fade" id="modalPelanggaran" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow" method="POST" action="penilaian.php">
                <input type="hidden" name="aksi" value="simpan_pelanggaran">
                <input type="hidden" name="siswa_id" value="<?= $siswa_terpilih_id ?>">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-round icon-pelanggaran" style="width:40px; height:40px; border-radius:12px;">
                            <i class="bi bi-exclamation-circle-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Catat Pelanggaran</h5>
                            <small class="text-muted"><?= htmlspecialchars($siswa_terpilih['nama']) ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Jenis Pelanggaran <span class="text-danger">*</span></label>
                        <select name="kategori_id" class="form-select" required style="border-radius:10px;">
                            <option value="">-- Pilih Jenis Pelanggaran --</option>
                            <?php foreach ($kategori_pelanggaran_list as $katGroup => $items): ?>
                                <optgroup label="<?= htmlspecialchars($katGroup) ?>">
                                    <?php foreach ($items as $kpel): ?>
                                        <option value="<?= $kpel['id'] ?>">
                                            <?= htmlspecialchars($kpel['nama_pelanggaran']) ?> (-<?= $kpel['poin'] ?> Poin)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required style="border-radius:10px;">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-secondary small">Keterangan / Kronologi Singkat (Opsional)</label>
                        <textarea name="keterangan" rows="2" class="form-control" placeholder="Contoh: Terlambat 15 menit apel pagi..." style="border-radius:10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4" style="border-radius:10px;">Simpan Pelanggaran</button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================================================= -->
    <!-- SCREENSHOT 1: LAYAR PEMILIHAN MURID (DROPDOWN + CARI + GRID KARTU) -->
    <!-- ========================================================================= -->
    
    <!-- BARIS KONTROL ATAS: DROPDOWN KELAS & SEARCH INPUT (PERSIS SCREENSHOT 1 & 2) -->
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mb-3">
        <!-- Dropdown Kelas (Rounded Blue Outline) -->
        <div style="min-width: 160px;">
            <select id="selectKelasPenilaian" class="form-select fw-semibold" 
                    style="border-radius: 14px; height: 44px; font-size: 0.92rem; border: 1.5px solid #3b82f6; color: #1e293b; background-color: #ffffff;"
                    onchange="filterKelasPenilaian(this.value)">
                <option value="semua" <?= $filter_kelas === 'semua' ? 'selected' : '' ?>>Semua Kelas</option>
                <optgroup label="Jurusan MPLB">
                    <?php foreach ($mplb_kelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($filter_kelas !== 'semua' && (int)$filter_kelas === (int)$k['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Jurusan TJKT">
                    <?php foreach ($tjkt_kelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($filter_kelas !== 'semua' && (int)$filter_kelas === (int)$k['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>

        <!-- Input Cari Nama Murid dengan counter (contoh 20/534) -->
        <div class="position-relative flex-grow-1">
            <i class="bi bi-search position-absolute text-muted" style="top: 12px; left: 14px; font-size: 0.95rem;"></i>
            <input type="text" id="searchInputPenilaian" class="form-control" 
                   placeholder="Cari nama murid..." 
                   style="border-radius: 14px; height: 44px; padding-left: 40px; padding-right: 80px; font-size: 0.92rem; border: 1.5px solid #cbd5e1;"
                   oninput="cariMuridInstan(this.value)">
            <span class="position-absolute text-muted small fw-semibold" id="counterMuridText" 
                  style="top: 13px; right: 16px; font-size: 0.82rem; pointer-events: none;">
                <?= $total_tampil ?>/<?= $total_siswa_sekolah ?>
            </span>
        </div>
    </div>

    <!-- GRID KARTU MURID 3 KOLOM DENGAN SCROLLBAR (PERSIS SCREENSHOT 1) -->
    <div class="students-scroll-container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" id="muridCardsGrid">
            <?php foreach ($daftar_semua_siswa as $s): 
                $initial = mb_strtoupper(mb_substr(trim($s['nama']), 0, 1, 'UTF-8'), 'UTF-8');
                $searchStr = strtolower($s['nama'] . ' ' . $s['nis'] . ' ' . ($s['nisn'] ?? ''));
                $skor = SKOR_AWAL - (int)$s['total_pelanggaran'] + (int)$s['total_prestasi'];
                [$label, $warna] = hitungPredikat($skor);
            ?>
                <div class="col card-murid-col" data-search="<?= htmlspecialchars($searchStr) ?>">
                    <div class="card-student-box" onclick="window.location.href='penilaian.php?siswa_id=<?= $s['id'] ?>'">
                        <!-- Avatar Inisial Lingkaran Lembut -->
                        <div class="avatar-circle-soft">
                            <?= htmlspecialchars($initial) ?>
                        </div>

                        <!-- Nama Siswa & Kelas -->
                        <div class="px-1">
                            <div class="student-name-bold" title="<?= htmlspecialchars($s['nama']) ?>">
                                <?= htmlspecialchars($s['nama']) ?>
                            </div>
                            <div class="student-class-sub">
                                <?= htmlspecialchars($s['nama_kelas']) ?>
                            </div>
                            <!-- Nilai Skor & Predikat -->
                            <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                                <span class="badge bg-<?= $warna ?> text-white fw-bold px-2 py-1 shadow-xs" style="font-size: 0.82rem; border-radius: 8px;">
                                    Skor: <?= $skor ?>
                                </span>
                                <span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?> border border-<?= $warna ?>-subtle fw-semibold px-2 py-1" style="font-size: 0.78rem; border-radius: 8px;">
                                    <?= $label ?>
                                </span>
                            </div>
                        </div>

                        <!-- Garis Pemisah Halus -->
                        <div class="student-divider"></div>

                        <!-- Indikator Skor: +0 | -0 (Persis Screenshot) -->
                        <div class="score-row-minimal">
                            <span class="score-green">+<?= (int)$s['total_prestasi'] ?></span>
                            <span class="score-pipe">|</span>
                            <span class="score-red">-<?= (int)$s['total_pelanggaran'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php endif; ?>

</div>

<script>
function filterKelasPenilaian(kelasId) {
    window.location.href = 'penilaian.php?kelas=' + encodeURIComponent(kelasId);
}

function cariMuridInstan(query) {
    const q = query.toLowerCase().trim();
    const items = document.querySelectorAll('.card-murid-col');
    let count = 0;

    items.forEach(el => {
        const key = el.getAttribute('data-search') || '';
        if (q === '' || key.includes(q)) {
            el.style.display = '';
            count++;
        } else {
            el.style.display = 'none';
        }
    });

    const counter = document.getElementById('counterMuridText');
    if (counter) {
        counter.innerText = count + '/<?= $total_siswa_sekolah ?>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
