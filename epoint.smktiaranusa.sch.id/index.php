<?php
require_once 'config.php';
require_once 'includes/auth.php';
$judul_halaman = 'Dashboard';

// Statistik ringkas
$total_siswa = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM siswa WHERE status='aktif'"))['n'];
$total_pelanggaran_bulan = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM riwayat_pelanggaran WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['n'];
$total_prestasi_bulan = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM riwayat_prestasi WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['n'];
$total_kelas = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM kelas"))['n'];

// Statistik per Jurusan (MPLB & TJKT)
$total_mplb = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' AND k.jurusan='MPLB'"))['n'];
$total_tjkt = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) n FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' AND k.jurusan='TJKT'"))['n'];

// Hitung statistik persentase pelanggaran: Harian, Mingguan, Bulanan, Tahunan
$total_pelanggaran_all = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM riwayat_pelanggaran"))['c'];

if (!function_exists('hitungStatPeriode')) {
    function hitungStatPeriode($koneksi, $where_curr, $where_prev, $total_siswa, $total_all) {
        $q = mysqli_query($koneksi, "
            SELECT 
                COUNT(*) AS kasus,
                COUNT(DISTINCT rp.siswa_id) AS siswa_terlibat,
                COALESCE(SUM(kp.poin), 0) AS total_poin,
                SUM(CASE WHEN kp.tingkat = 'Ringan' THEN 1 ELSE 0 END) AS jml_ringan,
                SUM(CASE WHEN kp.tingkat = 'Sedang' THEN 1 ELSE 0 END) AS jml_sedang,
                SUM(CASE WHEN kp.tingkat = 'Berat' THEN 1 ELSE 0 END) AS jml_berat,
                SUM(CASE WHEN k.jurusan = 'MPLB' THEN 1 ELSE 0 END) AS jml_mplb,
                SUM(CASE WHEN k.jurusan = 'TJKT' THEN 1 ELSE 0 END) AS jml_tjkt
            FROM riwayat_pelanggaran rp
            JOIN kategori_pelanggaran kp ON rp.kategori_id = kp.id
            JOIN siswa s ON rp.siswa_id = s.id
            JOIN kelas k ON s.kelas_id = k.id
            WHERE $where_curr
        ");
        $curr = mysqli_fetch_assoc($q);

        $q_prev = mysqli_query($koneksi, "SELECT COUNT(*) AS kasus FROM riwayat_pelanggaran rp WHERE $where_prev");
        $prev = mysqli_fetch_assoc($q_prev);

        $kasus = (int)($curr['kasus'] ?? 0);
        $siswa = (int)($curr['siswa_terlibat'] ?? 0);
        $poin = (int)($curr['total_poin'] ?? 0);
        $prev_kasus = (int)($prev['kasus'] ?? 0);

        // Persentase siswa melanggar terhadap total siswa aktif
        $pct_siswa = $total_siswa > 0 ? round(($siswa / $total_siswa) * 100, 1) : 0;
        
        // Persentase porsi terhadap seluruh pelanggaran
        $pct_share = $total_all > 0 ? round(($kasus / $total_all) * 100, 1) : 0;

        // Tren perubahan (%) vs periode sebelumnya
        if ($prev_kasus == 0) {
            $tren_pct = $kasus > 0 ? 100 : 0;
            $tren_arah = $kasus > 0 ? 'up' : 'neutral';
        } else {
            $diff = $kasus - $prev_kasus;
            $tren_pct = round(abs($diff) / $prev_kasus * 100, 1);
            $tren_arah = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral');
        }

        // Persentase per tingkat
        $jml_ringan = (int)($curr['jml_ringan'] ?? 0);
        $jml_sedang = (int)($curr['jml_sedang'] ?? 0);
        $jml_berat = (int)($curr['jml_berat'] ?? 0);
        $pct_ringan = $kasus > 0 ? round(($jml_ringan / $kasus) * 100, 1) : 0;
        $pct_sedang = $kasus > 0 ? round(($jml_sedang / $kasus) * 100, 1) : 0;
        $pct_berat = $kasus > 0 ? round(($jml_berat / $kasus) * 100, 1) : 0;

        // Persentase per jurusan
        $jml_mplb = (int)($curr['jml_mplb'] ?? 0);
        $jml_tjkt = (int)($curr['jml_tjkt'] ?? 0);
        $pct_mplb = $kasus > 0 ? round(($jml_mplb / $kasus) * 100, 1) : 0;
        $pct_tjkt = $kasus > 0 ? round(($jml_tjkt / $kasus) * 100, 1) : 0;

        return [
            'kasus' => $kasus,
            'siswa' => $siswa,
            'poin' => $poin,
            'pct_siswa' => $pct_siswa,
            'pct_share' => $pct_share,
            'prev_kasus' => $prev_kasus,
            'tren_pct' => $tren_pct,
            'tren_arah' => $tren_arah,
            'jml_ringan' => $jml_ringan,
            'pct_ringan' => $pct_ringan,
            'jml_sedang' => $jml_sedang,
            'pct_sedang' => $pct_sedang,
            'jml_berat' => $jml_berat,
            'pct_berat' => $pct_berat,
            'jml_mplb' => $jml_mplb,
            'pct_mplb' => $pct_mplb,
            'jml_tjkt' => $jml_tjkt,
            'pct_tjkt' => $pct_tjkt,
        ];
    }
}

$stat_hari = hitungStatPeriode($koneksi, "rp.tanggal = CURDATE()", "rp.tanggal = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", $total_siswa, $total_pelanggaran_all);
$stat_minggu = hitungStatPeriode($koneksi, "YEARWEEK(rp.tanggal, 1) = YEARWEEK(CURDATE(), 1)", "YEARWEEK(rp.tanggal, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)", $total_siswa, $total_pelanggaran_all);
$stat_bulan = hitungStatPeriode($koneksi, "MONTH(rp.tanggal) = MONTH(CURDATE()) AND YEAR(rp.tanggal) = YEAR(CURDATE())", "MONTH(rp.tanggal) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(rp.tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))", $total_siswa, $total_pelanggaran_all);
$stat_tahun = hitungStatPeriode($koneksi, "YEAR(rp.tanggal) = YEAR(CURDATE())", "YEAR(rp.tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))", $total_siswa, $total_pelanggaran_all);

// Query skor tiap siswa: skor_awal - total poin pelanggaran + total poin prestasi
$sql_skor = "SELECT s.id, s.nama, s.nis, k.nama_kelas, k.jurusan,
        (SELECT COALESCE(SUM(kp.poin),0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
        (SELECT COALESCE(SUM(kpr.poin),0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi
    FROM siswa s JOIN kelas k ON s.kelas_id=k.id
    WHERE s.status='aktif'";
$res = mysqli_query($koneksi, $sql_skor);
$daftar = [];
while ($row = mysqli_fetch_assoc($res)) {
    $row['skor'] = SKOR_AWAL - $row['total_pelanggaran'] + $row['total_prestasi'];
    $daftar[] = $row;
}
// Siswa yang perlu pembinaan HANYA siswa yang memiliki poin pelanggaran atau skor < SKOR_AWAL
$daftar_pelanggar = array_filter($daftar, fn($s) => (int)$s['total_pelanggaran'] > 0 || (int)$s['skor'] < SKOR_AWAL);
usort($daftar_pelanggar, fn($a,$b) => $a['skor'] <=> $b['skor']);
$perlu_pembinaan = array_slice($daftar_pelanggar, 0, 5);

// Siswa terbaik HANYA siswa yang telah mengumpulkan poin prestasi tambahan
$daftar_berprestasi = array_filter($daftar, fn($s) => (int)$s['total_prestasi'] > 0 || (int)$s['skor'] > SKOR_AWAL);
usort($daftar_berprestasi, fn($a,$b) => $b['skor'] <=> $a['skor']);
$siswa_terbaik = array_slice($daftar_berprestasi, 0, 5);

// Aktivitas terbaru gabungan
$sql_aktivitas = "
(SELECT 'pelanggaran' AS jenis, s.nama, kp.nama_pelanggaran AS keterangan, kp.poin, rp.tanggal, rp.created_at
 FROM riwayat_pelanggaran rp JOIN siswa s ON rp.siswa_id=s.id JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id)
UNION ALL
(SELECT 'prestasi' AS jenis, s.nama, kpr.nama_prestasi AS keterangan, kpr.poin, rpr.tanggal, rpr.created_at
 FROM riwayat_prestasi rpr JOIN siswa s ON rpr.siswa_id=s.id JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id)
ORDER BY created_at DESC LIMIT 8";
$aktivitas = mysqli_query($koneksi, $sql_aktivitas);

// Data untuk Panel Apresiasi & Prestasi Siswa
$total_prestasi_kasus = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM riwayat_prestasi"))['total'] ?? 0);
$total_poin_prestasi = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(kp.poin), 0) AS total FROM riwayat_prestasi rp JOIN kategori_prestasi kp ON rp.kategori_id=kp.id"))['total'] ?? 0);

$bintang_prestasi = null;
if (!empty($daftar_berprestasi)) {
    $bintang_prestasi = $daftar_berprestasi[0];
}

$prestasi_terbaru = mysqli_query($koneksi, "
    SELECT rpr.*, s.nama AS nama_siswa, s.nis, s.nisn, k.nama_kelas, k.jurusan, kp.nama_prestasi, kp.poin, kp.tingkat
    FROM riwayat_prestasi rpr
    JOIN siswa s ON rpr.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    JOIN kategori_prestasi kp ON rpr.kategori_id = kp.id
    ORDER BY rpr.created_at DESC LIMIT 5
");

$distribusi_tingkat = mysqli_query($koneksi, "
    SELECT kp.tingkat, COUNT(rpr.id) AS jumlah, COALESCE(SUM(kp.poin), 0) AS total_poin
    FROM kategori_prestasi kp
    LEFT JOIN riwayat_prestasi rpr ON kp.id = rpr.kategori_id
    GROUP BY kp.tingkat
    ORDER BY FIELD(kp.tingkat, 'Sekolah', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Nasional', 'Internasional')
");

$top_kelas_prestasi = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT k.nama_kelas, k.jurusan, COUNT(rpr.id) AS jumlah_prestasi, COALESCE(SUM(kp.poin), 0) AS total_poin
    FROM kelas k
    JOIN siswa s ON k.id = s.kelas_id
    JOIN riwayat_prestasi rpr ON s.id = rpr.siswa_id
    JOIN kategori_prestasi kp ON rpr.kategori_id = kp.id
    GROUP BY k.id
    ORDER BY total_poin DESC LIMIT 1
"));

$guru_users = null;
$osis_users = null;
if (isAdmin()) {
    $has_plain_col = false;
    try {
        $c_plain = @mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
        $has_plain_col = ($c_plain && mysqli_num_rows($c_plain) > 0);
    } catch (Throwable $e) {}
    $f_plain = $has_plain_col ? 'u.password_plain,' : "'' AS password_plain,";

    $guru_users = mysqli_query($koneksi, "
        SELECT u.id, u.username, u.nama_lengkap, u.role, u.kelas_id, $f_plain k.nama_kelas, k.jurusan
        FROM users u
        LEFT JOIN kelas k ON u.kelas_id = k.id
        WHERE u.role IN ('kepala_sekolah', 'guru_bk', 'wali_kelas', 'guru')
        ORDER BY FIELD(u.role, 'kepala_sekolah', 'guru_bk', 'wali_kelas', 'guru'), k.jurusan, k.nama_kelas, u.nama_lengkap
    ");

    $osis_users = mysqli_query($koneksi, "
        SELECT u.id, u.username, u.nama_lengkap, u.role, $f_plain
               (SELECT COUNT(*) FROM riwayat_pelanggaran WHERE petugas_id = u.id) AS total_input
        FROM users u
        WHERE u.role = 'osis'
        ORDER BY u.id ASC
    ");
}

include 'includes/header.php';
?>

<?php if (!empty($_SESSION['kelas_id']) && !empty($_SESSION['nama_kelas'])): 
    $wk_kelas_id = (int)$_SESSION['kelas_id'];
    $wk_stat = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT 
            COUNT(s.id) AS total_siswa,
            (SELECT COUNT(*) FROM riwayat_pelanggaran rp JOIN siswa s2 ON rp.siswa_id=s2.id WHERE s2.kelas_id=$wk_kelas_id) AS total_pelanggaran_kelas,
            (SELECT COUNT(*) FROM riwayat_prestasi rpr JOIN siswa s3 ON rpr.siswa_id=s3.id WHERE s3.kelas_id=$wk_kelas_id) AS total_prestasi_kelas
        FROM siswa s WHERE s.kelas_id=$wk_kelas_id AND s.status='aktif'
    "));
?>
<!-- Panel Khusus Wali Kelas -->
<div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #0284c7 100%); color:#fff; border-radius:14px;">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-white text-dark fw-bold px-2 py-1 shadow-sm">
                        <i class="bi bi-person-badge text-primary me-1"></i> Panel Wali Kelas
                    </span>
                    <span class="badge bg-light text-dark fw-bold px-2 py-1"><?= htmlspecialchars($_SESSION['nama_kelas']) ?></span>
                    <?= badgeJurusan($_SESSION['jurusan'] ?? '') ?>
                </div>
                <h5 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!</h5>
                <p class="mb-0 text-white-50 small">
                    Anda memegang perwalian kelas <strong><?= htmlspecialchars($_SESSION['nama_kelas']) ?></strong>. Terdaftar <strong><?= (int)($wk_stat['total_siswa'] ?? 0) ?> siswa</strong> aktif di kelas Anda.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="siswa.php?kelas=<?= $wk_kelas_id ?>" class="btn btn-light btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-people-fill text-dark me-1"></i> Siswa Kelas Saya
                </a>
                <a href="input_pelanggaran.php" class="btn btn-warning btn-sm fw-semibold shadow-sm text-dark">
                    <i class="bi bi-plus-circle-fill me-1"></i> Catat Pelanggaran
                </a>
                <a href="laporan.php?kelas=<?= $wk_kelas_id ?>" class="btn btn-outline-light btn-sm fw-semibold">
                    <i class="bi bi-file-earmark-text me-1"></i> Rekap Kelas
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (($_SESSION['role'] ?? '') === 'kepala_sekolah'): ?>
<!-- Panel Khusus Kepala Sekolah -->
<div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); color:#fff; border-radius:14px;">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm">
                        <i class="bi bi-mortarboard-fill text-dark me-1"></i> Panel Eksekutif Pimpinan
                    </span>
                    <span class="badge bg-white text-dark fw-semibold">Kepala Sekolah</span>
                </div>
                <h4 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></h4>
                <p class="mb-0 text-white-50 small">
                    Monitoring evaluasi kedisiplinan, catatan rekapitulasi poin, dan capaian prestasi siswa SMKS Tiara Nusa.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="laporan.php" class="btn btn-warning btn-sm fw-bold shadow-sm text-dark">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Rekap Nilai Sikap
                </a>
                <a href="penilaian.php" class="btn btn-light btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-patch-check-fill text-warning me-1"></i> Pantau Karakter Siswa
                </a>
                <a href="riwayat_siswa.php" class="btn btn-outline-light btn-sm fw-semibold">
                    <i class="bi bi-search me-1"></i> Cek Rekam Jejak Siswa
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isOsis()): 
    $my_id = (int)$_SESSION['user_id'];
    
    // 1. Kasus saya hari ini
    $q_my_today = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) c, COALESCE(SUM(kp.poin), 0) p 
        FROM riwayat_pelanggaran rp 
        JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id 
        WHERE rp.petugas_id = $my_id AND rp.tanggal = CURDATE()
    "));
    $my_cases_today = (int)($q_my_today['c'] ?? 0);
    $my_points_today = (int)($q_my_today['p'] ?? 0);

    // 2. Kasus saya bulan ini
    $q_my_month = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) c, COALESCE(SUM(kp.poin), 0) p 
        FROM riwayat_pelanggaran rp 
        JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id 
        WHERE rp.petugas_id = $my_id AND MONTH(rp.tanggal) = MONTH(CURDATE()) AND YEAR(rp.tanggal) = YEAR(CURDATE())
    "));
    $my_cases_month = (int)($q_my_month['c'] ?? 0);
    $my_points_month = (int)($q_my_month['p'] ?? 0);

    // 3. Total input seluruh tim OSIS hari ini
    $q_all_osis_today = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) c 
        FROM riwayat_pelanggaran rp 
        JOIN users u ON rp.petugas_id = u.id 
        WHERE u.role = 'osis' AND rp.tanggal = CURDATE()
    "));
    $all_osis_today = (int)($q_all_osis_today['c'] ?? 0);

    // 4. Log input pelanggaran oleh akun OSIS ini
    $my_submissions = mysqli_query($koneksi, "
        SELECT rp.*, s.nama AS nama_siswa, s.nis, s.nisn, k.nama_kelas, k.jurusan, kp.nama_pelanggaran, kp.poin, kp.tingkat, kp.kode
        FROM riwayat_pelanggaran rp
        JOIN siswa s ON rp.siswa_id = s.id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN kategori_pelanggaran kp ON rp.kategori_id = kp.id
        WHERE rp.petugas_id = $my_id
        ORDER BY rp.created_at DESC, rp.id DESC
        LIMIT 10
    ");
?>
<!-- ========================================================================= -->
<!-- POS KEDISIPLINAN OSIS (DEDICATED DASHBOARD KHUSUS PETUGAS PENCATATAN)     -->
<!-- ========================================================================= -->

<!-- 1. HERO BANNER PETUGAS OSIS -->
<div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 45%, #0f766e 100%); color:#fff; border-radius:20px; overflow:hidden; position:relative;">
    <div style="position:absolute; top:-40px; right:-40px; width:180px; height:180px; border-radius:50%; background:radial-gradient(circle, rgba(52,211,153,0.3) 0%, transparent 70%); pointer-events:none;"></div>
    <div class="card-body p-4 p-md-4 position-relative" style="z-index:1;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-white text-success fw-bold px-3 py-1 shadow-sm" style="border-radius:20px;">
                        <i class="bi bi-shield-check text-success me-1"></i> Tim Ketertiban &amp; Kedisiplinan
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.18); color:#fff; border: 1px solid rgba(255,255,255,0.25); border-radius:20px;">
                        OSIS SMKS Tiara Nusa
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-white" style="letter-spacing: -0.02em;">Selamat Bertugas, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!</h3>
                <p class="mb-0 text-white-50 small" style="font-size: 0.88rem; max-width: 620px;">
                    Portal khusus pencatatan tata tertib harian siswa. Catatlah pelanggaran secara objektif, jujur, teliti, dan penuh tanggung jawab.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <a href="input_pelanggaran.php" class="btn btn-warning fw-bold px-3 py-2 shadow-sm text-dark d-flex align-items-center gap-2" style="border-radius: 12px;">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <span>Catat Pelanggaran Siswa</span>
                </a>
                <a href="siswa.php" class="btn btn-light fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2 text-dark" style="border-radius: 12px;">
                    <i class="bi bi-search"></i>
                    <span>Cari Data Siswa</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 2. METRIK PRIBADI PETUGAS OSIS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing:0.5px; font-size:0.75rem;">Input Saya Hari Ini</span>
                <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px; height:34px; background:#ecfdf5; color:#059669;">
                    <i class="bi bi-calendar-check-fill"></i>
                </span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h2 class="fw-bold mb-0 text-dark"><?= $my_cases_today ?></h2>
                <span class="text-muted small">kasus</span>
                <?php if ($my_cases_today > 0): ?>
                    <span class="badge bg-danger ms-auto">-<?= $my_points_today ?> Poin</span>
                <?php endif; ?>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size:0.78rem;">
                Jumlah pelanggaran yang Anda catat pada tanggal <?= date('d M Y') ?>
            </small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing:0.5px; font-size:0.75rem;">Input Saya Bulan Ini</span>
                <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px; height:34px; background:#eff6ff; color:#2563eb;">
                    <i class="bi bi-calendar-month-fill"></i>
                </span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h2 class="fw-bold mb-0 text-dark"><?= $my_cases_month ?></h2>
                <span class="text-muted small">kasus</span>
                <?php if ($my_cases_month > 0): ?>
                    <span class="badge bg-danger ms-auto">-<?= $my_points_month ?> Poin</span>
                <?php endif; ?>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size:0.78rem;">
                Akumulasi kontribusi pencatatan Anda selama bulan <?= date('F Y') ?>
            </small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing:0.5px; font-size:0.75rem;">Total Tim OSIS Hari Ini</span>
                <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px; height:34px; background:#fef3c7; color:#d97706;">
                    <i class="bi bi-people-fill"></i>
                </span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h2 class="fw-bold mb-0 text-dark"><?= $all_osis_today ?></h2>
                <span class="text-muted small">kasus tercatat</span>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size:0.78rem;">
                Total pelanggaran yang dicatat seluruh petugas OSIS hari ini
            </small>
        </div>
    </div>
</div>

<!-- 3. LOG PENCATATAN SAYA & PANDUAN PIKET OSIS -->
<div class="row g-4 mb-4">
    <!-- Kolom Kiri: Riwayat Input yang Dicatat oleh Akun Ini -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100" style="border-radius:18px; overflow:hidden;">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:30px; height:30px; background:#ecfdf5; color:#059669;">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <h6 class="fw-bold mb-0 text-dark">Log Pencatatan Saya Terbaru</h6>
                </div>
                <a href="input_pelanggaran.php" class="btn btn-sm btn-outline-primary fw-semibold" style="border-radius:8px;">
                    <i class="bi bi-plus-lg me-1"></i> Input Baru
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px;">
                            <tr>
                                <th class="ps-4">Waktu</th>
                                <th>Siswa &amp; Kelas</th>
                                <th>Pelanggaran</th>
                                <th>Poin</th>
                                <th class="pe-4">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:0.88rem;">
                            <?php if (mysqli_num_rows($my_submissions) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <div class="fw-semibold text-dark">Belum Ada Pelanggaran yang Anda Catat</div>
                                        <small class="text-muted">Klik tombol "Catat Pelanggaran Siswa" di atas untuk mulai mencatat ketertiban.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($sub = mysqli_fetch_assoc($my_submissions)): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small" style="width:110px;">
                                            <div><?= date('d/m/Y', strtotime($sub['tanggal'])) ?></div>
                                            <small class="text-secondary" style="font-size:0.72rem;"><?= date('H:i', strtotime($sub['created_at'])) ?> WIB</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($sub['nama_siswa']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($sub['nama_kelas']) ?> &bull; <?= htmlspecialchars($sub['jurusan']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($sub['nama_pelanggaran']) ?>">
                                                <?= htmlspecialchars($sub['nama_pelanggaran']) ?>
                                            </div>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem;">
                                                <?= htmlspecialchars($sub['tingkat']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger text-white fw-bold">
                                                -<?= (int)$sub['poin'] ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-muted small" style="max-width:200px;">
                                            <div class="text-truncate" title="<?= htmlspecialchars($sub['keterangan'] ?: '-') ?>">
                                                <?= htmlspecialchars($sub['keterangan'] ?: '-') ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light px-4 py-2 border-top small text-muted">
                <i class="bi bi-info-circle me-1 text-primary"></i> Menampilkan maksimal 10 data pelanggaran terakhir yang Anda inputkan ke sistem.
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Panduan & SOP Piket OSIS -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:18px;">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-bookmark-fill text-warning me-2"></i>Panduan &amp; Kode Etik OSIS</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px; background:#ecfdf5; color:#059669;">
                        <i class="bi bi-check2-circle fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Objektif &amp; Netral</div>
                        <p class="text-muted small mb-0" style="font-size:0.8rem; line-height:1.4;">
                            Catat pelanggaran berdasarkan fakta nyata di lapangan. Dilarang membedakan atau melindungi teman dekat.
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px; background:#eff6ff; color:#2563eb;">
                        <i class="bi bi-person-bounding-box fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Teliti Identitas Siswa</div>
                        <p class="text-muted small mb-0" style="font-size:0.8rem; line-height:1.4;">
                            Pastikan mengecek nama lengkap, kelas, atau NISN agar poin pelanggaran tidak salah masuk ke siswa lain.
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px; background:#fef2f2; color:#dc2626;">
                        <i class="bi bi-shield-exclamation fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Koordinasi dengan Guru</div>
                        <p class="text-muted small mb-0" style="font-size:0.8rem; line-height:1.4;">
                            Jika menemukan pelanggaran berat atau siswa bersikap tidak kooperatif/menantang, segera lapor ke Guru Piket atau Guru BK.
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light p-3 border-top text-center">
                <a href="input_pelanggaran.php" class="btn btn-success btn-sm fw-semibold w-100 py-2" style="border-radius:10px;">
                    <i class="bi bi-plus-circle me-1"></i> Mulai Pencatatan Sekarang
                </a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Banner Ringkasan Jurusan MPLB & TJKT -->
<div class="card p-3 mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#fff; border-radius:14px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="badge bg-light text-dark fw-bold mb-1">SMK PUSAT KEUNGGULAN</span>
            <h5 class="fw-bold mb-0">Kompetensi Keahlian: MPLB &amp; TJKT</h5>
            <small class="text-white-50">Monitoring kedisiplinan dan capaian prestasi per jurusan SMKS Tiara Nusa</small>
        </div>
        <div class="d-flex gap-2">
            <div class="px-3 py-2 text-center rounded" style="background: rgba(13, 148, 136, 0.25); border: 1px solid rgba(13, 148, 136, 0.4);">
                <div class="small text-white-50">Siswa MPLB</div>
                <div class="fs-5 fw-bold" style="color: #2dd4bf"><?= $total_mplb ?> <small class="fs-6 fw-normal">Siswa</small></div>
            </div>
            <div class="px-3 py-2 text-center rounded" style="background: rgba(99, 102, 241, 0.25); border: 1px solid rgba(99, 102, 241, 0.4);">
                <div class="small text-white-50">Siswa TJKT</div>
                <div class="fs-5 fw-bold" style="color: #818cf8"><?= $total_tjkt ?> <small class="fs-6 fw-normal">Siswa</small></div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Utama Modern -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Siswa Aktif -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-modern stat-card-blue h-100">
            <i class="bi bi-people-fill stat-watermark"></i>
            <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
                <div>
                    <div class="stat-label">Total Siswa Aktif</div>
                    <div class="stat-value"><?= number_format($total_siswa, 0, ',', '.') ?></div>
                </div>
                <div class="stat-icon-glass">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="position-relative" style="z-index: 2;">
                <div class="stat-pill">
                    <i class="bi bi-mortarboard-fill"></i> MPLB &amp; TJKT Terdaftar
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Pelanggaran Bulan Ini -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-modern stat-card-rose h-100">
            <i class="bi bi-exclamation-triangle-fill stat-watermark"></i>
            <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
                <div>
                    <div class="stat-label">Pelanggaran Bulan Ini</div>
                    <div class="stat-value"><?= number_format($total_pelanggaran_bulan, 0, ',', '.') ?></div>
                </div>
                <div class="stat-icon-glass">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
            <div class="position-relative" style="z-index: 2;">
                <div class="stat-pill">
                    <i class="bi bi-calendar2-event-fill"></i> Rekap Bulan Berjalan
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Prestasi Bulan Ini -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-modern stat-card-emerald h-100">
            <i class="bi bi-trophy-fill stat-watermark"></i>
            <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
                <div>
                    <div class="stat-label">Prestasi Bulan Ini</div>
                    <div class="stat-value"><?= number_format($total_prestasi_bulan, 0, ',', '.') ?></div>
                </div>
                <div class="stat-icon-glass">
                    <i class="bi bi-trophy-fill"></i>
                </div>
            </div>
            <div class="position-relative" style="z-index: 2;">
                <div class="stat-pill">
                    <i class="bi bi-award-fill"></i> Capaian Prestasi Siswa
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Total Rombel / Kelas -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-modern stat-card-purple h-100">
            <i class="bi bi-building-fill stat-watermark"></i>
            <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
                <div>
                    <div class="stat-label">Total Rombel / Kelas</div>
                    <div class="stat-value"><?= number_format($total_kelas, 0, ',', '.') ?></div>
                </div>
                <div class="stat-icon-glass">
                    <i class="bi bi-building-fill"></i>
                </div>
            </div>
            <div class="position-relative" style="z-index: 2;">
                <div class="stat-pill">
                    <i class="bi bi-door-open-fill"></i> <?= $total_kelas ?> Rombel Aktif
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin() && $guru_users): ?>
<!-- PANEL KHUSUS ADMIN: DATA AKUN GURU & WALI KELAS -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                    <i class="bi bi-shield-lock-fill me-1"></i> <?= isAdmin() ? 'Khusus Administrator' : 'Panel Pimpinan Sekolah' ?>
                </span>
                <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= mysqli_num_rows($guru_users) ?> Akun Terdaftar</span>
                <?php if ($osis_users && mysqli_num_rows($osis_users) > 0): ?>
                    <a href="#panelAkunOsis" class="badge bg-success-subtle text-success text-decoration-none border border-success-subtle fw-semibold">
                        <i class="bi bi-shield-check me-1"></i> <?= mysqli_num_rows($osis_users) ?> Akun OSIS &darr;
                    </a>
                <?php endif; ?>
            </div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-vcard text-primary me-2"></i>Panel Akun Pendidik &amp; Kepala Sekolah</h5>
            <small class="text-muted">Data kredensial login (Username &amp; Password) untuk Kepala Sekolah, Dewan Guru &amp; Wali Kelas.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="filterGuruTable" class="form-control bg-light border-start-0" placeholder="Cari guru / kelas..." onkeyup="filterTabelGuru()">
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="salinSemuaAkun()" title="Salin seluruh akun untuk WhatsApp">
                <i class="bi bi-clipboard-check me-1"></i> Salin Semua Akun
            </button>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="btn btn-dark btn-sm fw-semibold shadow-xs">
                <i class="bi bi-person-gear me-1"></i> Kelola User
            </a>
            <?php else: ?>
            <span class="badge bg-light text-secondary border py-2 px-3">
                <i class="bi bi-eye me-1"></i> Mode Monitoring Eksekutif
            </span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" id="tabelAkunGuru">
                <thead class="table-light text-secondary sticky-top" style="font-size:0.82rem; letter-spacing:0.3px; z-index:1;">
                    <tr>
                        <th class="ps-3 ps-md-4" style="width:50px;">No</th>
                        <th>Nama Pendidik / Pimpinan</th>
                        <th>Peran &amp; Penugasan</th>
                        <th>Jurusan</th>
                        <th>Username</th>
                        <th>Password Default</th>
                        <?php if (isAdmin()): ?>
                        <th class="text-end pe-3 pe-md-4">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody style="font-size:0.9rem;">
                    <?php 
                    $no_guru = 1;
                    mysqli_data_seek($guru_users, 0);
                    while ($g = mysqli_fetch_assoc($guru_users)): 
                        $default_pass = !empty($g['password_plain']) ? $g['password_plain'] : ($g['username'] . '123');
                        if (empty($g['password_plain'])) {
                            if ($g['username'] === 'gurubk') $default_pass = 'gurubk123';
                            elseif ($g['username'] === 'walikelas') $default_pass = 'walikelas123';
                            elseif ($g['username'] === 'kepsek') $default_pass = 'kepsek123';
                        }
                        
                        $role_badge = [
                            'kepala_sekolah' => '<span class="badge bg-warning text-dark border border-warning-subtle fw-bold role-label-text"><i class="bi bi-mortarboard-fill me-1"></i>Kepala Sekolah</span>',
                            'guru_bk'        => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle role-label-text"><i class="bi bi-heart-pulse me-1"></i>Guru BK</span>',
                            'wali_kelas'     => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle role-label-text"><i class="bi bi-person-badge me-1"></i>Wali Kelas</span>',
                            'guru'           => '<span class="badge bg-secondary-subtle text-secondary role-label-text"><i class="bi bi-person me-1"></i>Guru</span>'
                        ][$g['role']] ?? '<span class="badge bg-secondary role-label-text">' . htmlspecialchars($g['role']) . '</span>';
                    ?>
                    <tr class="item-guru">
                        <td class="ps-3 ps-md-4 text-muted small"><?= $no_guru++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle rounded-circle bg-light border text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px; font-size:0.85rem;">
                                    <?php if ($g['role'] === 'kepala_sekolah'): ?>
                                        <i class="bi bi-mortarboard-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-person-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark nama-guru"><?= htmlspecialchars($g['nama_lengkap']) ?></div>
                                    <small class="text-muted">ID User: #<?= $g['id'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <?= $role_badge ?>
                                <?php if ($g['role'] === 'guru_bk' && !empty($g['nama_kelas'])): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle role-label-text"><i class="bi bi-person-badge me-1"></i>Wali Kelas</span>
                                <?php endif; ?>
                                <?php if (!empty($g['nama_kelas'])): ?>
                                    <span class="badge bg-dark text-white fw-semibold kelas-guru"><?= htmlspecialchars($g['nama_kelas']) ?></span>
                                <?php elseif ($g['role'] === 'wali_kelas'): ?>
                                    <span class="badge bg-light text-muted border">Perwalian Demo</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($g['role'] === 'kepala_sekolah'): ?>
                                <span class="badge bg-light text-dark border"><i class="bi bi-building-gear me-1"></i>Pimpinan</span>
                            <?php elseif ($g['role'] === 'guru_bk' && empty($g['jurusan'])): ?>
                                <span class="badge bg-light text-muted border">Semua Jurusan</span>
                            <?php else: ?>
                                <?= badgeJurusan($g['jurusan']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                                <code class="text-primary fw-bold username-val"><?= htmlspecialchars($g['username']) ?></code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="copyText('<?= htmlspecialchars($g['username']) ?>', this)" title="Salin Username">
                                    <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                                <code class="text-dark fw-semibold pass-val"><?= htmlspecialchars($default_pass) ?></code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="copyText('<?= htmlspecialchars($default_pass) ?>', this)" title="Salin Password">
                                    <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                                </button>
                            </div>
                        </td>
                        <?php if (isAdmin()): ?>
                        <td class="text-end pe-3 pe-md-4">
                            <a href="users.php" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:0.8rem;" title="Edit / Ganti Password">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterTabelGuru() {
    const input = document.getElementById('filterGuruTable').value.toLowerCase();
    const rows = document.querySelectorAll('#tabelAkunGuru tbody tr.item-guru');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(input) ? '' : 'none';
    });
}

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        const origClass = icon.className;
        icon.className = 'bi bi-check-lg text-success';
        setTimeout(() => { icon.className = origClass; }, 1500);
    });
}

function salinSemuaAkun() {
    let text = "📋 DAFTAR AKUN LOGIN E-POINT SMKS TIARA NUSA\n";
    text += "========================================\n\n";
    const rows = document.querySelectorAll('#tabelAkunGuru tbody tr.item-guru');
    rows.forEach((r, idx) => {
        const nama = r.querySelector('.nama-guru')?.innerText.trim() || '';
        const kelas = r.querySelector('.kelas-guru')?.innerText.trim() || '';
        const username = r.querySelector('.username-val')?.innerText.trim() || '';
        const pass = r.querySelector('.pass-val')?.innerText.trim() || '';
        const roleBadges = Array.from(r.querySelectorAll('.role-label-text')).map(el => el.innerText.trim());
        let peran = '';
        if (roleBadges.includes('Guru BK') && kelas) {
            peran = `Guru BK & Wali Kelas ${kelas}`;
        } else if (kelas && username !== 'walikelas') {
            peran = `Wali Kelas ${kelas}`;
        } else if (roleBadges.length > 0) {
            peran = roleBadges.join(' & ');
        } else {
            peran = 'Pendidik';
        }
        text += `${idx + 1}. ${nama} (${peran})\n   Username: ${username} | Password: ${pass}\n\n`;
    });
    text += "Link Login: " + window.location.origin + "/epoint/login.php\n";
    text += "Catatan: Harap segera ubah password setelah login pertama kali.";
    
    navigator.clipboard.writeText(text).then(() => {
        alert("✅ Seluruh data akun berhasil disalin ke clipboard! Anda dapat langsung menempelkannya (paste) ke WhatsApp atau dokumen catatan.");
    });
}
</script>
<?php endif; ?>

<?php if (isAdmin() && $osis_users && mysqli_num_rows($osis_users) > 0): ?>
<!-- PANEL KHUSUS ADMIN: DATA AKUN PETUGAS KEDISIPLINAN OSIS -->
<div class="card border-0 shadow-sm mb-4" id="panelAkunOsis" style="border-radius:14px; overflow:hidden;">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                    <i class="bi bi-shield-check me-1"></i> Tim Ketertiban Siswa
                </span>
                <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= mysqli_num_rows($osis_users) ?> Akun OSIS Aktif</span>
                <span class="badge bg-light text-muted border"><i class="bi bi-key me-1"></i>Password Login</span>
            </div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge-fill text-success me-2"></i>Panel Akun Petugas Kedisiplinan OSIS</h5>
            <small class="text-muted">Data username &amp; password akun siswa OSIS untuk dibagikan / dibuatkan akses pencatatan pelanggaran tata tertib.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="input-group input-group-sm" style="width: 200px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="filterOsisTable" class="form-control bg-light border-start-0" placeholder="Cari akun OSIS..." onkeyup="filterTabelOsis()">
            </div>
            <button type="button" class="btn btn-outline-success btn-sm fw-semibold" onclick="salinAkunOsis()" title="Salin seluruh akun OSIS dalam format pesan WhatsApp">
                <i class="bi bi-whatsapp me-1"></i> Salin Akun OSIS (WhatsApp)
            </button>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="btn btn-dark btn-sm fw-semibold shadow-xs">
                <i class="bi bi-person-plus-fill me-1"></i> Buat / Kelola Akun
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelAkunOsis">
                <thead class="table-light text-secondary" style="font-size:0.82rem; letter-spacing:0.3px;">
                    <tr>
                        <th class="ps-3 ps-md-4" style="width:50px;">No</th>
                        <th>Nama Petugas OSIS</th>
                        <th>Peran &amp; Hak Akses</th>
                        <th>Username</th>
                        <th>Password Default</th>
                        <th>Kasus Dicatat</th>
                        <?php if (isAdmin()): ?>
                        <th class="text-end pe-3 pe-md-4">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody style="font-size:0.9rem;">
                    <?php 
                    $no_osis = 1;
                    mysqli_data_seek($osis_users, 0);
                    while ($o = mysqli_fetch_assoc($osis_users)): 
                        $default_pass_osis = !empty($o['password_plain']) ? $o['password_plain'] : ($o['username'] . '123');
                    ?>
                    <tr class="item-osis">
                        <td class="ps-3 ps-md-4 text-muted small"><?= $no_osis++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle rounded-circle bg-success-subtle border border-success-subtle text-success fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px; font-size:0.85rem;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark nama-osis"><?= htmlspecialchars($o['nama_lengkap']) ?></div>
                                    <small class="text-muted">ID User: #<?= $o['id'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success text-white fw-bold role-label-osis"><i class="bi bi-shield-fill-check me-1"></i>Petugas OSIS</span>
                            <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Akses: Input Pelanggaran &amp; Cari Siswa</small>
                        </td>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                                <code class="text-success fw-bold username-osis-val"><?= htmlspecialchars($o['username']) ?></code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="copyText('<?= htmlspecialchars($o['username']) ?>', this)" title="Salin Username">
                                    <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                                <code class="text-dark fw-semibold pass-osis-val"><?= htmlspecialchars($default_pass_osis) ?></code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="copyText('<?= htmlspecialchars($default_pass_osis) ?>', this)" title="Salin Password">
                                    <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-journal-text me-1 text-primary"></i><?= (int)$o['total_input'] ?> Kasus
                            </span>
                        </td>
                        <?php if (isAdmin()): ?>
                        <td class="text-end pe-3 pe-md-4">
                            <a href="users.php" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:0.8rem;" title="Edit / Ganti Password">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light py-2 px-3 px-md-4 border-top">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
            <div>
                <i class="bi bi-info-circle-fill text-success me-1"></i>
                Password akun OSIS dapat dilihat pada tabel di atas (default: <code>[username]123</code> atau password khusus yang diatur oleh Admin).
            </div>
            <?php if (isAdmin()): ?>
            <div>
                <a href="users.php" class="text-decoration-none text-success fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Buat / Tambah Akun OSIS Baru di Menu Kelola User &rarr;
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterTabelOsis() {
    const input = document.getElementById('filterOsisTable').value.toLowerCase();
    const rows = document.querySelectorAll('#tabelAkunOsis tbody tr.item-osis');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(input) ? '' : 'none';
    });
}

