<?php
require 'config.php';
require 'includes/auth.php';
if (isOsis()) {
    redirect('index.php', 'Akses ditolak: Riwayat akumulasi poin siswa hanya dapat diakses oleh Pendidik dan Wali Kelas.', 'danger');
}
$judul_halaman = 'Riwayat Rekam Jejak Siswa';

// Data kelas untuk dropdown filter
$kelas_query = mysqli_query($koneksi, "SELECT id, nama_kelas, jurusan FROM kelas ORDER BY jurusan, nama_kelas");
$mplb_kelas = [];
$tjkt_kelas = [];
$semua_kelas = [];
while ($k = mysqli_fetch_assoc($kelas_query)) {
    $semua_kelas[] = $k;
    if ($k['jurusan'] === 'MPLB') {
        $mplb_kelas[] = $k;
    } else {
        $tjkt_kelas[] = $k;
    }
}

// Data seluruh siswa untuk live search instan
$siswa_list_query = mysqli_query($koneksi, "SELECT s.id, s.nama, s.nis, s.nisn, s.kelas_id, s.jenis_kelamin, s.nama_ortu, s.no_hp_ortu, k.nama_kelas, k.jurusan FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' ORDER BY k.jurusan, k.nama_kelas, s.nama");
$daftar_siswa = [];
while ($s = mysqli_fetch_assoc($siswa_list_query)) {
    $daftar_siswa[] = $s;
}
$total_siswa = count($daftar_siswa);

$siswa_id = (int)($_GET['id'] ?? 0);
$siswa = null; $pelanggaran = null; $prestasi = null; $skor = SKOR_AWAL;
$pelanggaran_list = [];
$prestasi_list = [];
$total_pel = 0;
$total_pres = 0;

if ($siswa_id) {
    $qS = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas, k.jurusan, k.wali_kelas FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.id=$siswa_id");
    $siswa = mysqli_fetch_assoc($qS);
    if ($siswa) {
        $qPel = mysqli_query($koneksi, "SELECT rp.*, kp.nama_pelanggaran, kp.poin, kp.tingkat, kp.kategori, u.nama_lengkap AS nama_petugas
            FROM riwayat_pelanggaran rp 
            JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id
            LEFT JOIN users u ON rp.petugas_id=u.id
            WHERE rp.siswa_id=$siswa_id ORDER BY rp.tanggal DESC, rp.id DESC");
        while ($r = mysqli_fetch_assoc($qPel)) {
            $pelanggaran_list[] = $r;
        }

        $qPres = mysqli_query($koneksi, "SELECT rp.*, kp.nama_prestasi, kp.poin, kp.tingkat, u.nama_lengkap AS nama_petugas
            FROM riwayat_prestasi rp 
            JOIN kategori_prestasi kp ON rp.kategori_id=kp.id
            LEFT JOIN users u ON rp.petugas_id=u.id
            WHERE rp.siswa_id=$siswa_id ORDER BY rp.tanggal DESC, rp.id DESC");
        while ($r = mysqli_fetch_assoc($qPres)) {
            $prestasi_list[] = $r;
        }

        $total_pel = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(kp.poin),0) t FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=$siswa_id"))['t'];
        $total_pres = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(kp.poin),0) t FROM riwayat_prestasi rp JOIN kategori_prestasi kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=$siswa_id"))['t'];
        $skor = SKOR_AWAL - (int)$total_pel + (int)$total_pres;
    }
}

