<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Akses terbatas untuk Admin dan Guru BK
if (!isGuruBK()) {
    redirect('index.php', 'Akses ditolak: Hanya Administrator dan Guru BK yang memiliki wewenang mengelola Kenaikan Kelas & Kelulusan.', 'danger');
}

$judul_halaman = 'Kenaikan & Kelulusan Siswa';
$tab = clean($koneksi, $_GET['tab'] ?? 'kelulusan');
if (!in_array($tab, ['kelulusan', 'kenaikan', 'riwayat'])) {
    $tab = 'kelulusan';
}

// =========================================================================
// PROSES AKSI POST
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = clean($koneksi, $_POST['aksi'] ?? '');

    // 1. LULUSKAN SISWA KELAS XII (PINDAHKAN KE ALUMNI)
    if ($aksi === 'luluskan_siswa') {
        $tahun_lulus = clean($koneksi, $_POST['tahun_lulus'] ?? '');
        $siswa_ids = $_POST['siswa_ids'] ?? [];

        if (empty($tahun_lulus)) {
            redirect('kelulusan_kenaikan.php?tab=kelulusan', 'Tahun Kelulusan wajib diisi (contoh: 2026/2027).', 'danger');
        }
        if (empty($siswa_ids) || !is_array($siswa_ids)) {
            redirect('kelulusan_kenaikan.php?tab=kelulusan', 'Pilih minimal satu siswa yang akan diluluskan.', 'warning');
        }

        $clean_ids = array_map('intval', $siswa_ids);
        $ids_str = implode(',', $clean_ids);

        $update = mysqli_query($koneksi, "
            UPDATE siswa 
            SET status = 'lulus', 
                tahun_lulus = '$tahun_lulus', 
                tanggal_lulus = CURDATE() 
            WHERE id IN ($ids_str) AND status = 'aktif'
        ");

        if ($update) {
            $jml = mysqli_affected_rows($koneksi);
            redirect('kelulusan_kenaikan.php?tab=kelulusan', "Selamat! Sebanyak $jml siswa Kelas XII berhasil diluluskan (Tahun Ajaran $tahun_lulus). Data tersimpan aman di Arsip Alumni.", 'success');
        } else {
            redirect('kelulusan_kenaikan.php?tab=kelulusan', 'Terjadi kesalahan sistem saat memproses kelulusan: ' . mysqli_error($koneksi), 'danger');
        }
    }

    // 2. KENAIKAN KELAS PER ROMBEL
    if ($aksi === 'kenaikan_rombel') {
        $kelas_asal_id = (int)($_POST['kelas_asal_id'] ?? 0);
        $kelas_tujuan_id = (int)($_POST['kelas_tujuan_id'] ?? 0);
        $siswa_ids = $_POST['siswa_ids'] ?? [];

        if (!$kelas_asal_id || !$kelas_tujuan_id) {
            redirect('kelulusan_kenaikan.php?tab=kenaikan', 'Kelas asal dan kelas tujuan harus dipilih.', 'danger');
        }
        if ($kelas_asal_id === $kelas_tujuan_id) {
            redirect('kelulusan_kenaikan.php?tab=kenaikan', 'Kelas tujuan tidak boleh sama dengan kelas asal.', 'warning');
        }
        if (empty($siswa_ids) || !is_array($siswa_ids)) {
            redirect('kelulusan_kenaikan.php?tab=kenaikan', 'Pilih minimal satu siswa yang akan dinaikkan kelas.', 'warning');
        }

        $clean_ids = array_map('intval', $siswa_ids);
        $ids_str = implode(',', $clean_ids);

        // Ambil nama kelas untuk notifikasi
        $qAsal = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id=$kelas_asal_id"));
        $qTujuan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id=$kelas_tujuan_id"));

        $update = mysqli_query($koneksi, "
            UPDATE siswa 
            SET kelas_id = $kelas_tujuan_id 
            WHERE id IN ($ids_str) AND status = 'aktif'
        ");

        if ($update) {
            $jml = mysqli_affected_rows($koneksi);
            redirect('kelulusan_kenaikan.php?tab=kenaikan', "Berhasil! Sebanyak $jml siswa dari {$qAsal['nama_kelas']} telah resmi dinaikkan ke tingkat {$qTujuan['nama_kelas']}.", 'success');
        } else {
            redirect('kelulusan_kenaikan.php?tab=kenaikan', 'Gagal memproses kenaikan kelas: ' . mysqli_error($koneksi), 'danger');
        }
    }

    // 3. KENAIKAN KELAS SERENTAK (OTOMATIS SELURUH ROMBEL X & XI)
    if ($aksi === 'kenaikan_serentak') {
        // Cek keamanan 1: Apakah kelas XII masih ada siswa aktif?
        $cek_xii = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT COUNT(*) n 
            FROM siswa s 
            JOIN kelas k ON s.kelas_id=k.id 
            WHERE s.status='aktif' AND k.nama_kelas LIKE 'XII %'
        "))['n'];

        if ($cek_xii > 0) {
            redirect('kelulusan_kenaikan.php?tab=kenaikan', "Gagal Kenaikan Serentak: Masih terdapat $cek_xii siswa aktif di Kelas XII! Mohon proses kelulusan seluruh siswa kelas XII terlebih dahulu di Tab 'Kelulusan Kelas XII' agar kelas XII kosong.", 'danger');
        }

        // Ambil seluruh kelas
        $qKelas = mysqli_query($koneksi, "SELECT id, nama_kelas, jurusan FROM kelas ORDER BY id");
        $all_kelas = [];
        while ($rk = mysqli_fetch_assoc($qKelas)) {
            $all_kelas[$rk['nama_kelas']] = $rk;
        }

        // Pemetaan:
        // XI -> XII
        // X -> XI
        $map_rombels = [
            // XI ke XII
            'XI TJKT 1' => 'XII TJKT 1',
            'XI TJKT 2' => 'XII TJKT 2',
            'XI MPLB 1' => 'XII MPLB 1',
            'XI MPLB 2' => 'XII MPLB 2',
            // X ke XI
            'X TJKT 1'  => 'XI TJKT 1',
            'X TJKT 2'  => 'XI TJKT 2',
            'X MPLB 1'  => 'XI MPLB 1',
            'X MPLB 2'  => 'XI MPLB 2',
        ];

        mysqli_begin_transaction($koneksi);
        try {
            $total_naik = 0;
            // LANGKAH 1: Naikkan XI ke XII terlebih dahulu
            $rombels_xi = ['XI TJKT 1', 'XI TJKT 2', 'XI MPLB 1', 'XI MPLB 2'];
            foreach ($rombels_xi as $asal_nama) {
                $tujuan_nama = $map_rombels[$asal_nama];
                if (isset($all_kelas[$asal_nama], $all_kelas[$tujuan_nama])) {
                    $id_asal = (int)$all_kelas[$asal_nama]['id'];
                    $id_tujuan = (int)$all_kelas[$tujuan_nama]['id'];
                    mysqli_query($koneksi, "UPDATE siswa SET kelas_id=$id_tujuan WHERE kelas_id=$id_asal AND status='aktif'");
                    $total_naik += mysqli_affected_rows($koneksi);
                }
            }

            // LANGKAH 2: Naikkan X ke XI
            $rombels_x = ['X TJKT 1', 'X TJKT 2', 'X MPLB 1', 'X MPLB 2'];
            foreach ($rombels_x as $asal_nama) {
                $tujuan_nama = $map_rombels[$asal_nama];
                if (isset($all_kelas[$asal_nama], $all_kelas[$tujuan_nama])) {
                    $id_asal = (int)$all_kelas[$asal_nama]['id'];
                    $id_tujuan = (int)$all_kelas[$tujuan_nama]['id'];
                    mysqli_query($koneksi, "UPDATE siswa SET kelas_id=$id_tujuan WHERE kelas_id=$id_asal AND status='aktif'");
                    $total_naik += mysqli_affected_rows($koneksi);
                }
            }

            mysqli_commit($koneksi);
            redirect('kelulusan_kenaikan.php?tab=kenaikan', "Alhamdulillah! Proses Kenaikan Kelas Serentak Sukses. Total $total_naik siswa telah dipromosikan (XI &rarr; XII dan X &rarr; XI). Kelas X kini siap menerima data siswa baru.", 'success');
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            redirect('kelulusan_kenaikan.php?tab=kenaikan', 'Terjadi kesalahan sistem saat proses kenaikan serentak: ' . $e->getMessage(), 'danger');
        }
    }

    // 4. BATALKAN STATUS LULUS (KEMBALIKAN KE AKTIF)
    if ($aksi === 'batalkan_kelulusan') {
        $siswa_id = (int)($_POST['siswa_id'] ?? 0);
        if ($siswa_id > 0) {
            $qS = mysqli_query($koneksi, "UPDATE siswa SET status='aktif', tahun_lulus=NULL, tanggal_lulus=NULL WHERE id=$siswa_id");
            if ($qS) {
                redirect('kelulusan_kenaikan.php?tab=kelulusan', 'Status siswa berhasil dikembalikan menjadi Siswa Aktif.', 'info');
            }
        }
        redirect('kelulusan_kenaikan.php?tab=kelulusan', 'Gagal membatalkan status kelulusan.', 'danger');
    }
}

// =========================================================================
// DATA PERSIAPAN TAMPILAN
// =========================================================================

// Data statistik ringkas
$stat_xii_aktif = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) n FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' AND k.nama_kelas LIKE 'XII %'
"))['n'];