function salinAkunOsis() {
    let text = "🛡️ DAFTAR AKUN PETUGAS KEDISIPLINAN OSIS - SMKS TIARA NUSA\n";
    text += "========================================================\n";
    text += "Berikut adalah username dan password akun Petugas OSIS untuk pencatatan pelanggaran siswa:\n\n";
    const rows = document.querySelectorAll('#tabelAkunOsis tbody tr.item-osis');
    rows.forEach((r, idx) => {
        const nama = r.querySelector('.nama-osis')?.innerText.trim() || '';
        const username = r.querySelector('.username-osis-val')?.innerText.trim() || '';
        const pass = r.querySelector('.pass-osis-val')?.innerText.trim() || '';
        text += `${idx + 1}. ${nama}\n   Username: ${username} | Password: ${pass}\n\n`;
    });
    text += "Link Login: " + window.location.origin + "/epoint/login.php\n";
    text += "Tugas: Mencatat pelanggaran tata tertib siswa secara jujur, objektif, dan bertanggung jawab.";
    
    navigator.clipboard.writeText(text).then(() => {
        alert("✅ Seluruh data akun OSIS berhasil disalin ke clipboard! Anda dapat langsung menempelkannya (paste) ke WhatsApp atau membagikannya ke siswa.");
    });
}
</script>
<?php endif; ?>