// Jika belum ada siswa terpilih, ambil rekam aktivitas terbaru untuk akses cepat (Quick Shortcuts)
$shortcut_terbaru = [];
if (!$siswa) {
    $qRecent = mysqli_query($koneksi, "
        (SELECT 'pelanggaran' AS tipe, rp.id, rp.tanggal, rp.keterangan, kp.nama_pelanggaran AS nama_kasus, kp.poin, s.id AS s_id, s.nama AS s_nama, k.nama_kelas, k.jurusan
         FROM riwayat_pelanggaran rp
         JOIN kategori_pelanggaran kp ON rp.kategori_id = kp.id
         JOIN siswa s ON rp.siswa_id = s.id
         JOIN kelas k ON s.kelas_id = k.id)
        UNION ALL
        (SELECT 'prestasi' AS tipe, rpr.id, rpr.tanggal, rpr.keterangan, kpr.nama_prestasi AS nama_kasus, kpr.poin, s.id AS s_id, s.nama AS s_nama, k.nama_kelas, k.jurusan
         FROM riwayat_prestasi rpr
         JOIN kategori_prestasi kpr ON rpr.kategori_id = kpr.id
         JOIN siswa s ON rpr.siswa_id = s.id
         JOIN kelas k ON s.kelas_id = k.id)
        ORDER BY tanggal DESC, id DESC
        LIMIT 6
    ");
    if ($qRecent) {
        while ($rc = mysqli_fetch_assoc($qRecent)) {
            $shortcut_terbaru[] = $rc;
        }
    }
}

include 'includes/header.php';
?>

<!-- Google Fonts: Plus Jakarta Sans untuk tipografi profesional -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

<style>
/* ==========================================================================
   DESAIN E-POINT ULTRA-MODERN (BESPOKE & HUMAN-CRAFTED)
   ========================================================================== */
.riwayat-wrapper {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
}

/* Control Bar Floating Elevated */
.search-control-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid rgba(226, 232, 240, 0.85);
    box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05), 0 2px 6px rgba(15, 23, 42, 0.02);
    padding: 16px 20px;
    transition: all 0.25s ease;
}
.search-control-card:focus-within {
    border-color: #93c5fd;
    box-shadow: 0 16px 36px -10px rgba(37, 99, 235, 0.12), 0 0 0 1px #bfdbfe;
}

.filter-kelas-col {
    width: 100%;
}
@media (min-width: 576px) {
    .filter-kelas-col {
        width: 220px;
        flex: 0 0 220px;
    }
}

.select-kelas-modern {
    border-radius: 14px !important;
    height: 44px;
    font-size: 0.9rem;
    font-weight: 600;
    border: 1.5px solid #3b82f6 !important;
    color: #1e293b;
    background-color: #ffffff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    cursor: pointer;
    transition: all 0.2s ease;
}
.select-kelas-modern:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15) !important;
}

.input-cari-modern {
    border-radius: 14px !important;
    height: 44px;
    padding-left: 42px !important;
    padding-right: 85px !important;
    font-size: 0.92rem;
    font-weight: 500;
    border: 1.5px solid #cbd5e1 !important;
    background-color: #ffffff;
    color: #0f172a;
    transition: all 0.2s ease;
}
.input-cari-modern:focus {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15) !important;
}

.badge-counter-pill {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    padding: 3px 10px;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    pointer-events: none;
}

/* Dropdown Popover List */
.dropdown-siswa-popover {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.04);
    background: #ffffff;
    max-height: 320px;
    overflow-y: auto;
}
.dropdown-siswa-popover::-webkit-scrollbar {
    width: 6px;
}
.dropdown-siswa-popover::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}

.item-siswa-row {
    padding: 10px 14px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    margin-bottom: 3px;
    transition: background-color 0.15s ease, transform 0.1s ease;
}
.item-siswa-row:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}
.item-siswa-row.active-selection {
    background-color: #eff6ff;
    border: 1px solid #bfdbfe;
}

/* Avatar Inisial Bulat Elegan */
.avatar-student {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    color: #ffffff;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Hero Showcase Profil Siswa */
.hero-dossier-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #042f2e 100%);
    border-radius: 24px;
    color: #ffffff;
    padding: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.35);
    margin-bottom: 24px;
}
.hero-dossier-card::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.4) 0%, transparent 70%);
    pointer-events: none;
}
.hero-dossier-card::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 10%;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
    pointer-events: none;
}

.avatar-hero-student {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d9488, #0284c7);
    border: 3.5px solid rgba(255,255,255,0.25);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; font-weight: 800; color: #fff;
    flex-shrink: 0;
}

.student-meta-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 30px;
    padding: 4px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    color: #e2e8f0;
}

/* Stat Metric Grid Card */
.stat-metric-card {
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    padding: 16px 20px;
    backdrop-filter: blur(12px);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.stat-metric-card .stat-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 6px;
}
.stat-metric-card .stat-value {
    font-size: 1.9rem;
    font-weight: 800;
    line-height: 1.1;
}

/* Timeline Cards */
.timeline-stream-card {
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
    height: 100%;
}
.timeline-entry-item {
    position: relative;
    padding-left: 28px;
    margin-bottom: 22px;
}
.timeline-entry-item:last-child {
    margin-bottom: 0;
}
.timeline-entry-item::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 8px;
    bottom: -22px;
    width: 2px;
    background: #e2e8f0;
}
.timeline-entry-item:last-child::before {
    display: none;
}
.timeline-bullet {
    position: absolute;
    left: 0;
    top: 4px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px #cbd5e1;
    z-index: 1;
}
.timeline-entry-item.is-pelanggaran .timeline-bullet {
    background: #ef4444;
    box-shadow: 0 0 0 2px #fecaca;
}
.timeline-entry-item.is-prestasi .timeline-bullet {
    background: #10b981;
    box-shadow: 0 0 0 2px #a7f3d0;
}

