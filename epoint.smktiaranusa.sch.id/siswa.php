<?php
require 'config.php';
require 'includes/auth.php';

// Endpoint AJAX untuk daftar siswa per rombel
if (isset($_GET['ajax_list'])) {
    header('Content-Type: application/json');
    $k_id = (int)($_GET['kelas'] ?? 0);
    $qAjax = mysqli_query($koneksi, "SELECT id, nama, nis, nisn FROM siswa WHERE kelas_id=$k_id AND status='aktif' ORDER BY nama ASC");
    $list = [];
    while ($r = mysqli_fetch_assoc($qAjax)) {
        $list[] = $r;
    }
    echo json_encode($list);
    exit;
}

$judul_halaman = 'Data Siswa';

// 1. Ambil seluruh master kelas beserta jumlah siswa aktif di masing-masing rombel
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

// 2. Filter parameter
$cari = clean($koneksi, $_GET['cari'] ?? '');

// Menentukan kelas yang aktif dipilih
$filter_kelas = null;
if (isset($_GET['kelas'])) {
    if ($_GET['kelas'] === 'semua' || $_GET['kelas'] === '') {
        $filter_kelas = 'semua';
    } else {
        $filter_kelas = (int)$_GET['kelas'];
    }
} else {
    // Default saat pertama buka (jika tidak sedang pencarian global)
    if ($cari === '') {
        // Jika akun login adalah Wali Kelas, otomatis arahkan ke kelas perwaliannya
        if (!empty($_SESSION['kelas_id']) && isset($kelas_map[$_SESSION['kelas_id']])) {
            $filter_kelas = (int)$_SESSION['kelas_id'];
        } else {
            // Default ke kelas pertama agar tidak langsung memuat ratusan siswa sekaligus
            $filter_kelas = !empty($daftar_kelas) ? (int)$daftar_kelas[0]['id'] : 'semua';
        }
    } else {
        $filter_kelas = 'semua';
    }
}

