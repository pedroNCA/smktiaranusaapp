<?php 
$halaman_ini = basename($_SERVER['PHP_SELF']); 
$role_user = $_SESSION['role'] ?? '';
$nama_user = $_SESSION['nama_lengkap'] ?? 'Pengguna';
?>
<div id="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand-card">
        <div class="d-flex align-items-center gap-3">
            <div class="brand-logo-ring">
                <img src="assets/logo_tinus.jpg" alt="Logo SMKS Tiara Nusa" class="brand-logo-img">
            </div>
            <div class="brand-info">
                <div class="brand-name">SMKS TIARA NUSA</div>
                <div class="brand-subtag">
                    <span class="pulse-indicator"></span>
                    <span>HUMANIS &bull; E-POINT</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu Stream -->
    <div class="sidebar-nav-container">
        
        <!-- SECTION: IKHTISAR -->
        <div class="sidebar-section-label">Ikhtisar</div>
        <a href="index.php" class="sidebar-nav-item <?= $halaman_ini=='index.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-grid-1x2-fill"></i></div>
            <span class="nav-label">Dashboard Utama</span>
        </a>

        <!-- SECTION: KESISWAAN -->
        <div class="sidebar-section-label">Layanan Kesiswaan</div>
        <a href="siswa.php" class="sidebar-nav-item <?= $halaman_ini=='siswa.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-people-fill"></i></div>
            <span class="nav-label">Data Siswa</span>
        </a>
        <?php if ($role_user !== 'osis'): ?>
        <a href="alumni.php" class="sidebar-nav-item <?= $halaman_ini=='alumni.php'?'active':'' ?>">
            <div class="nav-icon-box" style="background: rgba(245, 158, 11, 0.15); color: #d97706;"><i class="bi bi-mortarboard-fill"></i></div>
            <span class="nav-label">Arsip Alumni</span>
        </a>
        <?php endif; ?>

        <?php if ($role_user !== 'osis'): ?>
        <a href="riwayat_siswa.php" class="sidebar-nav-item <?= $halaman_ini=='riwayat_siswa.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-person-lines-fill"></i></div>
            <span class="nav-label">Riwayat per Siswa</span>
            <span class="nav-badge-pill">Fitur Utama</span>
        </a>
        <?php endif; ?>

        <?php if (in_array($role_user, ['admin', 'guru_bk'])): ?>
        <a href="kelulusan_kenaikan.php" class="sidebar-nav-item <?= $halaman_ini=='kelulusan_kenaikan.php'?'active':'' ?>">
            <div class="nav-icon-box" style="background: rgba(147, 51, 234, 0.15); color: #9333ea;"><i class="bi bi-arrow-repeat"></i></div>
            <span class="nav-label">Kenaikan &amp; Kelulusan</span>
        </a>
        <?php endif; ?>

        <?php if ($role_user !== 'kepala_sekolah'): ?>
        <a href="input_pelanggaran.php" class="sidebar-nav-item <?= $halaman_ini=='input_pelanggaran.php'?'active':'' ?>">
            <div class="nav-icon-box nav-danger"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <span class="nav-label">Input Pelanggaran</span>
        </a>
        <?php endif; ?>

        <?php if (!in_array($role_user, ['kepala_sekolah', 'osis'])): ?>
        <a href="input_prestasi.php" class="sidebar-nav-item <?= $halaman_ini=='input_prestasi.php'?'active':'' ?>">
            <div class="nav-icon-box nav-success"><i class="bi bi-award-fill"></i></div>
            <span class="nav-label">Input Prestasi</span>
        </a>
        <?php endif; ?>

        <!-- SECTION: REKAPITULASI -->
        <?php if ($role_user !== 'osis'): ?>
        <div class="sidebar-section-label">Rekapitulasi</div>
        <a href="laporan.php" class="sidebar-nav-item <?= $halaman_ini=='laporan.php'?'active':'' ?>">
            <div class="nav-icon-box nav-info"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
            <span class="nav-label">Laporan Rekap</span>
        </a>
        <?php endif; ?>

        <!-- SECTION: ADMINISTRASI (ADMIN ONLY) -->
        <?php if ($role_user === 'admin'): ?>
        <div class="sidebar-section-label">Administrasi Sistem</div>
        <a href="guru.php" class="sidebar-nav-item <?= $halaman_ini=='guru.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-person-badge-fill"></i></div>
            <span class="nav-label">Data Akun Guru</span>
        </a>
        <a href="kelas.php" class="sidebar-nav-item <?= $halaman_ini=='kelas.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-building-fill"></i></div>
            <span class="nav-label">Data Kelas</span>
        </a>
        <a href="kategori_pelanggaran.php" class="sidebar-nav-item <?= $halaman_ini=='kategori_pelanggaran.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-shield-slash-fill"></i></div>
            <span class="nav-label">Master Pelanggaran</span>
        </a>
        <a href="kategori_prestasi.php" class="sidebar-nav-item <?= $halaman_ini=='kategori_prestasi.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-trophy-fill"></i></div>
            <span class="nav-label">Master Prestasi</span>
        </a>
        <a href="users.php" class="sidebar-nav-item <?= $halaman_ini=='users.php'?'active':'' ?>">
            <div class="nav-icon-box"><i class="bi bi-gear-wide-connected"></i></div>
            <span class="nav-label">Kelola User</span>
        </a>
        <a href="pengaturan_telegram.php" class="sidebar-nav-item <?= $halaman_ini=='pengaturan_telegram.php'?'active':'' ?>">
            <div class="nav-icon-box" style="background: rgba(0, 136, 204, 0.15); color: #0088cc;"><i class="bi bi-telegram"></i></div>
            <span class="nav-label">Bot Telegram</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.68rem;">Notif</span>
        </a>
        <?php endif; ?>

    </div>

    <!-- Sidebar Bottom User Card -->
    <div class="sidebar-footer-card">
        <div class="d-flex align-items-center gap-2">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($nama_user, 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name" title="<?= htmlspecialchars($nama_user) ?>"><?= htmlspecialchars($nama_user) ?></div>
                <div class="sidebar-user-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role_user))) ?></div>
            </div>
        </div>
    </div>
</div>