.timeline-box-inner {
    border: 1px solid #f1f5f9;
    background: #fafbfd;
    border-radius: 14px;
    padding: 14px 16px;
    transition: all 0.2s ease;
}
.timeline-box-inner:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 6px 20px rgba(0,0,0,0.04);
}


/* Print CSS: Lembar Rekam Jejak Resmi SMKS Tiara Nusa */
@media print {
    body { background: #ffffff !important; font-size: 11pt !important; }
    #sidebar, .topbar, .search-control-card, .btn-print-hide, .btn-ganti-hide, .footer-bar, .alert {
        display: none !important;
    }
    #content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .hero-dossier-card {
        background: #ffffff !important;
        color: #000000 !important;
        border: 2px solid #000000 !important;
        box-shadow: none !important;
        padding: 16px !important;
        margin-bottom: 20px !important;
    }
    .hero-dossier-card * { color: #000000 !important; }
    .stat-metric-card {
        border: 1px solid #000000 !important;
        background: transparent !important;
    }
    .print-only-header {
        display: block !important;
        text-align: center;
        border-bottom: 3px double #000;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .print-signature-box {
        display: flex !important;
        justify-content: space-between;
        margin-top: 50px;
        text-align: center;
    }
}
.print-only-header, .print-signature-box {
    display: none;
}
</style>