<!-- SECTION PERSENTASE PELANGGARAN PER HARI, PER MINGGU, PER BULAN, PER TAHUN -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-percent text-danger me-1"></i> Analisis Persentase Pelanggaran</h6>
            <small class="text-muted">Statistik keterlibatan siswa dan rekapitulasi pelanggaran per hari, per minggu, per bulan, dan per tahun</small>
        </div>
        <span class="badge bg-light text-muted border">Update Otomatis Real-Time</span>
    </div>

    <div class="row g-3">
        <!-- 1. PER HARI -->
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-top border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-calendar-event text-danger me-1"></i> Per Hari</span>
                    <span class="badge bg-danger-subtle text-danger fw-semibold"><?= date('d M Y') ?></span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fs-3 fw-bold text-dark"><?= $stat_hari['kasus'] ?></span>
                    <span class="text-muted small">kasus</span>
                    <span class="badge bg-danger text-white ms-auto">-<?= $stat_hari['poin'] ?> Poin</span>
                </div>
                
                <div class="mt-2 p-2 rounded bg-light border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Persentase Siswa:</span>
                        <span class="fw-bold text-danger"><?= $stat_hari['pct_siswa'] ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: <?= min(100, $stat_hari['pct_siswa']) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.72rem;">
                        <span><?= $stat_hari['siswa'] ?> dari <?= $total_siswa ?> siswa aktif</span>
                        <span><?= $stat_hari['pct_share'] ?>% total kasus</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. PER MINGGU -->
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-top border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-calendar-range text-warning me-1"></i> Per Minggu</span>
                    <span class="badge bg-warning-subtle text-dark fw-semibold">Minggu Ke-<?= date('W') ?></span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fs-3 fw-bold text-dark"><?= $stat_minggu['kasus'] ?></span>
                    <span class="text-muted small">kasus</span>
                    <span class="badge bg-warning text-dark ms-auto">-<?= $stat_minggu['poin'] ?> Poin</span>
                </div>
                
                <div class="mt-2 p-2 rounded bg-light border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Persentase Siswa:</span>
                        <span class="fw-bold text-warning-emphasis"><?= $stat_minggu['pct_siswa'] ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: <?= min(100, $stat_minggu['pct_siswa']) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.72rem;">
                        <span><?= $stat_minggu['siswa'] ?> dari <?= $total_siswa ?> siswa aktif</span>
                        <span><?= $stat_minggu['pct_share'] ?>% total kasus</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. PER BULAN -->
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-top border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-calendar-month text-info me-1"></i> Per Bulan</span>
                    <span class="badge bg-info-subtle text-dark fw-semibold"><?= date('F Y') ?></span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fs-3 fw-bold text-dark"><?= $stat_bulan['kasus'] ?></span>
                    <span class="text-muted small">kasus</span>
                    <span class="badge bg-info text-dark ms-auto">-<?= $stat_bulan['poin'] ?> Poin</span>
                </div>
                
                <div class="mt-2 p-2 rounded bg-light border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Persentase Siswa:</span>
                        <span class="fw-bold text-info-emphasis"><?= $stat_bulan['pct_siswa'] ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: <?= min(100, $stat_bulan['pct_siswa']) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.72rem;">
                        <span><?= $stat_bulan['siswa'] ?> dari <?= $total_siswa ?> siswa aktif</span>
                        <span><?= $stat_bulan['pct_share'] ?>% total kasus</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. PER TAHUN -->
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-top border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-calendar-check text-primary me-1"></i> Per Tahun</span>
                    <span class="badge bg-primary-subtle text-primary fw-semibold">Tahun <?= date('Y') ?></span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fs-3 fw-bold text-dark"><?= $stat_tahun['kasus'] ?></span>
                    <span class="text-muted small">kasus</span>
                    <span class="badge bg-primary text-white ms-auto">-<?= $stat_tahun['poin'] ?> Poin</span>
                </div>
                
                <div class="mt-2 p-2 rounded bg-light border border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Persentase Siswa:</span>
                        <span class="fw-bold text-primary"><?= $stat_tahun['pct_siswa'] ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: <?= min(100, $stat_tahun['pct_siswa']) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.72rem;">
                        <span><?= $stat_tahun['siswa'] ?> dari <?= $total_siswa ?> siswa aktif</span>
                        <span><?= $stat_tahun['pct_share'] ?>% total kasus</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAIL ANALISIS PERSENTASE KOMPARATIF BERDASARKAN PERIODE -->
    <div class="card p-3 border-0 shadow-sm mt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-0">Rincian Komparasi Persentase Pelanggaran</h6>
                <small class="text-muted">Pilih periode untuk melihat perbandingan persentase jurusan, tingkat keparahan, dan rasio kedisiplinan</small>
            </div>
            <ul class="nav nav-pills nav-fill gap-1" id="tabPeriode" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-3 btn-sm" id="tab-hari-btn" data-bs-toggle="pill" data-bs-target="#tab-hari" type="button">Per Hari</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3 btn-sm" id="tab-minggu-btn" data-bs-toggle="pill" data-bs-target="#tab-minggu" type="button">Per Minggu</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3 btn-sm" id="tab-bulan-btn" data-bs-toggle="pill" data-bs-target="#tab-bulan" type="button">Per Bulan</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3 btn-sm" id="tab-tahun-btn" data-bs-toggle="pill" data-bs-target="#tab-tahun" type="button">Per Tahun</button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="tabPeriodeContent">
            <?php 
            $tab_list = [
                'hari'   => ['label' => 'Hari Ini', 'data' => $stat_hari],
                'minggu' => ['label' => 'Minggu Ini', 'data' => $stat_minggu],
                'bulan'  => ['label' => 'Bulan Ini', 'data' => $stat_bulan],
                'tahun'  => ['label' => 'Tahun Ini', 'data' => $stat_tahun]
            ];
            $is_first = true;
            foreach ($tab_list as $key => $t):
                $st = $t['data'];
                $tertib_pct = max(0, 100 - $st['pct_siswa']);
                $tertib_jml = max(0, $total_siswa - $st['siswa']);
            ?>
            <div class="tab-pane fade <?= $is_first ? 'show active' : '' ?>" id="tab-<?= $key ?>" role="tabpanel">
                <div class="row g-3">
                    <!-- 1. Persentase Jurusan (MPLB vs TJKT) -->
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light border h-100 d-flex flex-column">
                            <div class="fw-bold mb-2 small text-dark d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-pie-chart-fill text-primary me-1"></i> Proporsi Jurusan</span>
                                <span class="badge bg-secondary-subtle text-secondary border"><?= $st['kasus'] ?> Kasus</span>
                            </div>
                            
                            <!-- Grafik Donut Jurusan -->
                            <div class="my-2" style="height: 160px; position: relative;">
                                <canvas id="chart-jurusan-<?= $key ?>"></canvas>
                            </div>

                            <div class="mt-auto pt-2 border-top">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><?= badgeJurusan('MPLB') ?></span>
                                        <span class="fw-bold" style="color:#0f766e"><?= $st['pct_mplb'] ?>% <small class="text-muted">(<?= $st['jml_mplb'] ?>)</small></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?= $st['pct_mplb'] ?>%; background-color:#0d9488;"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><?= badgeJurusan('TJKT') ?></span>
                                        <span class="fw-bold" style="color:#4338ca"><?= $st['pct_tjkt'] ?>% <small class="text-muted">(<?= $st['jml_tjkt'] ?>)</small></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?= $st['pct_tjkt'] ?>%; background-color:#6366f1;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Persentase Tingkat Keparahan -->
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light border h-100 d-flex flex-column">
                            <div class="fw-bold mb-2 small text-dark d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-shield-exclamation text-danger me-1"></i> Tingkat Pelanggaran</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">-<?= $st['poin'] ?> Poin Total</span>
                            </div>
                            
                            <!-- Grafik Donut Tingkat Pelanggaran -->
                            <div class="my-2" style="height: 160px; position: relative;">
                                <canvas id="chart-tingkat-<?= $key ?>"></canvas>
                            </div>

                            <div class="mt-auto pt-2 border-top">
                                <div class="mb-1">
                                    <div class="d-flex justify-content-between small">
                                        <span class="text-muted"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#06b6d4"></span>Ringan</span>
                                        <span class="fw-semibold text-info-emphasis"><?= $st['pct_ringan'] ?>% (<?= $st['jml_ringan'] ?>)</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 5px;">
                                        <div class="progress-bar bg-info" style="width: <?= $st['pct_ringan'] ?>%;"></div>
                                    </div>
                                </div>

                                <div class="mb-1">
                                    <div class="d-flex justify-content-between small">
                                        <span class="text-muted"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#f59e0b"></span>Sedang</span>
                                        <span class="fw-semibold text-warning-emphasis"><?= $st['pct_sedang'] ?>% (<?= $st['jml_sedang'] ?>)</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 5px;">
                                        <div class="progress-bar bg-warning" style="width: <?= $st['pct_sedang'] ?>%;"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between small">
                                        <span class="text-muted"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#ef4444"></span>Berat</span>
                                        <span class="fw-semibold text-danger"><?= $st['pct_berat'] ?>% (<?= $st['jml_berat'] ?>)</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-danger" style="width: <?= $st['pct_berat'] ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Rasio Kepatuhan Siswa -->
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light border h-100 d-flex flex-column">
                            <div class="fw-bold mb-2 small text-dark d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-person-check-fill text-success me-1"></i> Rasio Kepatuhan</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $t['label'] ?></span>
                            </div>

                            <!-- Grafik Donut Rasio Kepatuhan -->
                            <div class="my-2" style="height: 160px; position: relative;">
                                <canvas id="chart-kepatuhan-<?= $key ?>"></canvas>
                            </div>

                            <div class="mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Siswa Tertib</span>
                                    <span class="fw-bold text-success"><?= $tertib_pct ?>% <small class="text-muted fw-normal">(<?= $tertib_jml ?>)</small></span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $tertib_pct ?>%;"></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-danger fw-semibold"><i class="bi bi-exclamation-circle-fill me-1"></i>Terlibat Pelanggaran</span>
                                    <span class="fw-bold text-danger"><?= $st['pct_siswa'] ?>% <small class="text-muted fw-normal">(<?= $st['siswa'] ?>)</small></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" style="width: <?= $st['pct_siswa'] ?>%;"></div>
                                </div>

                                <div class="text-muted mt-2 text-center" style="font-size: 0.72rem;">
                                    <?= $tertib_jml ?> dari <?= $total_siswa ?> siswa tidak melakukan pelanggaran pada periode ini.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $is_first = false;
            endforeach; 
            ?>
        </div>
    </div>