$stat_xi_aktif = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) n FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' AND k.nama_kelas LIKE 'XI %'
"))['n'];

$stat_x_aktif = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) n FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' AND k.nama_kelas LIKE 'X %'
"))['n'];

$stat_alumni_total = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) n FROM siswa WHERE status='lulus'
"))['n'];

// Master kelas
$qKelasAll = mysqli_query($koneksi, "
    SELECT k.*, COUNT(CASE WHEN s.status='aktif' THEN s.id END) AS jumlah_aktif 
    FROM kelas k 
    LEFT JOIN siswa s ON k.id=s.kelas_id 
    GROUP BY k.id 
    ORDER BY k.jurusan, k.nama_kelas
");
$daftar_kelas = [];
$kelas_xii = [];
$kelas_xi = [];
$kelas_x = [];
while ($k = mysqli_fetch_assoc($qKelasAll)) {
    $daftar_kelas[] = $k;
    if (strpos($k['nama_kelas'] ?? '', 'XII ') === 0) $kelas_xii[] = $k;
    elseif (strpos($k['nama_kelas'] ?? '', 'XI ') === 0) $kelas_xi[] = $k;
    elseif (strpos($k['nama_kelas'] ?? '', 'X ') === 0) $kelas_x[] = $k;
}

// Filter kelas XII terpilih pada Tab Kelulusan
$filter_xii_kelas = (int)($_GET['kelas_xii'] ?? 0);
$sql_siswa_xii = "
    SELECT s.*, k.nama_kelas, k.jurusan,
        (SELECT COALESCE(SUM(kp.poin),0) FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id=s.id) AS total_pelanggaran,
        (SELECT COALESCE(SUM(kpr.poin),0) FROM riwayat_prestasi rpr JOIN kategori_prestasi kpr ON rpr.kategori_id=kpr.id WHERE rpr.siswa_id=s.id) AS total_prestasi
    FROM siswa s 
    JOIN kelas k ON s.kelas_id=k.id 
    WHERE s.status='aktif' AND k.nama_kelas LIKE 'XII %'
";
if ($filter_xii_kelas > 0) {
    $sql_siswa_xii .= " AND s.kelas_id = $filter_xii_kelas";
}
$sql_siswa_xii .= " ORDER BY k.jurusan, k.nama_kelas, s.nama";
$qSiswaXii = mysqli_query($koneksi, $sql_siswa_xii);

// Tahun ajaran default cerdas
$tahun_sekarang = (int)date('Y');
$bulan_sekarang = (int)date('m');
if ($bulan_sekarang >= 7) {
    $tahun_ajaran_default = $tahun_sekarang . '/' . ($tahun_sekarang + 1);
} else {
    $tahun_ajaran_default = ($tahun_sekarang - 1) . '/' . $tahun_sekarang;
}

include 'includes/header.php';
?>

<!-- Header Banner Halaman -->
<div class="card p-4 mb-4 text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); border-radius: 18px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="badge bg-warning-subtle text-warning border border-warning-subtle mb-2 px-3 py-1 fw-bold">
                <i class="bi bi-arrow-repeat me-1"></i> Manajemen Tahun Ajaran Baru
            </div>
            <h4 class="fw-bold mb-1 text-white">Kenaikan Kelas &amp; Kelulusan Siswa</h4>
            <p class="text-white-50 mb-0 small">
                Transisi berjenjang SMKS Tiara Nusa: Luluskan siswa tingkat akhir ke <strong>Arsip Alumni</strong> dan promosikan siswa tingkat X &amp; XI dengan aman tanpa kehilangan rekam jejak.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="alumni.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="bi bi-mortarboard-fill text-warning"></i>
                <span>Lihat Arsip Alumni (<?= $stat_alumni_total ?>)</span>
            </a>
            <a href="siswa.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="bi bi-people-fill text-info"></i>
                <span>Data Siswa Aktif</span>
            </a>
        </div>
    </div>
</div>

<!-- Ringkasan Status Rombel & Jenjang -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="background: #f8fafc; border-radius: 14px;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #fee2e2; color: #dc2626;">
                    <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Tingkat XII (Calon Lulus)</div>
                    <div class="fs-4 fw-bold text-dark"><?= $stat_xii_aktif ?> <small class="fs-6 fw-normal text-muted">Siswa</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="background: #f8fafc; border-radius: 14px;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #e0e7ff; color: #4338ca;">
                    <i class="bi bi-arrow-up-circle-fill fs-5"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Tingkat XI (Naik ke XII)</div>
                    <div class="fs-4 fw-bold text-dark"><?= $stat_xi_aktif ?> <small class="fs-6 fw-normal text-muted">Siswa</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="background: #f8fafc; border-radius: 14px;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Tingkat X (Naik ke XI)</div>
                    <div class="fs-4 fw-bold text-dark"><?= $stat_x_aktif ?> <small class="fs-6 fw-normal text-muted">Siswa</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="background: #f8fafc; border-radius: 14px;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #dcfce7; color: #15803d;">
                    <i class="bi bi-archive-fill fs-5"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Total Alumni Terdata</div>
                    <div class="fs-4 fw-bold text-dark"><?= $stat_alumni_total ?> <small class="fs-6 fw-normal text-muted">Alumni</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigasi Tab Utama -->
<ul class="nav nav-pills gap-2 mb-4 p-2 bg-white rounded-4 shadow-sm border" id="pills-tab" role="tablist">
    <li class="nav-item flex-fill text-center" role="presentation">
        <a href="kelulusan_kenaikan.php?tab=kelulusan" class="nav-link py-2 fw-semibold <?= $tab==='kelulusan' ? 'active' : '' ?>" style="border-radius:10px;">
            <i class="bi bi-mortarboard-fill me-1"></i> 1. Kelulusan Siswa Kelas XII (Alumni)
            <?php if ($stat_xii_aktif > 0): ?>
                <span class="badge bg-danger ms-1"><?= $stat_xii_aktif ?></span>
            <?php else: ?>
                <span class="badge bg-success ms-1"><i class="bi bi-check"></i> Kosong</span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item flex-fill text-center" role="presentation">
        <a href="kelulusan_kenaikan.php?tab=kenaikan" class="nav-link py-2 fw-semibold <?= $tab==='kenaikan' ? 'active' : '' ?>" style="border-radius:10px;">
            <i class="bi bi-chevron-double-up me-1"></i> 2. Kenaikan Kelas (Tingkat X &amp; XI)
        </a>
    </li>
    <li class="nav-item flex-fill text-center" role="presentation">
        <a href="kelulusan_kenaikan.php?tab=riwayat" class="nav-link py-2 fw-semibold <?= $tab==='riwayat' ? 'active' : '' ?>" style="border-radius:10px;">
            <i class="bi bi-info-circle-fill me-1"></i> Panduan &amp; FAQ Siklus Tahunan
        </a>
    </li>
</ul>

<!-- =========================================================================
     TAB 1: PROSES KELULUSAN KELAS XII
     ========================================================================= -->
<?php if ($tab === 'kelulusan'): ?>
<div class="card p-3 p-md-4 border-0 shadow-sm mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom">
        <div>
            <h5 class="fw-bold mb-1 text-dark">
                <i class="bi bi-mortarboard-fill text-primary me-2"></i>Kelulusan Siswa Tingkat Akhir (Kelas XII)
            </h5>
            <p class="text-muted small mb-0">
                Pilih siswa kelas XII yang dinyatakan lulus. Siswa yang diluluskan akan otomatis berganti status menjadi <strong>Lulus (Alumni)</strong>, tidak lagi muncul pada menu pelanggaran aktif, dan tersimpan permanen di <strong>Arsip Alumni</strong>.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted fw-semibold">Filter Rombel:</label>
            <select class="form-select form-select-sm" style="width: auto; border-radius: 10px;" onchange="location.href='kelulusan_kenaikan.php?tab=kelulusan&kelas_xii=' + this.value;">
                <option value="0" <?= $filter_xii_kelas===0 ? 'selected' : '' ?>>Semua Rombel XII (<?= $stat_xii_aktif ?> Siswa)</option>
                <?php foreach ($kelas_xii as $kx): ?>
                    <option value="<?= $kx['id'] ?>" <?= $filter_xii_kelas===$kx['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kx['nama_kelas']) ?> (<?= $kx['jumlah_aktif'] ?> Siswa)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($stat_xii_aktif === 0): ?>
        <div class="text-center py-5">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; background: #ecfdf5; color: #059669;">
                <i class="bi bi-check-circle-fill fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark">Seluruh Kelas XII Sudah Kosong / Telah Diluluskan</h5>
            <p class="text-muted small mx-auto" style="max-width: 500px;">
                Saat ini tidak ada siswa aktif yang tersisa di rombel Kelas XII. Anda sekarang dapat melanjutkan ke <strong>Langkah 2: Kenaikan Kelas</strong> untuk menaikkan siswa Kelas XI ke Kelas XII.
            </p>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="kelulusan_kenaikan.php?tab=kenaikan" class="btn btn-primary btn-sm px-4 py-2" style="border-radius:10px;">
                    <i class="bi bi-arrow-right-circle me-1"></i> Lanjut ke Kenaikan Kelas &rarr;
                </a>
                <a href="alumni.php" class="btn btn-outline-secondary btn-sm px-4 py-2" style="border-radius:10px;">
                    <i class="bi bi-archive me-1"></i> Buka Arsip Alumni
                </a>
            </div>
        </div>
    <?php else: ?>
        <form method="POST" id="formLuluskan" onsubmit="return confirmLuluskan();">
            <input type="hidden" name="aksi" value="luluskan_siswa">

            <!-- Konfigurasi Tahun Lulus & Action Bar -->
            <div class="p-3 mb-3 rounded-3" style="background: #f8fafc; border: 1.5px dashed #cbd5e1;">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-dark mb-1">
                            <i class="bi bi-calendar-check me-1 text-primary"></i> Tahun Ajaran Kelulusan:
                        </label>
                        <input type="text" name="tahun_lulus" class="form-control form-control-sm fw-bold text-primary" value="<?= htmlspecialchars($tahun_ajaran_default) ?>" placeholder="Contoh: 2026/2027" required style="border-radius:8px;">
                        <small class="text-muted" style="font-size:0.75rem;">Format disarankan: <strong>2026/2027</strong></small>
                    </div>
                    <div class="col-md-8 text-md-end">
                        <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2 mt-2 mt-md-0">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckAll(true)" style="border-radius:8px;">
                                <i class="bi bi-check-all"></i> Centang Semua
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckAll(false)" style="border-radius:8px;">
                                <i class="bi bi-x"></i> Batalkan Semua
                            </button>
                            <button type="submit" class="btn btn-success btn-sm px-3 fw-bold shadow-sm" style="border-radius:8px;">
                                <i class="bi bi-mortarboard-fill me-1"></i> Luluskan Siswa Terpilih (<span id="countSelected">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Siswa Kelas XII -->
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="tableSiswaXII">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" class="form-check-input" id="checkMaster" checked onchange="toggleCheckAll(this.checked)">
                            </th>
                            <th width="50" class="text-center">No</th>
                            <th>Identitas Siswa</th>
                            <th>Rombel &amp; Jurusan</th>
                            <th class="text-center">Pelanggaran</th>
                            <th class="text-center">Prestasi</th>
                            <th class="text-center">Skor Sikap</th>
                            <th class="text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        while ($s = mysqli_fetch_assoc($qSiswaXii)): 
                            $skor = SKOR_AWAL - (int)$s['total_pelanggaran'] + (int)$s['total_prestasi'];
                            [$predikat_label, $predikat_class] = hitungPredikat($skor);
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>" class="form-check-input check-item" checked onchange="updateSelectedCount()">
                            </td>
                            <td class="text-center text-muted small"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs flex-shrink-0" 
                                         style="width:34px; height:34px; background: <?= $s['jenis_kelamin']==='L' ? '#2563eb' : '#db2777' ?>; font-size:0.75rem;">
                                        <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size:0.9rem;"><?= htmlspecialchars($s['nama']) ?></div>
                                        <div class="small text-muted font-monospace" style="font-size:0.75rem;">
                                            NISN: <?= $s['nisn'] ?: '-' ?> &bull; NIS: <?= $s['nis'] ?: '-' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($s['nama_kelas']) ?></span>
                                <div class="mt-1"><?= badgeJurusan($s['jurusan']) ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($s['total_pelanggaran'] > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger fw-bold">-<?= $s['total_pelanggaran'] ?> Poin</span>
                                <?php else: ?>
                                    <span class="text-muted small">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($s['total_prestasi'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success fw-bold">+<?= $s['total_prestasi'] ?> Poin</span>
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
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- =========================================================================
     TAB 2: PROSES KENAIKAN KELAS (TINGKAT X & XI)
     ========================================================================= -->
<?php if ($tab === 'kenaikan'): ?>
<div class="card p-3 p-md-4 border-0 shadow-sm mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom">
        <div>
            <h5 class="fw-bold mb-1 text-dark">
                <i class="bi bi-chevron-double-up text-primary me-2"></i>Kenaikan Kelas Tingkat X &amp; Tingkat XI
            </h5>
            <p class="text-muted small mb-0">
                Pindahkan siswa ke tingkat berikutnya secara serentak atau per rombel. Siswa tingkat XI dipromosikan ke tingkat XII, dan siswa tingkat X dipromosikan ke tingkat XI.
            </p>
        </div>
    </div>

    <!-- Peringatan Jika Kelas XII Masih Ada Siswa -->
    <?php if ($stat_xii_aktif > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-3 rounded-4 p-3 mb-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
            <div>
                <strong class="d-block text-dark">Perhatian: Masih ada <?= $stat_xii_aktif ?> siswa aktif di Kelas XII!</strong>
                <span class="small text-muted">
                    Sangat disarankan untuk memproses <strong>Kelulusan Kelas XII</strong> terlebih dahulu sebelum melakukan kenaikan kelas, agar rombel Kelas XII kosong dan data siswa tidak saling bertumpuk.
                </span>
                <div class="mt-2">
                    <a href="kelulusan_kenaikan.php?tab=kelulusan" class="btn btn-warning btn-sm fw-bold" style="border-radius:8px;">
                        <i class="bi bi-mortarboard-fill me-1"></i> Buka Tab Kelulusan Kelas XII Sekarang &rarr;
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- OPSI A: KENAIKAN KELAS SERENTAK (1 KLIK) -->
        <div class="col-lg-6">
            <div class="card h-100 p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 16px;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #059669; color: #ffffff;">
                        <i class="bi bi-lightning-charge-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-success">Kenaikan Kelas Serentak (Semua Rombel)</h6>
                        <small class="text-muted">Proses otomatis untuk seluruh rombel X dan XI</small>
                    </div>
                </div>

                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Sistem akan otomatis memindahkan seluruh rombel secara paralel:
                </p>

                <div class="bg-white p-3 rounded-3 mb-3 shadow-xs small">
                    <div class="fw-bold text-dark mb-2 border-bottom pb-1">
                        <i class="bi bi-layers-fill text-primary me-1"></i> Rencana Pemetaan Promosi:
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="badge bg-secondary-subtle text-secondary w-100 text-start py-2">
                                XI TJKT 1 &rarr; <strong>XII TJKT 1</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-secondary-subtle text-secondary w-100 text-start py-2">
                                XI TJKT 2 &rarr; <strong>XII TJKT 2</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-secondary-subtle text-secondary w-100 text-start py-2">
                                XI MPLB 1 &rarr; <strong>XII MPLB 1</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-secondary-subtle text-secondary w-100 text-start py-2">
                                XI MPLB 2 &rarr; <strong>XII MPLB 2</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-info-subtle text-info w-100 text-start py-2">
                                X TJKT 1 &rarr; <strong>XI TJKT 1</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-info-subtle text-info w-100 text-start py-2">
                                X TJKT 2 &rarr; <strong>XI TJKT 2</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-info-subtle text-info w-100 text-start py-2">
                                X MPLB 1 &rarr; <strong>XI MPLB 1</strong>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-info-subtle text-info w-100 text-start py-2">
                                X MPLB 2 &rarr; <strong>XI MPLB 2</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" onsubmit="return confirmKenaikanSerentak();" class="mt-auto">
                    <input type="hidden" name="aksi" value="kenaikan_serentak">
                    <button type="submit" class="btn btn-success fw-bold w-100 py-2 shadow-sm" style="border-radius: 10px;" <?= $stat_xii_aktif > 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-play-circle-fill me-1"></i> Jalankan Kenaikan Kelas Serentak
                    </button>
                    <?php if ($stat_xii_aktif > 0): ?>
                        <small class="text-danger d-block mt-1 text-center" style="font-size:0.75rem;">
                            *Tombol dinonaktifkan sampai kelas XII dikosongkan.
                        </small>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- OPSI B: KENAIKAN KELAS PER ROMBEL (SELEKTIF) -->
        <div class="col-lg-6">
            <div class="card h-100 p-4 border-0 shadow-sm" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #e0e7ff; color: #4338ca;">
                        <i class="bi bi-sliders fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Kenaikan Kelas Manual Per Rombel</h6>
                        <small class="text-muted">Pilih satu rombel asal dan rombel tujuan spesifik</small>
                    </div>
                </div>

                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Gunakan opsi ini jika Anda ingin menaikkan siswa kelas per rombel atau ada siswa tertentu yang perlu disesuaikan (misal tinggal kelas).
                </p>

                <!-- Form Pemilihan Rombel -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Pilih Rombel Asal yang Ingin Diproses:</label>
                    <select class="form-select form-select-sm" id="selectRombelAsal" onchange="loadSiswaRombel(this.value)">
                        <option value="">-- Pilih Kelas Asal --</option>
                        <optgroup label="Tingkat XI (Naik ke XII)">
                            <?php foreach ($kelas_xi as $kx): ?>
                                <option value="<?= $kx['id'] ?>"><?= htmlspecialchars($kx['nama_kelas']) ?> (<?= $kx['jumlah_aktif'] ?> Siswa Aktif)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Tingkat X (Naik ke XI)">
                            <?php foreach ($kelas_x as $kx): ?>
                                <option value="<?= $kx['id'] ?>"><?= htmlspecialchars($kx['nama_kelas']) ?> (<?= $kx['jumlah_aktif'] ?> Siswa Aktif)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div id="containerRombelProses" style="display:none;">
                    <form method="POST" id="formRombelManual">
                        <input type="hidden" name="aksi" value="kenaikan_rombel">
                        <input type="hidden" name="kelas_asal_id" id="inputKelasAsalId" value="">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Pilih Rombel Tujuan:</label>
                            <select name="kelas_tujuan_id" id="selectRombelTujuan" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Kelas Tujuan --</option>
                                <?php foreach ($daftar_kelas as $dk): ?>
                                    <option value="<?= $dk['id'] ?>"><?= htmlspecialchars($dk['nama_kelas']) ?> (<?= $dk['jurusan'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="siswaRombelList" class="mb-3 p-3 bg-light rounded-3" style="max-height: 200px; overflow-y: auto;">
                            <!-- Diisi via AJAX/JS -->
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2 shadow-sm" style="border-radius:10px;">
                            <i class="bi bi-arrow-up-right-circle me-1"></i> Proses Kenaikan Rombel Ini
                        </button>
                    </form>
                </div>

                <div id="containerRombelPlaceholder" class="text-center py-4 bg-light rounded-3 text-muted small">
                    <i class="bi bi-arrow-up-circle fs-3 d-block mb-1"></i>
                    Silakan pilih kelas asal di atas untuk menampilkan daftar siswa.
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================================
     TAB 3: PANDUAN & FAQ SIKLUS TAHUNAN
     ========================================================================= -->
<?php if ($tab === 'riwayat'): ?>
<div class="card p-4 border-0 shadow-sm mb-4">
    <h5 class="fw-bold mb-3 text-dark">
        <i class="bi bi-question-circle-fill text-primary me-2"></i>Panduan &amp; Prosedur Tahun Ajaran Baru
    </h5>

    <div class="accordion" id="accordionFaq">
        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-xs">
            <h2 class="accordion-header">
                <button class="accordion-button fw-bold text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    1. Apakah data siswa yang lulus akan terhapus dari sistem?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#accordionFaq">
                <div class="accordion-body small text-muted">
                    <strong>TIDAK.</strong> Siswa yang diluluskan akan dipindahkan secara aman ke menu <strong>Arsip Alumni</strong>. Seluruh riwayat pelanggaran dan prestasi yang mereka peroleh selama 3 tahun bersekolah di SMKS Tiara Nusa tetap tersimpan utuh dan dapat dilihat kapan saja melalui menu Alumni maupun pencarian rekapitulasi.
                </div>
            </div>
        </div>

        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-xs">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-bold text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    2. Apa urutan yang benar saat pergantian tahun ajaran baru?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accordionFaq">
                <div class="accordion-body small text-muted">
                    Urutan yang direkomendasikan adalah:<br>
                    <strong>Langkah 1:</strong> Luluskan seluruh siswa Kelas XII di Tab <em>Kelulusan Kelas XII</em> sehingga rombel XII kosong.<br>
                    <strong>Langkah 2:</strong> Jalankan Kenaikan Kelas (XI naik ke XII, X naik ke XI). Sekarang rombel Kelas X menjadi kosong.<br>
                    <strong>Langkah 3:</strong> Masukkan data siswa baru tingkat X melalui menu <em>Data Siswa &rarr; Import Excel</em>.
                </div>
            </div>
        </div>

        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-xs">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-bold text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    3. Bagaimana jika ada salah satu siswa yang tinggal kelas (tidak naik)?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accordionFaq">
                <div class="accordion-body small text-muted">
                    Anda dapat menggunakan fitur <strong>Kenaikan Kelas Manual Per Rombel</strong> di Tab Kenaikan Kelas. Pada daftar siswa rombel tersebut, cukup hilangkan tanda centang pada nama siswa yang tidak naik kelas, lalu klik proses. Siswa yang tidak dicentang akan tetap berada di kelasnya semula.
                </div>
            </div>
        </div>

        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-xs">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-bold text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    4. Apakah alumni yang sudah lulus masih bisa diinput pelanggaran?
                </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#accordionFaq">
                <div class="accordion-body small text-muted">
                    <strong>Tidak.</strong> Siswa yang berstatus alumni secara otomatis dihilangkan dari daftar pilihan siswa di menu Input Pelanggaran maupun Input Prestasi, sehingga tidak akan terpilih secara tidak sengaja oleh Guru atau Petugas OSIS.
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Checkbox controller pada Tab Kelulusan
function updateSelectedCount() {
    const checked = document.querySelectorAll('.check-item:checked').length;
    const el = document.getElementById('countSelected');
    if (el) el.textContent = checked;
}

function toggleCheckAll(status) {
    const items = document.querySelectorAll('.check-item');
    items.forEach(cb => cb.checked = status);
    const master = document.getElementById('checkMaster');
    if (master) master.checked = status;
    updateSelectedCount();
}

function confirmLuluskan() {
    const checked = document.querySelectorAll('.check-item:checked').length;
    if (checked === 0) {
        alert('Pilih minimal satu siswa yang akan diluluskan.');
        return false;
    }
    return confirm('KONFIRMASI KELULUSAN:\n\nApakah Anda yakin ingin meluluskan ' + checked + ' siswa terpilih?\n\nSiswa yang diluluskan akan dipindahkan ke Arsip Alumni dan dinonaktifkan dari daftar siswa aktif.');
}

function confirmKenaikanSerentak() {
    return confirm('KONFIRMASI KENAIKAN KELAS SERENTAK:\n\nApakah Anda yakin ingin memproses kenaikan kelas untuk seluruh rombel tingkat X dan XI?\n\n- Siswa XI akan dinaikkan ke tingkat XII\n- Siswa X akan dinaikkan ke tingkat XI\n- Tingkat X akan dikosongkan untuk siswa baru');
}

// Handler pemilihan rombel manual
function loadSiswaRombel(kelasId) {
    const container = document.getElementById('containerRombelProses');
    const placeholder = document.getElementById('containerRombelPlaceholder');
    const inputId = document.getElementById('inputKelasAsalId');
    const listEl = document.getElementById('siswaRombelList');

    if (!kelasId) {
        container.style.display = 'none';
        placeholder.style.display = 'block';
        return;
    }

    inputId.value = kelasId;
    listEl.innerHTML = '<div class="text-center py-2 text-muted"><span class="spinner-border spinner-border-sm me-1"></span> Memuat data siswa rombel...</div>';
    container.style.display = 'block';
    placeholder.style.display = 'none';

    fetch('siswa.php?kelas=' + kelasId + '&ajax_list=1')
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                listEl.innerHTML = '<div class="text-muted small text-center">Tidak ada siswa aktif di kelas ini.</div>';
                return;
            }
            let html = '<div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">' +
                       '<span class="fw-bold small">Daftar Siswa (' + data.length + '):</span>' +
                       '<button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="toggleRombelCheckAll(true)">Pilih Semua</button>' +
                       '</div>';
            data.forEach((s, idx) => {
                html += '<div class="form-check small mb-1">' +
                        '<input class="form-check-input check-rombel" type="checkbox" name="siswa_ids[]" value="' + s.id + '" id="rb_' + s.id + '" checked>' +
                        '<label class="form-check-label" for="rb_' + s.id + '">' +
                        (idx + 1) + '. ' + s.nama + ' (' + (s.nisn || s.nis || '-') + ')' +
                        '</label></div>';
            });
            listEl.innerHTML = html;
        })
        .catch(err => {
            listEl.innerHTML = '<div class="text-danger small">Gagal memuat data siswa. Silakan refresh halaman.</div>';
        });
}

function toggleRombelCheckAll(status) {
    document.querySelectorAll('.check-rombel').forEach(cb => cb.checked = status);
}

document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php include 'includes/footer.php'; ?>