<div class="riwayat-wrapper">

    <!-- KOP SURAT RESMI (HANYA MUNCUL SAAT DI-PRINT / CETAK LAPORAN) -->
    <div class="print-only-header">
        <h4 style="margin: 0; font-weight: 800; font-size: 1.3rem; letter-spacing: 0.5px;">SMKS TIARA NUSA</h4>
        <div style="font-size: 0.85rem; color: #333; margin-top: 3px;">
            LEMBAR REKAM JEJAK AKUMULASI POIN KEDISIPLINAN &amp; PRESTASI SISWA
        </div>
        <div style="font-size: 0.78rem; color: #666; margin-top: 2px;">
            Tahun Ajaran <?= date('Y') ?>/<?= date('Y') + 1 ?> &bull; Dicetak pada: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- BARIS KONTROL ATAS: DROPDOWN KELAS & SEARCH INPUT (PERSIS SCREENSHOT) -->
    <!-- ========================================================================= -->
    <div class="search-control-card mb-4 btn-print-hide">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 28px; height: 28px; background: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-person-bounding-box" style="font-size: 0.95rem;"></i>
                </span>
                <span class="fw-bold text-dark" style="font-size: 0.95rem;">Pilih Siswa</span>
            </div>
            <div class="text-muted small fw-medium" style="font-size: 0.8rem;">
                <i class="bi bi-info-circle me-1 text-primary"></i> Pilih kelas &amp; cari nama murid untuk melihat rekam jejak
            </div>
        </div>

        <div class="position-relative">
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                <!-- Dropdown Kelas (Rounded Blue Outline) -->
                <div class="filter-kelas-col">
                    <select id="filterKelasSiswa" class="form-select select-kelas-modern" onchange="onFilterKelasChange(this.value)">
                        <option value="semua" <?= (!$siswa || $siswa['kelas_id'] === 'semua') ? 'selected' : '' ?>>Semua Kelas</option>
                        <optgroup label="Jurusan MPLB">
                            <?php foreach ($mplb_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($siswa && (int)$siswa['kelas_id'] === (int)$k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Jurusan TJKT">
                            <?php foreach ($tjkt_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($siswa && (int)$siswa['kelas_id'] === (int)$k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Input Cari Nama Murid dengan counter (contoh 424/424) -->
                <div class="position-relative flex-grow-1">
                    <i class="bi bi-search position-absolute text-muted" style="top: 13px; left: 15px; font-size: 0.92rem;"></i>
                    <input type="text" id="inputCariSiswa" class="form-control input-cari-modern" 
                           placeholder="Cari nama murid atau NISN..." 
                           autocomplete="off"
                           value="<?= $siswa ? htmlspecialchars($siswa['nama']) : '' ?>"
                           style="border-radius: 14px; height: 44px; padding-left: 42px; padding-right: 85px;"
                           oninput="onCariSiswaInput(this.value)"
                           onfocus="bukaDropdownSiswa()"
                           onclick="bukaDropdownSiswa()">
                    <span class="position-absolute badge-counter-pill" id="counterMuridText" 
                          style="top: 10px; right: 12px;">
                        <?= $total_siswa ?>/<?= $total_siswa ?>
                    </span>

                    <!-- Popover Hasil Pencarian Siswa (Tepat di bawah input) -->
                    <div id="dropdownListSiswa" class="dropdown-siswa-popover position-absolute start-0 end-0 bg-white border p-2 shadow-lg d-none" 
                         style="top: 48px; z-index: 1060;">
                        <div id="listSiswaContainer">
                            <!-- Diisi secara dinamis oleh JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Tombol Ganti Murid (Jika ada siswa aktif) -->
                <?php if ($siswa): ?>
                <a href="riwayat_siswa.php" class="btn btn-light btn-sm px-3 fw-bold d-flex align-items-center justify-content-center gap-1 flex-shrink-0 text-secondary border shadow-xs btn-ganti-hide" style="height: 44px; border-radius: 14px;" title="Reset dan pilih murid lain">
                    <i class="bi bi-arrow-counterclockwise fs-6"></i>
                    <span>Ganti</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAMPILAN PROFIL SISWA AKTIF & REKAM JEJAK LENGKAP -->
    <!-- ========================================================================= -->
    <?php if ($siswa): 
        [$label_skor, $warna_skor] = hitungPredikat($skor);
        $initial_nama = mb_strtoupper(mb_substr(trim($siswa['nama']), 0, 1, 'UTF-8'), 'UTF-8');
        $jumlah_pelanggaran = count($pelanggaran_list);
        $jumlah_prestasi = count($prestasi_list);
    ?>

    <!-- HERO PROFILE CARD -->
    <div class="hero-dossier-card">
        <div class="row align-items-center g-4">
            <!-- Profil Identitas Kiri -->
            <div class="col-lg-7 col-xl-8">
                <div class="d-flex align-items-start gap-3 flex-wrap flex-sm-nowrap">
                    <div class="avatar-hero-student">
                        <?= htmlspecialchars($initial_nama) ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <?php if (($siswa['status'] ?? '') === 'lulus'): ?>
                            <span class="badge" style="background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); font-size: 0.72rem; letter-spacing: 0.5px; border-radius: 30px;">
                                <i class="bi bi-mortarboard-fill me-1"></i> ALUMNI (Lulus <?= htmlspecialchars($siswa['tahun_lulus'] ?: 'Purna Didik') ?>)
                            </span>
                            <?php else: ?>
                            <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.72rem; letter-spacing: 0.5px; border-radius: 30px;">
                                <i class="bi bi-check-circle-fill me-1"></i> SISWA AKTIF
                            </span>
                            <?php endif; ?>
                            <span class="badge" style="background: rgba(56, 189, 248, 0.2); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 0.72rem; letter-spacing: 0.5px; border-radius: 30px;">
                                JURUSAN <?= htmlspecialchars($siswa['jurusan']) ?>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1" style="font-size: 1.45rem; letter-spacing: -0.3px; color:#ffffff;">
                            <?= htmlspecialchars($siswa['nama']) ?>
                        </h3>
                        <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                            <span class="student-meta-pill">
                                <i class="bi bi-mortarboard text-info"></i> Kelas: <strong class="text-white"><?= htmlspecialchars($siswa['nama_kelas']) ?></strong>
                            </span>
                            <span class="student-meta-pill">
                                <i class="bi bi-card-text text-warning"></i> NISN: <strong class="text-white font-monospace"><?= htmlspecialchars($siswa['nisn'] ?: $siswa['nis']) ?></strong>
                            </span>
                            <span class="student-meta-pill">
                                <i class="bi bi-gender-ambiguous text-light"></i> <?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                            </span>
                        </div>
                        <?php if (!empty($siswa['nama_ortu']) || !empty($siswa['wali_kelas'])): ?>
                        <div class="mt-2 text-white-50 small" style="font-size: 0.78rem;">
                            <?php if (!empty($siswa['wali_kelas'])): ?>
                                <span class="me-3"><i class="bi bi-person-badge me-1"></i> Wali Kelas: <strong class="text-light"><?= htmlspecialchars($siswa['wali_kelas']) ?></strong></span>
                            <?php endif; ?>
                            <?php if (!empty($siswa['nama_ortu'])): ?>
                                <span><i class="bi bi-people me-1"></i> Wali Murid: <strong class="text-light"><?= htmlspecialchars($siswa['nama_ortu']) ?></strong> <?= !empty($siswa['no_hp_ortu']) ? '(' . htmlspecialchars($siswa['no_hp_ortu']) . ')' : '' ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Metrik Skor Kanan -->
            <div class="col-lg-5 col-xl-4">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-metric-card text-center">
                            <div class="stat-label">Skor Karakter</div>
                            <div class="stat-value text-<?= $warna_skor ?>"><?= $skor ?></div>
                            <div>
                                <span class="badge bg-<?= $warna_skor ?> px-2 py-1" style="font-size: 0.74rem; border-radius: 8px;">
                                    <?= $label_skor ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-metric-card text-center">
                            <div class="stat-label">Rasio Perilaku</div>
                            <div class="d-flex align-items-center justify-content-center gap-2 my-1">
                                <span class="text-danger fw-bold fs-5">-<?= (int)$total_pel ?></span>
                                <span class="text-white-50">/</span>
                                <span class="text-success fw-bold fs-5">+<?= (int)$total_pres ?></span>
                            </div>
                            <div class="text-white-50" style="font-size: 0.7rem;">
                                <?= $jumlah_pelanggaran ?> Kasus &bull; <?= $jumlah_prestasi ?> Prestasi
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-end btn-print-hide">
                    <button type="button" class="btn btn-sm btn-outline-light px-3 py-2 fw-bold" style="border-radius: 12px; font-size: 0.8rem;" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Cetak Lembar Riwayat (PDF)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- BANNER KHUSUS JIKA SKOR < 75 (BUTUH PERHATIAN / PEMBINAAN) -->
    <?php if ($skor < 75): ?>
    <div class="alert alert-danger border-0 p-3 mb-4 shadow-sm d-flex flex-wrap align-items-center justify-content-between" style="border-radius: 18px; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 42px; height: 42px; flex-shrink: 0;">
                <i class="bi bi-shield-exclamation fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-danger mb-0" style="font-size: 0.95rem;">Perhatian: Siswa Berada di Bawah Batas Kelulusan Sikap</h6>
                <p class="text-secondary small mb-0 mt-1" style="font-size: 0.82rem; line-height: 1.4;">
                    Skor siswa saat ini <strong><?= $skor ?> / 100</strong> (di bawah standar minimal 75). Disarankan untuk segera melakukan konseling, pembinaan khusus bersama Guru BK, dan menginformasikan kepada wali murid.
                </p>
            </div>
        </div>
        <div class="mt-2 mt-md-0 btn-print-hide">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-bold" style="border-radius: 10px;">
                Status: Perlu Pembinaan
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- DUAL COLUMN STREAM: PELANGGARAN & PRESTASI -->
    <div class="row g-4">
        <!-- Kolom Kiri: Riwayat Pelanggaran -->
        <div class="col-lg-6">
            <div class="timeline-stream-card">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; background: #fee2e2; color: #dc2626;">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 0.9rem;"></i>
                        </span>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Catatan Pelanggaran</h6>
                            <span class="text-muted" style="font-size: 0.72rem;"><?= $jumlah_pelanggaran ?> kejadian tercatat</span>
                        </div>
                    </div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1" style="border-radius: 8px; font-size: 0.82rem;">
                        Total -<?= (int)$total_pel ?> Poin
                    </span>
                </div>

                <?php if (empty($pelanggaran_list)): ?>
                    <div class="p-4 text-center text-muted my-3 rounded-4" style="background: #f8fafc; border: 1.5px dashed #e2e8f0;">
                        <i class="bi bi-shield-check text-success fs-2 d-block mb-2"></i>
                        <div class="fw-bold text-dark small">Bersih dari Pelanggaran</div>
                        <div class="text-secondary small mt-1" style="font-size: 0.78rem;">Siswa ini belum pernah tercatat melakukan pelanggaran tata tertib sekolah.</div>
                    </div>
                <?php else: ?>
                    <div class="pt-2">
                        <?php foreach ($pelanggaran_list as $p): 
                            $tingkat = $p['tingkat'] ?? 'Ringan';
                            $tingkatBadge = match($tingkat) {
                                'Berat'  => 'background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;',
                                'Sedang' => 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;',
                                default  => 'background:#f8fafc; color:#475569; border:1px solid #e2e8f0;',
                            };
                        ?>
                            <div class="timeline-entry-item is-pelanggaran">
                                <div class="timeline-bullet"></div>
                                <div class="timeline-box-inner">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge px-2 py-1" style="border-radius: 6px; font-size: 0.7rem; <?= $tingkatBadge ?>">
                                                <?= htmlspecialchars($tingkat) ?>
                                            </span>
                                            <span class="text-muted small fw-medium" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($p['tanggal'])) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger text-white fw-bold px-2 py-1 shadow-xs" style="border-radius: 8px; font-size: 0.8rem;">
                                                -<?= (int)$p['poin'] ?>
                                            </span>
                                            <?php if (isGuruBK() || isAdmin()): ?>
                                            <a href="hapus_riwayat.php?jenis=pelanggaran&id=<?= $p['id'] ?>&siswa_id=<?= $siswa_id ?>" 
                                               class="btn btn-sm btn-outline-danger py-0 px-1 border-0 btn-print-hide" 
                                               style="font-size: 0.8rem;" 
                                               title="Hapus riwayat ini"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus catatan pelanggaran ini?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="fw-bold text-dark mt-1" style="font-size: 0.88rem; line-height: 1.35;">
                                        <?= htmlspecialchars($p['nama_pelanggaran']) ?>
                                    </div>
                                    <?php if (!empty($p['keterangan'])): ?>
                                    <div class="text-secondary small mt-1 p-2 rounded-2" style="background: rgba(0,0,0,0.02); font-size: 0.78rem; border-left: 2.5px solid #ef4444;">
                                        <i class="bi bi-chat-left-quote me-1"></i> <?= htmlspecialchars($p['keterangan']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['nama_petugas'])): ?>
                                    <div class="text-muted small mt-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-person-check me-1"></i> Dicatat oleh: <?= htmlspecialchars($p['nama_petugas']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Riwayat Prestasi -->
        <div class="col-lg-6">
            <div class="timeline-stream-card">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; background: #dcfce7; color: #16a34a;">
                            <i class="bi bi-trophy-fill" style="font-size: 0.9rem;"></i>
                        </span>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Catatan Prestasi &amp; Apresiasi</h6>
                            <span class="text-muted" style="font-size: 0.72rem;"><?= $jumlah_prestasi ?> capaian tercatat</span>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1" style="border-radius: 8px; font-size: 0.82rem;">
                        Total +<?= (int)$total_pres ?> Poin
                    </span>
                </div>

                <?php if (empty($prestasi_list)): ?>
                    <div class="p-4 text-center text-muted my-3 rounded-4" style="background: #f8fafc; border: 1.5px dashed #e2e8f0;">
                        <i class="bi bi-award text-secondary fs-2 d-block mb-2"></i>
                        <div class="fw-bold text-dark small">Belum Ada Catatan Prestasi</div>
                        <div class="text-secondary small mt-1" style="font-size: 0.78rem;">Siswa ini belum memiliki catatan penghargaan lomba atau apresiasi perilaku.</div>
                    </div>
                <?php else: ?>
                    <div class="pt-2">
                        <?php foreach ($prestasi_list as $pr): 
                            $tingkatPr = $pr['tingkat'] ?? 'Sekolah';
                        ?>
                            <div class="timeline-entry-item is-prestasi">
                                <div class="timeline-bullet"></div>
                                <div class="timeline-box-inner">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge px-2 py-1" style="border-radius: 6px; font-size: 0.7rem; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                                                <i class="bi bi-star-fill me-1"></i><?= htmlspecialchars($tingkatPr) ?>
                                            </span>
                                            <span class="text-muted small fw-medium" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($pr['tanggal'])) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success text-white fw-bold px-2 py-1 shadow-xs" style="border-radius: 8px; font-size: 0.8rem;">
                                                +<?= (int)$pr['poin'] ?>
                                            </span>
                                            <?php if (isGuruBK() || isAdmin()): ?>
                                            <a href="hapus_riwayat.php?jenis=prestasi&id=<?= $pr['id'] ?>&siswa_id=<?= $siswa_id ?>" 
                                               class="btn btn-sm btn-outline-danger py-0 px-1 border-0 btn-print-hide" 
                                               style="font-size: 0.8rem;" 
                                               title="Hapus riwayat ini"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus catatan prestasi ini?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="fw-bold text-dark mt-1" style="font-size: 0.88rem; line-height: 1.35;">
                                        <?= htmlspecialchars($pr['nama_prestasi']) ?>
                                    </div>
                                    <?php if (!empty($pr['keterangan'])): ?>
                                    <div class="text-secondary small mt-1 p-2 rounded-2" style="background: rgba(0,0,0,0.02); font-size: 0.78rem; border-left: 2.5px solid #10b981;">
                                        <i class="bi bi-chat-left-quote me-1"></i> <?= htmlspecialchars($pr['keterangan']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($pr['nama_petugas'])): ?>
                                    <div class="text-muted small mt-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-person-check me-1"></i> Dicatat oleh: <?= htmlspecialchars($pr['nama_petugas']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- AREA TANDA TANGAN RESMI KHUSUS PRINT LAPORAN -->
    <div class="print-signature-box">
        <div style="width: 200px;">
            <div>Mengetahui,</div>
            <div>Orang Tua / Wali Murid</div>
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; font-weight: bold;"><?= htmlspecialchars($siswa['nama_ortu'] ?: '( ........................................ )') ?></div>
        </div>
        <div style="width: 200px;">
            <div>Bogor, <?= date('d F Y') ?></div>
            <div>Wali Kelas</div>
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; font-weight: bold;"><?= htmlspecialchars($siswa['wali_kelas'] ?: '( ........................................ )') ?></div>
        </div>
        <div style="width: 200px;">
            <div>Guru Bimbingan Konseling</div>
            <div>Koordinator BK</div>
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; font-weight: bold;">( ........................................ )</div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAMPILAN ONBOARDING / EMPTY STATE (KETIKA BELUM ADA SISWA DIPILIH) -->
    <!-- ========================================================================= -->
    <?php else: ?>

    <div class="card p-4 p-md-5 border-0 shadow-sm text-center mb-4" style="border-radius: 24px; background: #ffffff;">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle shadow-xs" 
             style="width: 76px; height: 76px; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); color: #0284c7;">
            <i class="bi bi-clock-history fs-1"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.3px;">Pilih Murid untuk Membuka Dossier Rekam Jejak</h4>
        <p class="text-secondary small mx-auto mb-4" style="max-width: 520px; line-height: 1.55; font-size: 0.88rem;">
            Ketik nama atau NISN murid pada kolom pencarian di atas untuk meninjau akumulasi skor, grafik perilaku, dan catatan pelanggaran maupun prestasi.
        </p>

        <?php if (!empty($shortcut_terbaru)): ?>
        <!-- Pintasan Kasus Terbaru -->
        <div class="border-top pt-4 text-start">
            <div class="fw-bold text-dark small mb-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.8px; color: #64748b;">
                <i class="bi bi-activity text-primary me-1"></i> Aktivitas Catatan Terbaru:
            </div>
            <div class="row g-2">
                <?php foreach ($shortcut_terbaru as $st): 
                    $isPel = ($st['tipe'] === 'pelanggaran');
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between cursor-pointer" 
                         style="cursor: pointer; transition: all 0.15s ease;"
                         onclick="window.location.href='riwayat_siswa.php?id=<?= $st['s_id'] ?>'"
                         onmouseover="this.style.background='#ffffff'; this.style.borderColor='#93c5fd';"
                         onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" 
                                  style="width: 32px; height: 32px; background: <?= $isPel ? '#fee2e2' : '#dcfce7' ?>; color: <?= $isPel ? '#dc2626' : '#16a34a' ?>;">
                                <i class="bi <?= $isPel ? 'bi-exclamation-triangle-fill' : 'bi-trophy-fill' ?>" style="font-size: 0.82rem;"></i>
                            </span>
                            <div class="text-truncate">
                                <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($st['s_nama']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($st['nama_kelas']) ?> &bull; <?= htmlspecialchars($st['nama_kasus']) ?></div>
                            </div>
                        </div>
                        <span class="badge <?= $isPel ? 'bg-danger' : 'bg-success' ?> rounded-pill fw-bold" style="font-size: 0.75rem;">
                            <?= $isPel ? '-' : '+' ?><?= (int)$st['poin'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>

<!-- JAVASCRIPT LOGIC INTERAKTIF -->
<script>
const daftarSiswa = <?= json_encode($daftar_siswa) ?>;
const totalSiswaSemua = <?= $total_siswa ?>;
const activeSiswaId = <?= (int)$siswa_id ?>;

function getFilteredSiswa() {
    const kId = document.getElementById('filterKelasSiswa').value;
    const q = (document.getElementById('inputCariSiswa').value || '').toLowerCase().trim();
    
    let muridDiKelas = daftarSiswa;
    if (kId !== 'semua') {
        muridDiKelas = daftarSiswa.filter(s => String(s.kelas_id) === String(kId));
    }
    
    let hasil = muridDiKelas;
    if (q) {
        hasil = muridDiKelas.filter(s => {
            const nama = (s.nama || '').toLowerCase();
            const nisn = (s.nisn || '').toLowerCase();
            const nis = (s.nis || '').toLowerCase();
            const kelas = (s.nama_kelas || '').toLowerCase();
            return nama.includes(q) || nisn.includes(q) || nis.includes(q) || kelas.includes(q);
        });
    }
    
    return {
        hasil: hasil,
        totalKelas: muridDiKelas.length,
        totalSemua: totalSiswaSemua
    };
}

function updateCounterDisplay(filtered) {
    const counterEl = document.getElementById('counterMuridText');
    if (!counterEl) return;
    const kId = document.getElementById('filterKelasSiswa').value;
    const q = (document.getElementById('inputCariSiswa').value || '').trim();
    
    if (q) {
        counterEl.innerText = `${filtered.hasil.length}/${kId === 'semua' ? filtered.totalSemua : filtered.totalKelas}`;
    } else if (kId !== 'semua') {
        counterEl.innerText = `${filtered.totalKelas}/${filtered.totalSemua}`;
    } else {
        counterEl.innerText = `${filtered.totalSemua}/${filtered.totalSemua}`;
    }
}

function renderListSiswa() {
    const filtered = getFilteredSiswa();
    updateCounterDisplay(filtered);
    
    const container = document.getElementById('listSiswaContainer');
    if (!container) return;
    
    if (filtered.hasil.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-muted">
                <i class="bi bi-search fs-4 d-block mb-1 text-secondary"></i>
                <span class="small">Tidak ada siswa yang cocok dengan pencarian Anda.</span>
            </div>
        `;
        return;
    }
    
    let html = '';
    const paletWarna = ['#0d9488', '#0284c7', '#7c3aed', '#db2777', '#ea580c', '#4f46e5'];
    
    filtered.hasil.slice(0, 45).forEach((s, idx) => {
        const initial = (s.nama || '?').charAt(0).toUpperCase();
        const avatarColor = paletWarna[s.id % paletWarna.length];
        const isActive = (s.id == activeSiswaId) ? 'active-selection' : '';
        const badgeJurusan = s.jurusan === 'TJKT' ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-info-subtle text-info-emphasis border-info-subtle';
        
        html += `
            <div class="item-siswa-row ${isActive}" onclick="pilihSiswa(${s.id})">
                <div class="d-flex align-items-center gap-3 overflow-hidden">
                    <div class="avatar-student" style="background: ${avatarColor};">
                        ${initial}
                    </div>
                    <div class="text-truncate">
                        <div class="fw-bold text-dark small text-truncate" style="font-size: 0.88rem;">${s.nama}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            NISN: <span class="font-monospace text-secondary">${s.nisn || s.nis || '-'}</span>
                            &bull; Kelas: <strong>${s.nama_kelas}</strong>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                    <span class="badge border ${badgeJurusan} px-2 py-1" style="font-size: 0.72rem; border-radius: 6px;">
                        ${s.nama_kelas}
                    </span>
                    <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                </div>
            </div>
        `;
    });
    
    if (filtered.hasil.length > 45) {
        html += `
            <div class="text-center text-muted p-2 small border-top mt-1" style="font-size: 0.76rem;">
                <i class="bi bi-three-dots"></i> Menampilkan 45 dari ${filtered.hasil.length} siswa. Ketik nama untuk mempersempit hasil.
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function bukaDropdownSiswa() {
    renderListSiswa();
    const box = document.getElementById('dropdownListSiswa');
    if (box) box.classList.remove('d-none');
}

function tutupDropdownSiswa() {
    const box = document.getElementById('dropdownListSiswa');
    if (box) box.classList.add('d-none');
}

function onFilterKelasChange(val) {
    renderListSiswa();
    bukaDropdownSiswa();
}

function onCariSiswaInput(val) {
    bukaDropdownSiswa();
}

function pilihSiswa(id) {
    window.location.href = 'riwayat_siswa.php?id=' + id;
}


// Tutup dropdown saat klik di luar kotak
document.addEventListener('click', function(e) {
    const box = document.getElementById('dropdownListSiswa');
    const input = document.getElementById('inputCariSiswa');
    const select = document.getElementById('filterKelasSiswa');
    if (!box || !input) return;
    
    if (!box.contains(e.target) && e.target !== input && e.target !== select) {
        tutupDropdownSiswa();
    }
});

// Tombol keyboard Escape untuk menutup popover
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        tutupDropdownSiswa();
    }
});

// Inisialisasi awal counter
document.addEventListener('DOMContentLoaded', function() {
    const filtered = getFilteredSiswa();
    updateCounterDisplay(filtered);
});
</script>

<?php include 'includes/footer.php'; ?>