</div>

<?php
// Siapkan konfigurasi data grafik per periode untuk Chart.js
$chart_config_data = [
    'hari' => [
        'jurusan' => [
            'labels' => ['MPLB', 'TJKT'],
            'data' => [$stat_hari['jml_mplb'], $stat_hari['jml_tjkt']],
            'colors' => ['#0d9488', '#6366f1'],
            'total' => $stat_hari['kasus']
        ],
        'tingkat' => [
            'labels' => ['Ringan', 'Sedang', 'Berat'],
            'data' => [$stat_hari['jml_ringan'], $stat_hari['jml_sedang'], $stat_hari['jml_berat']],
            'colors' => ['#06b6d4', '#f59e0b', '#ef4444'],
            'total' => $stat_hari['kasus']
        ],
        'kepatuhan' => [
            'labels' => ['Tertib', 'Melanggar'],
            'data' => [max(0, $total_siswa - $stat_hari['siswa']), $stat_hari['siswa']],
            'colors' => ['#10b981', '#ef4444'],
            'total' => $total_siswa
        ]
    ],
    'minggu' => [
        'jurusan' => [
            'labels' => ['MPLB', 'TJKT'],
            'data' => [$stat_minggu['jml_mplb'], $stat_minggu['jml_tjkt']],
            'colors' => ['#0d9488', '#6366f1'],
            'total' => $stat_minggu['kasus']
        ],
        'tingkat' => [
            'labels' => ['Ringan', 'Sedang', 'Berat'],
            'data' => [$stat_minggu['jml_ringan'], $stat_minggu['jml_sedang'], $stat_minggu['jml_berat']],
            'colors' => ['#06b6d4', '#f59e0b', '#ef4444'],
            'total' => $stat_minggu['kasus']
        ],
        'kepatuhan' => [
            'labels' => ['Tertib', 'Melanggar'],
            'data' => [max(0, $total_siswa - $stat_minggu['siswa']), $stat_minggu['siswa']],
            'colors' => ['#10b981', '#ef4444'],
            'total' => $total_siswa
        ]
    ],
    'bulan' => [
        'jurusan' => [
            'labels' => ['MPLB', 'TJKT'],
            'data' => [$stat_bulan['jml_mplb'], $stat_bulan['jml_tjkt']],
            'colors' => ['#0d9488', '#6366f1'],
            'total' => $stat_bulan['kasus']
        ],
        'tingkat' => [
            'labels' => ['Ringan', 'Sedang', 'Berat'],
            'data' => [$stat_bulan['jml_ringan'], $stat_bulan['jml_sedang'], $stat_bulan['jml_berat']],
            'colors' => ['#06b6d4', '#f59e0b', '#ef4444'],
            'total' => $stat_bulan['kasus']
        ],
        'kepatuhan' => [
            'labels' => ['Tertib', 'Melanggar'],
            'data' => [max(0, $total_siswa - $stat_bulan['siswa']), $stat_bulan['siswa']],
            'colors' => ['#10b981', '#ef4444'],
            'total' => $total_siswa
        ]
    ],
    'tahun' => [
        'jurusan' => [
            'labels' => ['MPLB', 'TJKT'],
            'data' => [$stat_tahun['jml_mplb'], $stat_tahun['jml_tjkt']],
            'colors' => ['#0d9488', '#6366f1'],
            'total' => $stat_tahun['kasus']
        ],
        'tingkat' => [
            'labels' => ['Ringan', 'Sedang', 'Berat'],
            'data' => [$stat_tahun['jml_ringan'], $stat_tahun['jml_sedang'], $stat_tahun['jml_berat']],
            'colors' => ['#06b6d4', '#f59e0b', '#ef4444'],
            'total' => $stat_tahun['kasus']
        ],
        'kepatuhan' => [
            'labels' => ['Tertib', 'Melanggar'],
            'data' => [max(0, $total_siswa - $stat_tahun['siswa']), $stat_tahun['siswa']],
            'colors' => ['#10b981', '#ef4444'],
            'total' => $total_siswa
        ]
    ]
];
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartDataPeriode = <?= json_encode($chart_config_data) ?>;
    const initializedTabs = {};
    const chartInstances = {};

    function renderDonut(canvasId, cfg) {
        const el = document.getElementById(canvasId);
        if (!el) return;

        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }

        const isZero = cfg.total === 0 || cfg.data.every(v => v === 0);
        const labels = isZero ? ['Belum ada kasus'] : cfg.labels;
        const data = isZero ? [1] : cfg.data;
        const bgColors = isZero ? ['#e2e8f0'] : cfg.colors;

        const ctx = el.getContext('2d');
        chartInstances[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: bgColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: isZero ? 0 : 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 8,
                            usePointStyle: true,
                            font: { size: 11, family: "'Segoe UI', Tahoma, sans-serif" }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (isZero) return ' 0 Kasus (0%)';
                                const val = ctx.raw || 0;
                                const total = cfg.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function initTabCharts(tabKey) {
        if (!chartDataPeriode[tabKey]) return;
        const c = chartDataPeriode[tabKey];
        renderDonut(`chart-jurusan-${tabKey}`, c.jurusan);
        renderDonut(`chart-tingkat-${tabKey}`, c.tingkat);
        renderDonut(`chart-kepatuhan-${tabKey}`, c.kepatuhan);
        initializedTabs[tabKey] = true;
    }

    // Inisialisasi tab aktif awal (Per Hari)
    initTabCharts('hari');

    // Tangani perpindahan tab Bootstrap
    const tabButtons = document.querySelectorAll('#tabPeriode button[data-bs-toggle="pill"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function (e) {
            const targetId = e.target.getAttribute('data-bs-target');
            const key = targetId.replace('#tab-', '');
            if (!initializedTabs[key]) {
                initTabCharts(key);
            } else {
                ['jurusan', 'tingkat', 'kepatuhan'].forEach(type => {
                    const cid = `chart-${type}-${key}`;
                    if (chartInstances[cid]) {
                        chartInstances[cid].resize();
                    }
                });
            }
        });
    });
});
</script>

