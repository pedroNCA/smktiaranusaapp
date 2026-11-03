<?php

require_once '../config/database.php';

require_once '../app/Helpers/Auth.php';
require_once '../app/Services/DashboardService.php';

Auth::sekretaris();

$bulanIndonesia = [
    1 => 'JAN',
    2 => 'FEB',
    3 => 'MAR',
    4 => 'APR',
    5 => 'MEI',
    6 => 'JUN',
    7 => 'JUL',
    8 => 'AGU',
    9 => 'SEP',
    10 => 'OKT',
    11 => 'NOV',
    12 => 'DES'
];

$hariIndonesia = [
    'Sunday'    => 'Minggu',
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu'
];

$tanggal_hari_ini = date('Y-m-d');

$dashboard = DashboardService::dashboardSekretaris(
    $conn,
    Auth::id(),
    $tanggal_hari_ini
);

$kelas        = $dashboard['kelas'];
$stats        = $dashboard['stats'];
$workflow     = $dashboard['workflow'];
$totalSiswa   = $dashboard['total_siswa'];
$progress     = $dashboard['progress'];
$canInput     = $dashboard['can_input'];

$statusText = "Belum Mengisi";
$statusClass = "draft";

if ($workflow) {

    switch ($workflow['status']) {

        case 'sekretaris':
            $statusText = "Menunggu Guru Mapel";
            $statusClass = "sekretaris";
            break;

        case 'mapel':
            $statusText = "Menunggu Guru Piket";
            $statusClass = "mapel";
            break;

        case 'selesai':
            $statusText = "Presensi Selesai";
            $statusClass = "selesai";
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Input Kehadiran - PRESTA</title>
    <!-- Panggil file head modular -->
    <?php include 'includes/head.php'; ?>
</head>

<body class="app-layout">

    <!-- Memanggil Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Memanggil Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <div class="content-area">

            <!-- HERO -->

            <section class="sek-hero">

                <div class="hero-left">

                    <span class="hero-badge">
                        Dashboard Sekretaris
                    </span>

                    <h1>
                        Selamat Datang,
                        <?= htmlspecialchars(Auth::name()) ?>
                    </h1>

                    <p>

                        <?= htmlspecialchars($kelas['nama_kelas']) ?>

                        •

                        <?php

                        $namaBulan = [
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember'
                        ];

                        ?>

                        <?= date('d') . ' ' . $namaBulan[(int)date('n')] . ' ' . date('Y') ?>

                    </p>

                    <div class="hero-status">

                        <?php

                        $status = $dashboard['verification']['status'] ?? 'draft';

                        $statusClass = 'pending';
                        $statusText  = 'Belum Mengisi';

                        switch ($status) {

                            case 'sekretaris':
                                $statusClass = 'success';
                                $statusText  = 'Sudah Dikirim';
                                break;

                            case 'mapel':
                                $statusClass = 'info';
                                $statusText  = 'Diverifikasi Guru';
                                break;

                            case 'selesai':
                                $statusClass = 'done';
                                $statusText  = 'Selesai';
                                break;

                            default:
                                $statusClass = 'pending';
                                $statusText  = 'Belum Mengisi';
                        }

                        ?>

                        <span class="status-dot <?= $statusClass ?>"></span>

                        <?= $statusText ?>

                    </div>

                </div>

                <div class="hero-right">

                    <div class="today-card">

                        <span class="today-day">

                            <?= date('d') ?>

                        </span>

                        <span class="today-month">

                            <?= $bulanIndonesia[(int)date('n')] ?>

                        </span>

                        <small>

                            <?= $hariIndonesia[date('l')] ?>

                        </small>

                    </div>

                </div>

            </section>

            <!-- Statistik -->

            <section class="summary-grid">

                <div class="summary-card hadir">

                    <div class="icon">

                        ✓

                    </div>

                    <div>

                        <small>Hadir</small>

                        <h2><?= $stats['hadir'] ?></h2>

                    </div>

                </div>

                <div class="summary-card izin">

                    <div class="icon">

                        ⓘ

                    </div>

                    <div>

                        <small>Izin</small>

                        <h2><?= $stats['izin'] ?></h2>

                    </div>

                </div>

                <div class="summary-card sakit">

                    <div class="icon">

                        ❤

                    </div>

                    <div>

                        <small>Sakit</small>

                        <h2><?= $stats['sakit'] ?></h2>

                    </div>

                </div>

                <div class="summary-card alfa">

                    <div class="icon">

                        ✕

                    </div>

                    <div>

                        <small>Alfa</small>

                        <h2><?= $stats['alfa'] ?></h2>

                    </div>

                </div>

                <div class="summary-card telat">

                    <div class="icon">

                        ⏰

                    </div>

                    <div>

                        <small>Terlambat</small>

                        <h2><?= $stats['terlambat'] ?></h2>

                    </div>

                </div>

                <div class="summary-card belum">

                    <div class="icon">

                        …

                    </div>

                    <div>

                        <small>Belum Diisi</small>

                        <h2><?= $stats['belum'] ?></h2>

                    </div>

                </div>

            </section>

            <section class="info-grid">

                <div class="info-card">

                    <span>Kelas</span>

                    <h3>

                        <?= htmlspecialchars($kelas['nama_kelas']) ?>

                    </h3>

                </div>

                <div class="info-card">

                    <span>Wali Kelas</span>

                    <h3>

                        <?= htmlspecialchars($kelas['wali_kelas']) ?>

                    </h3>

                </div>

                <div class="info-card">

                    <span>Total Siswa</span>

                    <h3>

                        <?= number_format($totalSiswa) ?>

                        Siswa

                    </h3>

                </div>

            </section>

            <section class="workflow-card">

                <div class="workflow-header">

                    <div>

                        <h2>Progress Verifikasi</h2>

                        <p>
                            Alur verifikasi presensi kelas hari ini.
                        </p>

                    </div>

                    <span class="workflow-percent">

                        <?= $progress ?>%

                    </span>

                </div>

                <div class="workflow-progress">

                    <div
                        class="workflow-bar"
                        style="width: <?= $progress ?>%;">
                    </div>

                </div>

                <div class="workflow-steps">

                    <div class="step <?= $workflow['status'] == 'draft' || $workflow['status'] == 'sekretaris' || $workflow['status'] == 'mapel' || $workflow['status'] == 'selesai' ? 'active' : '' ?>">

                        <div class="circle">

                            ✓

                        </div>

                        <span>Sekretaris</span>

                    </div>

                    <div class="line <?= in_array($workflow['status'], ['mapel', 'selesai']) ? 'active' : '' ?>"></div>

                    <div class="step <?= in_array($workflow['status'], ['mapel', 'selesai']) ? 'active' : '' ?>">

                        <div class="circle">

                            ✓

                        </div>

                        <span>Guru Mapel</span>

                    </div>

                    <div class="line <?= $workflow['status'] == 'selesai' ? 'active' : '' ?>"></div>

                    <div class="step <?= $workflow['status'] == 'selesai' ? 'active' : '' ?>">

                        <div class="circle">

                            ✓

                        </div>

                        <span>Guru Piket</span>

                    </div>

                </div>

            </section>

            <section class="main-action">

                <a href="presensi.php" class="btn-presensi">

                    ▶ Mulai Presensi

                </a>

            </section>
    </main>

    <script>
        function toggleSidebar() {
            document
                .querySelector('.sidebar')
                .classList.toggle('active');
        }

        function toggleDropdown() {
            document
                .getElementById('userDropdown')
                .classList.toggle('show');
        }

        document.addEventListener('click', function(e) {

            const sidebar = document.querySelector('.sidebar');

            const toggle = document.querySelector('.menu-toggle');

            if (

                sidebar.classList.contains('active')

                &&

                !sidebar.contains(e.target)

                &&

                !toggle.contains(e.target)

            ) {

                sidebar.classList.remove('active');

            }

        });

        // Toggle Dropdown Profil
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }

        // Menutup dropdown profil jika klik di luar
        window.onclick = function(event) {
            if (!event.target.matches('.user-profile') && !event.target.matches('.avatar')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    if (dropdowns[i].classList.contains('show')) {
                        dropdowns[i].classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>

</html>