// 3. Query Siswa berdasarkan kelas terpilih
$sql = "SELECT s.*, k.nama_kelas, k.jurusan, k.wali_kelas,
        (SELECT COALESCE(SUM(kp.poin),0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
        (SELECT COALESCE(SUM(kpr.poin),0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi
    FROM siswa s 
    JOIN kelas k ON s.kelas_id=k.id 
    WHERE s.status='aktif'";

if ($filter_kelas !== 'semua' && $filter_kelas > 0) {
    $sql .= " AND s.kelas_id = " . (int)$filter_kelas;
}
if ($cari !== '') {
    $sql .= " AND (s.nama LIKE '%$cari%' OR s.nis LIKE '%$cari%' OR s.nisn LIKE '%$cari%')";
}
$sql .= " ORDER BY k.jurusan, k.nama_kelas, s.nama";
$data_siswa_query = mysqli_query($koneksi, $sql);

// Simpan data siswa ke dalam array agar bisa dihitung dan dirender fleksibel (tabel / card)
$list_siswa = [];
$total_l = 0;
$total_p = 0;
while ($row = mysqli_fetch_assoc($data_siswa_query)) {
    if ($row['jenis_kelamin'] === 'L') $total_l++;
    if ($row['jenis_kelamin'] === 'P') $total_p++;
    $list_siswa[] = $row;
}
$jumlah_siswa_terpilih = count($list_siswa);

// 4. Export Data Siswa ke Excel (.xlsx) / CSV
if (isset($_GET['export']) && !isOsis()) {
    require_once 'includes/export_helper.php';
    $fmt = strtolower($_GET['export']);
    $nama_kelas_file = ($filter_kelas !== 'semua' && isset($kelas_map[$filter_kelas])) 
        ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $kelas_map[$filter_kelas]['nama_kelas']) 
        : 'Semua_Kelas';
    $filename = 'data_siswa_epoint_' . $nama_kelas_file . '_' . date('Ymd_His');
    $sheetTitle = 'Data Siswa ' . ($filter_kelas !== 'semua' && isset($kelas_map[$filter_kelas]) ? $kelas_map[$filter_kelas]['nama_kelas'] : '');
    $headers = ['NO', 'NISN', 'NIS', 'NAMA LENGKAP SISWA', 'JENIS KELAMIN', 'KELAS', 'JURUSAN', 'NAMA WALI KELAS', 'NAMA ORTU', 'NO HP ORTU / WA', 'TOTAL PELANGGARAN', 'TOTAL PRESTASI', 'SKOR AKHIR', 'PREDIKAT'];

    $rows = [];
    $no = 1;
    foreach ($list_siswa as $s) {
        $skor = SKOR_AWAL - (int)$s['total_pelanggaran'] + (int)$s['total_prestasi'];
        [$label] = hitungPredikat($skor);
        $rows[] = [
            $no++,
            ($s['nisn'] && $s['nisn'] !== '0000000000') ? $s['nisn'] : ($s['nis'] ?: '-'),
            ($s['nis'] && $s['nis'] !== '0000000000') ? $s['nis'] : '-',
            $s['nama'],
            $s['jenis_kelamin'],
            $s['nama_kelas'],
            $s['jurusan'],
            $s['wali_kelas'] ?? '-',
            $s['nama_ortu'] ?: '-',
            $s['no_hp_ortu'] ?: '-',
            (int)$s['total_pelanggaran'],
            (int)$s['total_prestasi'],
            $skor,
            $label
        ];
    }

    if ($fmt === 'csv') {
        downloadCsv($filename, $headers, $rows);
    } else {
        downloadXlsx($filename, $sheetTitle, $headers, $rows);
    }
}

// Info kelas aktif
$info_kelas_aktif = ($filter_kelas !== 'semua' && isset($kelas_map[$filter_kelas])) ? $kelas_map[$filter_kelas] : null;

include 'includes/header.php';
?>

<style>
/* Styling Navigasi & Kartu Siswa */
.kelas-pill-btn {
    border-radius: 10px;
    padding: 7px 14px;
    font-size: 0.86rem;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    background: #ffffff;
    color: #495057;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
.kelas-pill-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.kelas-pill-btn.active {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
    color: #ffffff !important;
    border-color: #1d4ed8;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}
.kelas-pill-btn.active .badge {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}
/* ==========================================================================
   ENHANCED STUDENT CARD DESIGN SYSTEM (RICH VIBRANT AESTHETICS)
   ========================================================================== */
.student-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 22px;
    background: #ffffff;
    transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 0 0 14px 0;
    position: relative;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}

/* Card Accent Bar (Top Color Stripe per Jurusan) */
.student-card-stripe {
    height: 5px;
    width: 100%;
    background: #cbd5e1;
}
.student-card.jurusan-mplb .student-card-stripe {
    background: linear-gradient(90deg, #0d9488 0%, #10b981 50%, #34d399 100%);
}
.student-card.jurusan-tjkt .student-card-stripe {
    background: linear-gradient(90deg, #4338ca 0%, #6366f1 50%, #818cf8 100%);
}

/* Card Ambient Gradient & Border */
.student-card.jurusan-mplb {
    background: linear-gradient(180deg, rgba(240, 253, 250, 0.75) 0%, #ffffff 42%);
    border-color: #ccfbf1;
}
.student-card.jurusan-tjkt {
    background: linear-gradient(180deg, rgba(238, 242, 255, 0.75) 0%, #ffffff 42%);
    border-color: #ede9fe;
}

.student-card:hover {
    transform: translateY(-6px);
}
.student-card.jurusan-mplb:hover {
    box-shadow: 0 16px 32px -8px rgba(13, 148, 136, 0.22), 0 4px 12px -2px rgba(13, 148, 136, 0.08);
    border-color: #5eead4;
}
.student-card.jurusan-tjkt:hover {
    box-shadow: 0 16px 32px -8px rgba(99, 102, 241, 0.22), 0 4px 12px -2px rgba(99, 102, 241, 0.08);
    border-color: #a5b4fc;
}

.student-card-content {
    padding: 16px 16px 0 16px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

/* Avatar with Gender Differentiation & Glow */
.avatar-wrapper {
    position: relative;
    width: 64px;
    height: 64px;
    margin: 0 auto 12px auto;
}
.avatar-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    font-weight: 800;
    transition: transform 0.25s ease;
}
.student-card:hover .avatar-circle {
    transform: scale(1.08);
}
.avatar-male {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #38bdf8 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px -3px rgba(37, 99, 235, 0.42);
}
.avatar-female {
    background: linear-gradient(135deg, #be185d 0%, #ec4899 55%, #fb7185 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px -3px rgba(236, 72, 153, 0.42);
}
.avatar-gender-pill {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}
.gender-male-badge {
    background: #0284c7;
    color: #ffffff;
}
.gender-female-badge {
    background: #e11d48;
    color: #ffffff;
}

/* Student Typography & Badges */
.student-name {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: -0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 6px;
}
.student-class-box {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
}
.pill-kelas-mplb {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.pill-kelas-tjkt {
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.pill-nisn {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
    font-size: 0.72rem;
    font-family: monospace;
    padding: 3px 8px;
    border-radius: 30px;
}

/* Score Pill Capsule */
.score-capsule {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.2px;
    margin-bottom: 12px;
}
.score-capsule-green {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    color: #065f46;
    border: 1px solid #a7f3d0;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.12);
}
.score-capsule-blue {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #1e40af;
    border: 1px solid #bfdbfe;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
}
.score-capsule-yellow {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #92400e;
    border: 1px solid #fde68a;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.12);
}
.score-capsule-red {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #991b1b;
    border: 1px solid #fecaca;
    box-shadow: 0 2px 6px rgba(220, 38, 38, 0.12);
}

/* Card Footer / Points Strip */
.student-card-footer {
    margin: auto 12px 0 12px;
    padding: 7px 12px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-around;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
}
.point-stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.82rem;
    font-weight: 800;
}
.point-plus {
    color: #059669;
}
.point-minus {
    color: #dc2626;
}
.point-sep {
    width: 1px;
    height: 14px;
    background: #e2e8f0;
}
.card-action-menu {
    position: absolute;
    top: 10px;
    right: 12px;
    opacity: 0.35;
    transition: opacity 0.2s;
    z-index: 5;
}
.student-card:hover .card-action-menu {
    opacity: 1;
}
.view-toggle-btn {
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 600;
}
</style>

<!-- CONTROL BAR: DROPDOWN KELAS & SEARCH INPUT (SESUAI DESAIN MINIMALIS) -->
<div class="card p-3 border-0 shadow-sm mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <!-- Sisi Kiri: Dropdown Kelas & Pencarian Murid (Persis Screenshot) -->
        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 flex-grow-1" style="max-width: 680px;">
            <!-- Dropdown Pilihan Kelas -->
            <div class="w-100 w-sm-auto" style="min-width: 170px;">
                <select id="selectKelas" class="form-select fw-semibold" 
                        style="border-radius: 12px; height: 42px; font-size: 0.92rem; border-color: #3b82f6; color: #1e293b; background-color: #ffffff;"
                        onchange="pindahKelas(this.value)">
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
                <i class="bi bi-search position-absolute text-muted" style="top: 11px; left: 14px; font-size: 0.95rem;"></i>
                <input type="text" id="liveSearchInput" class="form-control" 
                       placeholder="Cari nama murid..." 
                       value="<?= htmlspecialchars($cari) ?>"
                       style="border-radius: 12px; height: 42px; padding-left: 38px; padding-right: 75px; font-size: 0.92rem; border-color: #cbd5e1;"
                       oninput="filterSiswaInstan(this.value)">
                <span class="position-absolute text-muted small fw-semibold" id="labelCounterHeader" 
                      style="top: 11px; right: 14px; font-size: 0.8rem; pointer-events: none;">
                    <?= $jumlah_siswa_terpilih ?>/<?= $total_siswa_sekolah ?>
                </span>
            </div>
        </div>

        <!-- Sisi Kanan: Tombol Aksi & Export -->
        <div class="d-flex gap-2 flex-wrap align-items-center">

            <?php if (!isOsis()): ?>
            <a href="alumni.php" class="btn btn-outline-secondary btn-sm fw-semibold" title="Buka Data Siswa yang Sudah Lulus">
                <i class="bi bi-mortarboard me-1 text-primary"></i> Data Alumni
            </a>
            <?php endif; ?>

            <?php if (isGuruBK()): ?>
            <a href="kelulusan_kenaikan.php" class="btn btn-outline-warning btn-sm fw-semibold text-dark" title="Kelola Kenaikan Kelas & Kelulusan">
                <i class="bi bi-arrow-repeat me-1 text-warning"></i> Kenaikan &amp; Kelulusan
            </a>
            <?php endif; ?>

            <?php if (!isOsis()): ?>
            <div class="dropdown">
                <button class="btn btn-outline-success btn-sm dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-1"></i> Export Data
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="siswa.php?<?= http_build_query(array_merge($_GET, ['export' => 'xlsx'])) ?>">
                            <i class="bi bi-file-earmark-spreadsheet text-success fs-6"></i> Export Excel (.xlsx)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="siswa.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">
                            <i class="bi bi-file-earmark-text text-primary fs-6"></i> Export CSV (.csv)
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <button class="btn btn-success btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="bi bi-file-earmark-excel me-1"></i> Import
            </button>
            <?php endif; ?>

            <?php if (!isOsis() && !isKepalaSekolah()): ?>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSiswa" onclick="modeTambah(<?= ($filter_kelas !== 'semua' && $filter_kelas > 0) ? $filter_kelas : 0 ?>)">
                <i class="bi bi-plus-lg me-1"></i> Tambah Siswa
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Bar Ringkas Kelas Terpilih (Subtle) -->
    <?php if ($info_kelas_aktif): ?>
        <div class="mt-2 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center text-muted small" style="font-size: 0.82rem;">
            <div>
                <i class="bi bi-mortarboard text-primary me-1"></i> Kelas: <strong class="text-dark"><?= htmlspecialchars($info_kelas_aktif['nama_kelas']) ?></strong>
                <span class="mx-2">&bull;</span>
                <i class="bi bi-person-badge text-secondary me-1"></i> Wali Kelas: <strong class="text-dark"><?= htmlspecialchars($info_kelas_aktif['wali_kelas'] ?: '-') ?></strong>
            </div>
            <div>
                <span class="badge bg-light text-secondary border fw-normal">
                    Total: <?= $jumlah_siswa_terpilih ?> Siswa (<?= $total_l ?> Laki-laki / <?= $total_p ?> Perempuan)
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- 3. TAMPILAN SISWA (MODE TABEL / MODE KARTU) -->
<?php if ($jumlah_siswa_terpilih === 0): ?>
    <div class="card p-5 text-center border-0 shadow-sm">
        <div class="mb-3 text-muted">
            <i class="bi bi-people" style="font-size:3rem;"></i>
        </div>
        <h6 class="fw-bold text-dark">Belum Ada Siswa di Kelas Ini</h6>
        <p class="text-muted small mb-3">Tidak ditemukan data siswa aktif untuk pilihan kelas atau kata kunci pencarian ini.</p>
        <?php if (!isOsis() && !isKepalaSekolah()): ?>
            <div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSiswa" onclick="modeTambah(<?= ($filter_kelas !== 'semua' && $filter_kelas > 0) ? $filter_kelas : 0 ?>)">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Siswa ke Kelas Ini
                </button>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ==================== MODE KARTU (GRID CARD - PERSIS SCREENSHOT) ==================== -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-3 g-3" id="siswaCardGrid">
        <?php foreach ($list_siswa as $s): 
            $initial = mb_strtoupper(mb_substr(trim($s['nama']), 0, 1, 'UTF-8'), 'UTF-8');
            $searchData = strtolower($s['nama'] . ' ' . $s['nis'] . ' ' . ($s['nisn'] ?? ''));
            $skor = SKOR_AWAL - (int)$s['total_pelanggaran'] + (int)$s['total_prestasi'];
            [$label, $warna] = hitungPredikat($skor);
            $targetUrl = isOsis() ? "input_pelanggaran.php?siswa_id={$s['id']}" : "riwayat_siswa.php?id={$s['id']}";
            $targetTitle = isOsis() ? "Klik untuk langsung catat pelanggaran siswa ini" : "Klik untuk melihat riwayat lengkap siswa";

            $isMplb = ($s['jurusan'] ?? '') === 'MPLB';
            $jurusanClass = $isMplb ? 'jurusan-mplb' : 'jurusan-tjkt';
            $isMale = ($s['jenis_kelamin'] ?? 'L') === 'L';
            $avatarClass = $isMale ? 'avatar-male' : 'avatar-female';
            $genderBadgeClass = $isMale ? 'gender-male-badge' : 'gender-female-badge';
            $genderIcon = $isMale ? 'bi-gender-male' : 'bi-gender-female';
            $genderTitle = $isMale ? 'Laki-laki' : 'Perempuan';

            // Skor capsule style
            $scoreCapsuleClass = 'score-capsule-green';
            $scoreIcon = 'bi-stars text-warning';
            if ($skor < 50) {
                $scoreCapsuleClass = 'score-capsule-red';
                $scoreIcon = 'bi-exclamation-triangle-fill text-danger';
            } elseif ($skor < 75) {
                $scoreCapsuleClass = 'score-capsule-yellow';
                $scoreIcon = 'bi-exclamation-circle-fill text-warning';
            } elseif ($skor < 90) {
                $scoreCapsuleClass = 'score-capsule-blue';
                $scoreIcon = 'bi-check-circle-fill text-primary';
            }
        ?>
            <div class="col item-siswa" data-search="<?= htmlspecialchars($searchData) ?>">
                <div class="student-card <?= $jurusanClass ?>" onclick="window.location.href='<?= $targetUrl ?>'" title="<?= $targetTitle ?>">
                    <!-- Top Gradient Accent Stripe -->
                    <div class="student-card-stripe"></div>

                    <!-- Menu Aksi Cepat di Pojok Kanan Atas (Tampil saat hover) -->
                    <?php if (!isKepalaSekolah() && !isOsis()): ?>
                        <div class="dropdown card-action-menu" onclick="event.stopPropagation();">
                            <button class="btn btn-sm btn-light p-0 rounded-circle border-0 d-flex align-items-center justify-content-center" 
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false" 
                                    style="width:28px; height:28px; background:rgba(255,255,255,0.85); box-shadow:0 1px 3px rgba(0,0,0,0.1);" 
                                    title="Opsi Siswa">
                                <i class="bi bi-three-dots-vertical text-secondary"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="font-size:0.85rem; border-radius:12px;">
                                <li>
                                    <a class="dropdown-item py-1" href="riwayat_siswa.php?id=<?= $s['id'] ?>">
                                        <i class="bi bi-clock-history text-info me-2"></i> Riwayat &amp; Poin
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item py-1" 
                                            onclick='modeEdit(<?= json_encode($s) ?>)' 
                                            data-bs-toggle="modal" data-bs-target="#modalSiswa">
                                        <i class="bi bi-pencil text-primary me-2"></i> Edit Data
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item py-1 text-danger" 
                                        href="siswa_proses.php?aksi=hapus&id=<?= $s['id'] ?>&kelas_id=<?= $s['kelas_id'] ?>" 
                                        onclick="return confirm('Yakin hapus siswa <?= addslashes(htmlspecialchars($s['nama'])) ?> beserta seluruh riwayatnya?')">
                                        <i class="bi bi-trash text-danger me-2"></i> Hapus Siswa
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="student-card-content">
                        <!-- Avatar Berwarna Bergradasi + Indikator Gender -->
                        <div class="avatar-wrapper">
                            <div class="avatar-circle <?= $avatarClass ?>">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                            <span class="avatar-gender-pill <?= $genderBadgeClass ?>" title="<?= $genderTitle ?>">
                                <i class="bi <?= $genderIcon ?>"></i>
                            </span>
                        </div>

                        <!-- Nama Lengkap Siswa -->
                        <div class="text-center px-1">
                            <div class="student-name" title="<?= htmlspecialchars($s['nama']) ?>">
                                <?= htmlspecialchars($s['nama']) ?>
                            </div>

                            <!-- Badge Rombel & NISN Berwarna -->
                            <div class="student-class-box">
                                <?php if ($isMplb): ?>
                                    <span class="pill-kelas-mplb">
                                        <i class="bi bi-briefcase-fill" style="font-size:0.7rem;"></i> <?= htmlspecialchars($s['nama_kelas']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="pill-kelas-tjkt">
                                        <i class="bi bi-hdd-network-fill" style="font-size:0.7rem;"></i> <?= htmlspecialchars($s['nama_kelas']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php $identitasNisn = ($s['nisn'] && $s['nisn'] !== '0000000000') ? $s['nisn'] : (($s['nis'] && $s['nis'] !== '0000000000') ? $s['nis'] : ''); ?>
                                <?php if ($identitasNisn): ?>
                                    <span class="pill-nisn">
                                        <?= htmlspecialchars($identitasNisn) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Skor Karakter & Predikat -->
                            <div>
                                <div class="score-capsule <?= $scoreCapsuleClass ?>">
                                    <i class="bi <?= $scoreIcon ?>"></i> Skor <?= $skor ?> &bull; <?= $label ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Poin Apresiasi (+) & Pelanggaran (-) -->
                    <div class="student-card-footer">
                        <div class="point-stat-item point-plus" title="Total Apresiasi">
                            <i class="bi bi-trophy-fill text-success" style="font-size:0.8rem;"></i> +<?= (int)$s['total_prestasi'] ?>
                        </div>
                        <div class="point-sep"></div>
                        <div class="point-stat-item point-minus" title="Total Pelanggaran">
                            <i class="bi bi-shield-exclamation text-danger" style="font-size:0.8rem;"></i> -<?= (int)$s['total_pelanggaran'] ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ==================== MODAL TAMBAH / EDIT SISWA ==================== -->
<?php if (!isOsis() && !isKepalaSekolah()): ?>
<div class="modal fade" id="modalSiswa" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="siswa_proses.php">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="judulModalSiswa">Tambah Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="aksi" id="f_aksi" value="tambah">
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label fw-semibold">NISN <span class="text-danger">*</span></label>
                <input type="text" name="nisn" id="f_nisn" class="form-control" placeholder="NISN Nasional (10 digit)" required>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold">NIS <span class="text-muted fw-normal small">(Default: 000000)</span></label>
                <input type="text" name="nis" id="f_nis" class="form-control" placeholder="000000" value="000000">
            </div>
        </div>
        <div class="mb-2 mt-2">
            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="f_nama" class="form-control" placeholder="Nama lengkap siswa" required>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label fw-semibold">Kelas &amp; Jurusan <span class="text-danger">*</span></label>
                <select name="kelas_id" id="f_kelas_id" class="form-select" required>
                    <optgroup label="Jurusan MPLB">
                        <?php foreach ($mplb_kelas as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Jurusan TJKT">
                        <?php foreach ($tjkt_kelas as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold">Jenis Kelamin</label>
                <select name="jenis_kelamin" id="f_jk" class="form-select">
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
        </div>
        <div class="mb-2 mt-2">
            <label class="form-label fw-semibold">Nama Orang Tua</label>
            <input type="text" name="nama_ortu" id="f_ortu" class="form-control" placeholder="Nama ayah / ibu">
        </div>
        <div class="mb-2">
            <label class="form-label fw-semibold">No. HP Orang Tua / WhatsApp</label>
            <input type="text" name="no_hp_ortu" id="f_hp" class="form-control" placeholder="Contoh: 08123456789">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check-lg me-1"></i> Simpan Data</button>
      </div>
    </form>
  </div>
</div>

<!-- ==================== MODAL IMPORT SISWA ==================== -->
<div class="modal fade" id="modalImport" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="siswa_import.php" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel text-success me-1"></i> Import Siswa dari Excel / CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          Unggah file Excel (<strong>.xlsx</strong>) atau <strong>.csv</strong> yang memuat daftar siswa. Sistem akan otomatis menyinkronkan siswa, kelas, jurusan, serta nama wali kelas.
        </p>
        <div class="mb-3">
          <label class="form-label fw-semibold">Pilih File (.xlsx / .csv)</label>
          <input type="file" name="file_excel" class="form-control" accept=".xlsx, .csv" required>
        </div>
        <div class="p-3 bg-light rounded border">
          <div class="fw-bold small mb-1"><i class="bi bi-file-earmark-arrow-down text-success me-1"></i> Unduh Format Template Resmi:</div>
          <p class="text-muted small mb-2" style="font-size:0.78rem;">
            Pilih format template yang Anda inginkan. Anda cukup mengisi nama, kelas, dan data siswa pada file tersebut lalu unggah kembali ke sini:
          </p>
          <div class="d-flex gap-2 flex-wrap">
            <a href="siswa_import.php?download_template=xlsx" class="btn btn-success btn-sm fw-semibold shadow-xs">
              <i class="bi bi-file-earmark-excel-fill me-1"></i> Unduh Template Excel (.xlsx)
            </a>
            <a href="siswa_import.php?download_template=csv" class="btn btn-outline-secondary btn-sm fw-semibold">
              <i class="bi bi-filetype-csv me-1"></i> Unduh Format CSV (.csv)
            </a>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i> Mulai Import Data</button>
      </div>
    </form>
  </div>
</div>

<script>
function modeTambah(defaultKelasId = 0) {
    document.getElementById('judulModalSiswa').innerText = 'Tambah Siswa';
    document.getElementById('f_aksi').value = 'tambah';
    document.getElementById('f_id').value = '';
    ['f_nisn','f_nama','f_ortu','f_hp'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f_nis').value = '000000';
    
    const selKelas = document.getElementById('f_kelas_id');
    if (selKelas) {
        if (defaultKelasId > 0) {
            selKelas.value = defaultKelasId;
        } else {
            selKelas.selectedIndex = 0;
        }
    }
    const selJk = document.getElementById('f_jk');
    if (selJk) selJk.value = 'L';
}

function modeEdit(s) {
    document.getElementById('judulModalSiswa').innerText = 'Edit Siswa';
    document.getElementById('f_aksi').value = 'edit';
    document.getElementById('f_id').value = s.id;
    document.getElementById('f_nisn').value = (s.nisn && s.nisn !== '0000000000' && s.nisn !== '000000') ? s.nisn : '';
    document.getElementById('f_nis').value = s.nis || '000000';
    document.getElementById('f_nama').value = s.nama;
    document.getElementById('f_kelas_id').value = s.kelas_id;
    document.getElementById('f_jk').value = s.jenis_kelamin;
    document.getElementById('f_ortu').value = s.nama_ortu ?? '';
    document.getElementById('f_hp').value = s.no_hp_ortu ?? '';
}
</script>
<?php endif; ?>

<script>
// Pindah kelas saat dropdown diubah
function pindahKelas(kelasId) {
    const cariInput = document.getElementById('liveSearchInput');
    const cariVal = cariInput ? encodeURIComponent(cariInput.value.trim()) : '';
    let url = 'siswa.php?kelas=' + encodeURIComponent(kelasId);
    if (cariVal) {
        url += '&cari=' + cariVal;
    }
    window.location.href = url;
}

// Pencarian Instan (Real-time Instant Search)
function filterSiswaInstan(query) {
    const q = query.toLowerCase().trim();
    const items = document.querySelectorAll('.item-siswa');
    let visibleCount = 0;
    const totalCount = items.length;

    items.forEach(el => {
        const searchKey = el.getAttribute('data-search') || '';
        if (q === '' || searchKey.includes(q)) {
            el.style.display = '';
            visibleCount++;
        } else {
            el.style.display = 'none';
        }
    });

    const counterEl = document.getElementById('labelCounterHeader');
    if (counterEl) {
        counterEl.innerText = visibleCount + '/' + <?= $total_siswa_sekolah ?>;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
