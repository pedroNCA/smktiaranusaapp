<?php
$currentRoute = $_GET['route'] ?? '';
$userRole = $_SESSION['role'] ?? 'sekretaris';

/*
|--------------------------------------------------------------------------
| Cek apakah guru adalah wali kelas (jika role guru)
|--------------------------------------------------------------------------
*/
$isWali = false;
if ($userRole === 'guru' && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
    if ($stmt) {
        $userId = $_SESSION['user_id'];
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $isWali = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    }
}

function activeMenu($routeTarget, $currentRoute)
{
    return $currentRoute === $routeTarget ? 'active' : '';
}
?>

<!-- CSS Khusus Sidebar & Overlay Mobile -->
<style>
    .sidebar-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 40;
        opacity: 0;
        pointer-events: none;
        /* Kunci agar tidak memblokir klik saat sidebar tertutup */
        transition: opacity 0.3s ease;
    }

    body.sidebar-open .sidebar-overlay {
        opacity: 1;
        pointer-events: auto;
        /* Aktifkan kembali tangkapan klik saat sidebar terbuka */
    }
</style>

<!-- Overlay untuk mobile, klik untuk menutup sidebar -->
<div class="sidebar-overlay" onclick="document.body.classList.remove('sidebar-open')"></div>

<aside class="sidebar z-50">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="assets/img/logo.png" alt="PRESTA">
            <div>
                <strong>PRESTA</strong>
                <small><?php echo ucfirst($userRole); ?> Panel</small>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <?php if ($userRole == 'sekretaris'): ?>
            <a href="index.php?route=sekretaris/dashboard" class="menu-item <?= activeMenu('sekretaris/dashboard', $currentRoute) ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="index.php?route=sekretaris/presensi" class="menu-item <?= activeMenu('sekretaris/presensi', $currentRoute) ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Input Kehadiran</span>
            </a>

        <?php elseif ($userRole == 'guru'): ?>
            <a href="index.php?route=guru/dashboard" class="menu-item <?= activeMenu('guru/dashboard', $currentRoute) ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>

            <?php if ($isWali): ?>
                <a href="index.php?route=guru/presensi_wali" class="menu-item <?= activeMenu('guru/presensi_wali', $currentRoute) ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Presensi Kelas Saya</span>
                </a>
            <?php endif; ?>

            <a href="index.php?route=guru/verifikasi" class="menu-item <?= activeMenu('guru/verifikasi', $currentRoute) ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Verifikasi Presensi</span>
            </a>

            <?php if ($isWali): ?>
                <a href="index.php?route=guru/rekap_hari_ini" class="menu-item <?= activeMenu('guru/rekap_hari_ini', $currentRoute) ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Rekap Hari Ini</span>
                </a>
                <a href="index.php?route=guru/laporan" class="menu-item <?= activeMenu('guru/laporan', $currentRoute) ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            <?php endif; ?>

        <?php elseif ($userRole == 'piket'): ?>
            <a href="index.php?route=piket/dashboard" class="menu-item <?= activeMenu('piket/dashboard', $currentRoute) ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="index.php?route=piket/monitoring" class="menu-item <?= activeMenu('piket/monitoring', $currentRoute) ?>">
                <i class="fas fa-binoculars"></i>
                <span>Monitoring Kelas</span>
            </a>
            <!-- Menu Presensi Guru Piket (Seluruh Kelas Tanpa Batas Waktu) -->
            <a href="index.php?route=piket/presensi" class="menu-item <?= (strpos($currentRoute, 'piket/presensi') !== false) ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i>
                <span>Presensi Kelas</span>
            </a>

            <!-- Menu Finalisasi Presensi -->
            <a href="index.php?route=piket/finalisasi"
                class="menu-item <?= (strpos($currentRoute, 'piket/finalisasi') !== false) ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i>
                <span>Finalisasi</span>
            </a>
        <?php endif; ?>
    </nav>
</aside>

<script>
    // Otomatis menutup sidebar saat menu diklik di perangkat mobile
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                document.body.classList.remove('sidebar-open');
            }
        });
    });
</script>