<!-- ========================================================= -->
<!-- PANEL APRESIASI & PRESTASI SISWA (KESEIMBANGAN REWARD)    -->
<!-- ========================================================= -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                    <i class="bi bi-award-fill me-1"></i> Rekapitulasi Apresiasi Siswa
                </span>
                <span class="badge bg-light text-muted border"><?= $total_prestasi_kasus ?> Catatan Prestasi</span>
            </div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-trophy-fill text-warning me-2"></i>Panel Apresiasi &amp; Prestasi Siswa</h5>
            <small class="text-muted">Sorotan pencapaian positif, kejuaraan lomba, dan pembiasaan karakter terpuji siswa SMKS Tiara Nusa.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <?php if (!isOsis() && !isKepalaSekolah()): ?>
            <a href="input_prestasi.php" class="btn btn-success btn-sm fw-semibold shadow-xs">
                <i class="bi bi-plus-circle me-1"></i> Input Prestasi Baru
            </a>
            <?php endif; ?>
            <a href="laporan.php" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Rekap Nilai Siswa
            </a>
        </div>
    </div>

    <!-- 4 Metrik Ringkas Apresiasi -->
    <div class="card-body p-3 p-md-4 bg-light border-bottom">
        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="bg-white p-3 rounded-3 border h-100 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Prestasi Dicatat</span>
                        <div class="avatar-circle rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                            <i class="bi bi-trophy-fill" style="font-size:0.9rem;"></i>
                        </div>
                    </div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= $total_prestasi_kasus ?> <span class="fs-6 fw-normal text-muted">Kegiatan</span></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="bg-white p-3 rounded-3 border h-100 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Poin Apresiasi Diberikan</span>
                        <div class="avatar-circle rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                            <i class="bi bi-patch-check-fill" style="font-size:0.9rem;"></i>
                        </div>
                    </div>
                    <div class="fs-4 fw-bold text-success mb-0">+<?= $total_poin_prestasi ?> <span class="fs-6 fw-normal text-muted">Poin</span></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="bg-white p-3 rounded-3 border h-100 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Bintang Prestasi Teratas</span>
                        <div class="avatar-circle rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                            <i class="bi bi-star-fill" style="font-size:0.9rem;"></i>
                        </div>
                    </div>
                    <?php if ($bintang_prestasi): ?>
                        <div class="fw-bold text-dark text-truncate mb-0" style="font-size:0.92rem;" title="<?= htmlspecialchars($bintang_prestasi['nama']) ?>">
                            <?= htmlspecialchars($bintang_prestasi['nama']) ?>
                        </div>
                        <small class="text-muted"><?= htmlspecialchars($bintang_prestasi['nama_kelas']) ?> &bull; <strong class="text-success">+<?= (int)$bintang_prestasi['total_prestasi'] ?> Poin</strong></small>
                    <?php else: ?>
                        <div class="fw-semibold text-muted mb-0" style="font-size:0.9rem;">Belum ada catatan</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="bg-white p-3 rounded-3 border h-100 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Kelas Paling Berprestasi</span>
                        <div class="avatar-circle rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                            <i class="bi bi-buildings-fill" style="font-size:0.9rem;"></i>
                        </div>
                    </div>
                    <?php if ($top_kelas_prestasi && (int)$top_kelas_prestasi['total_poin'] > 0): ?>
                        <div class="fw-bold text-dark mb-0" style="font-size:0.95rem;">
                            <?= htmlspecialchars($top_kelas_prestasi['nama_kelas']) ?>
                        </div>
                        <small class="text-muted"><?= (int)$top_kelas_prestasi['jumlah_prestasi'] ?> Prestasi &bull; <strong class="text-success">+<?= (int)$top_kelas_prestasi['total_poin'] ?> Poin</strong></small>
                    <?php else: ?>
                        <div class="fw-semibold text-muted mb-0" style="font-size:0.9rem;">Belum ada data</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten Utama: Prestasi Terbaru & Distribusi Tingkat -->
    <div class="card-body p-3 p-md-4">
        <div class="row g-4">
            <!-- Kolom Kiri: Tabel Prestasi Terbaru -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-success me-1"></i> Prestasi &amp; Penghargaan Terbaru
                    </h6>
                    <small class="text-muted">5 catatan terbaru</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-secondary" style="font-size:0.8rem;">
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Prestasi &amp; Tingkat</th>
                                <th class="text-end">Poin</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:0.88rem;">
                            <?php if (mysqli_num_rows($prestasi_terbaru) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-trophy text-muted fs-3 d-block mb-1"></i>
                                        <span class="fw-semibold">Belum Ada Catatan Prestasi</span>
                                        <div class="small mt-1">Pencatatan prestasi dapat dilakukan melalui tombol "Input Prestasi Baru".</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php while ($p = mysqli_fetch_assoc($prestasi_terbaru)): 
                                $badgeTingkat = [
                                    'Sekolah'        => 'bg-secondary-subtle text-secondary',
                                    'Kecamatan'      => 'bg-info-subtle text-info-emphasis',
                                    'Kabupaten/Kota' => 'bg-primary-subtle text-primary',
                                    'Provinsi'       => 'bg-warning-subtle text-warning-emphasis',
                                    'Nasional'       => 'bg-danger-subtle text-danger',
                                    'Internasional'  => 'bg-success text-white'
                                ][$p['tingkat']] ?? 'bg-light text-dark';
                            ?>
                                <tr>
                                    <td class="text-muted small" style="width:90px;"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['nama_siswa']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['nama_kelas']) ?></small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width:240px;" title="<?= htmlspecialchars($p['nama_prestasi']) ?>">
                                            <?= htmlspecialchars($p['nama_prestasi']) ?>
                                        </div>
                                        <span class="badge <?= $badgeTingkat ?> py-0 px-1" style="font-size:0.7rem;">
                                            <?= htmlspecialchars($p['tingkat']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success" style="width:70px;">
                                        +<?= (int)$p['poin'] ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kolom Kanan: Distribusi Prestasi per Tingkat -->
            <div class="col-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-bar-chart-steps text-primary me-1"></i> Sebaran Tingkat Penghargaan
                    </h6>
                    <small class="text-muted">Berdasarkan cakupan</small>
                </div>
                <div class="bg-light p-3 rounded-3 border">
                    <?php 
                    $has_tingkat = false;
                    while ($dt = mysqli_fetch_assoc($distribusi_tingkat)): 
                        if ((int)$dt['jumlah'] > 0) $has_tingkat = true;
                        $pct = $total_prestasi_kasus > 0 ? round(((int)$dt['jumlah'] / $total_prestasi_kasus) * 100, 1) : 0;
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.83rem;">
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($dt['tingkat']) ?></span>
                                <span class="text-muted">
                                    <strong class="text-dark"><?= (int)$dt['jumlah'] ?></strong> kegiatan &bull; 
                                    <span class="text-success fw-bold">+<?= (int)$dt['total_poin'] ?> Poin</span>
                                </span>
                            </div>
                            <div class="progress" style="height:7px;">
                                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php if (!$has_tingkat): ?>
                        <div class="text-center py-3 text-muted small">
                            <i class="bi bi-info-circle me-1"></i> Sebaran tingkat lomba akan otomatis terisi saat data prestasi dicatat.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3 border-0 shadow-sm">
            <h6 class="fw-bold mb-3"><i class="bi bi-emoji-frown text-danger"></i> 5 Siswa Perlu Pembinaan (Skor Terendah)</h6>
            <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Jurusan</th>
                        <th>Kelas</th>
                        <th class="text-center">Skor</th>
                        <th>Predikat</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($perlu_pembinaan as $s): [$label,$warna] = hitungPredikat($s['skor']); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
                        <td><?= badgeJurusan($s['jurusan']) ?></td>
                        <td><span class="text-muted fw-semibold"><?= htmlspecialchars($s['nama_kelas']) ?></span></td>
                        <td class="text-center fw-bold text-danger"><?= $s['skor'] ?></td>
                        <td><span class="badge bg-<?= $warna ?>"><?= $label ?></span></td>
                    </tr>
                <?php endforeach; if (empty($perlu_pembinaan)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-shield-check text-success fs-3 d-block mb-1"></i>
                            <span class="fw-semibold text-dark">Kondisi Tertib &amp; Kondusif</span>
                            <div class="small text-muted mt-1">Belum ada siswa yang melakukan pelanggaran (seluruh siswa masih memiliki skor penuh 100).</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3 border-0 shadow-sm">
            <h6 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning"></i> 5 Siswa Terbaik (Skor Tertinggi)</h6>
            <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Jurusan</th>
                        <th>Kelas</th>
                        <th class="text-center">Skor</th>
                        <th>Predikat</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($siswa_terbaik as $s): [$label,$warna] = hitungPredikat($s['skor']); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
                        <td><?= badgeJurusan($s['jurusan']) ?></td>
                        <td><span class="text-muted fw-semibold"><?= htmlspecialchars($s['nama_kelas']) ?></span></td>
                        <td class="text-center fw-bold text-success"><?= $s['skor'] ?></td>
                        <td><span class="badge bg-<?= $warna ?>"><?= $label ?></span></td>
                    </tr>
                <?php endforeach; if (empty($siswa_terbaik)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-trophy text-warning fs-3 d-block mb-1"></i>
                            <span class="fw-semibold text-dark">Belum Ada Rekor Prestasi</span>
                            <div class="small text-muted mt-1">Daftar siswa berprestasi akan otomatis tampil setelah ada poin prestasi yang dicatatkan.</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 mt-3 border-0 shadow-sm">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history"></i> Aktivitas Terbaru</h6>
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Jenis</th>
                <th>Keterangan</th>
                <th class="text-center">Poin</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($aktivitas) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada aktivitas</td></tr>
        <?php endif; ?>
        <?php while ($a = mysqli_fetch_assoc($aktivitas)): ?>
            <tr>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                <td><strong><?= htmlspecialchars($a['nama']) ?></strong></td>
                <td>
                    <?php if ($a['jenis']=='pelanggaran'): ?>
                        <span class="badge bg-danger">Pelanggaran</span>
                    <?php else: ?>
                        <span class="badge bg-success">Prestasi</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($a['keterangan']) ?></td>
                <td class="text-center fw-bold <?= $a['jenis']=='pelanggaran' ? 'text-danger' : 'text-success' ?>">
                    <?= $a['jenis']=='pelanggaran' ? '-' : '+' ?><?= $a['poin'] ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; // Akhir if (!isOsis()) ?>

<?php include 'includes/footer.php'; ?>
