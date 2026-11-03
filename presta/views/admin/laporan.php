<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';

// Keamanan: Pastikan yang akses adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php?route=auth/login");
    exit;
}

$pesan = '';

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$tahun_ajaran = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$jenis = $_GET['jenis'] ?? 'bulanan';
$periode = $_GET['periode'] ?? date('n');

if ($tahun_ajaran == 0) {
    $q = $conn->query("
    SELECT id
    FROM tahun_ajaran
    WHERE status='Aktif'
    LIMIT 1
    ");

    if ($q && $q->num_rows) {
        $tahun_ajaran = $q->fetch_assoc()['id'];
    }
}

/*
|--------------------------------------------------------------------------
| LIST TAHUN AJARAN
|--------------------------------------------------------------------------
*/

$listTahun = [];
$q = $conn->query("
SELECT
id,
nama_tahun,
status
FROM tahun_ajaran
ORDER BY id DESC
");

if ($q) {
    while ($r = $q->fetch_assoc()) {
        $listTahun[] = $r;
    }
}

/*
|--------------------------------------------------------------------------
| LIST KELAS
|--------------------------------------------------------------------------
*/

$listKelas = [];
$q = $conn->query("
SELECT
id,
nama_kelas
FROM kelas
ORDER BY nama_kelas
");

if ($q) {
    while ($r = $q->fetch_assoc()) {
        $listKelas[] = $r;
    }
}

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

$totalSiswa = 0;
$totalHadir = 0;
$totalIzin = 0;
$totalSakit = 0;
$totalAlfa = 0;
$totalTerlambat = 0;
$persentase = 0;

if ($kelas) {
    $stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM siswa
    WHERE kelas_id=?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $kelas);
        $stmt->execute();
        $totalSiswa = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi - PRESTA Admin</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper {
            width: 100% !important;
        }
        .dataTables_wrapper table.dataTable {
            width: 100% !important;
            margin: 0 !important;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            outline: none;
            background-color: #fff;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #2563eb !important;
            color: white !important;
            border: 1px solid #2563eb !important;
            border-radius: 0.5rem !important;
        }
    </style>
</head>

<body class="bg-slate-50 antialiased text-gray-800">

    <!-- Wrapper Utama Layout Admin -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <!-- Konten Kanan (Topbar + Isi Halaman) -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Topbar -->
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <!-- Area Konten Utama -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Header Halaman -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900">Laporan Absensi Siswa</h2>
                        <p class="text-xs text-gray-500 mt-1">Laporan kehadiran siswa berdasarkan kelas dan periode.</p>
                    </div>

                    <!-- Filter Card -->
                    <form id="formLaporan" method="GET" class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tahun Ajaran</label>
                                <select name="tahun" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white cursor-pointer">
                                    <?php foreach ($listTahun as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $tahun_ajaran == $t['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['nama_tahun']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kelas</label>
                                <select name="kelas" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white cursor-pointer">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($listKelas as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= $kelas == $k['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kelas']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Jenis Laporan</label>
                                <select name="jenis" id="jenisLaporan" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white cursor-pointer">
                                    <option value="bulanan" <?= $jenis == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                                    <option value="semester" <?= $jenis == 'semester' ? 'selected' : '' ?>>Semester</option>
                                    <option value="tahunan" <?= $jenis == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Periode</label>
                                <select name="periode" id="periode" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white cursor-pointer">
                                    <?php
                                    if ($jenis == 'semester') {
                                    ?>
                                        <option value="ganjil" <?= $periode == 'ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                                        <option value="genap" <?= $periode == 'genap' ? 'selected' : '' ?>>Semester Genap</option>
                                    <?php
                                    } elseif ($jenis == 'tahunan') {
                                    ?>
                                        <option value="tahun">1 Tahun Pelajaran</option>
                                    <?php
                                    } else {
                                        $bulan = [
                                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                        ];
                                        foreach ($bulan as $no => $nama) {
                                    ?>
                                            <option value="<?= $no ?>" <?= $periode == $no ? 'selected' : '' ?>><?= $nama ?></option>
                                    <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <button type="button" id="btnDownload" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fas fa-file-excel"></i> Download Excel
                            </button>
                        </div>
                    </form>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!--<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Siswa</span>
                            <h2 class="text-3xl font-bold text-gray-900 mt-2" id="totalSiswa"><?= number_format($totalSiswa) ?></h2>
                        </div>-->
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Hadir</span>
                            <h2 class="text-3xl font-bold text-emerald-600 mt-2" id="totalHadir"><?= number_format($totalHadir) ?></h2>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Izin</span>
                            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="totalIzin"><?= number_format($totalIzin) ?></h2>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Sakit</span>
                            <h2 class="text-3xl font-bold text-purple-600 mt-2" id="totalSakit"><?= number_format($totalSakit) ?></h2>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Alfa</span>
                            <h2 class="text-3xl font-bold text-red-600 mt-2" id="totalAlfa"><?= number_format($totalAlfa) ?></h2>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Terlambat</span>
                            <h2 class="text-3xl font-bold text-amber-600 mt-2" id="totalTerlambat"><?= number_format($totalTerlambat) ?></h2>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Persentase Kehadiran</span>
                            <h2 class="text-3xl font-bold text-gray-900 mt-2" id="persentaseHadir"><?= number_format($persentase, 2) ?>%</h2>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Hari Efektif</span>
                            <h2 class="text-3xl font-bold text-gray-900 mt-2" id="hariEfektif">0</h2>
                        </div>
                    </div>

                    <!-- Grafik Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div>
                            <h4 class="font-bold text-gray-900 text-base">Grafik Kehadiran</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Statistik kehadiran berdasarkan filter yang dipilih.</p>
                        </div>
                        <div class="h-80 w-full">
                            <canvas id="grafikKehadiran"></canvas>
                        </div>
                    </div>

                    <!-- Preview Rekap Absensi dengan DataTables & Pagination -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div>
                            <h4 class="font-bold text-gray-900 text-base">Preview Rekap Absensi</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Data rekapitulasi lengkap dengan pagination interaktif.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="previewLaporan" class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-4" width="60">No</th>
                                        <th class="py-3 px-4" width="150">NIS</th>
                                        <th class="py-3 px-4">Nama Siswa</th>
                                        <th class="py-3 px-4 text-center" width="70">H</th>
                                        <th class="py-3 px-4 text-center" width="70">I</th>
                                        <th class="py-3 px-4 text-center" width="70">S</th>
                                        <th class="py-3 px-4 text-center" width="70">A</th>
                                        <th class="py-3 px-4 text-center" width="70">T</th>
                                        <th class="py-3 px-4 text-center" width="90">%</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyPreview" class="divide-y divide-gray-50 text-sm font-medium">
                                    <tr>
                                        <td class="py-4 px-4 text-gray-500">1</td>
                                        <td class="py-4 px-4 text-gray-900 font-bold">-</td>
                                        <td class="py-4 px-4 text-gray-500 italic">Silakan pilih kelas dan klik tampilkan</td>
                                        <td class="py-4 px-4 text-center text-gray-400">0</td>
                                        <td class="py-4 px-4 text-center text-gray-400">0</td>
                                        <td class="py-4 px-4 text-center text-gray-400">0</td>
                                        <td class="py-4 px-4 text-center text-gray-400">0</td>
                                        <td class="py-4 px-4 text-center text-gray-400">0</td>
                                        <td class="py-4 px-4 text-center text-gray-400 font-bold">0%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ringkasan Laporan Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div>
                            <h4 class="font-bold text-gray-900 text-base">Ringkasan Laporan</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Informasi yang akan dicetak ke Excel.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 rounded-xl border border-gray-100 bg-slate-50">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Periode</span>
                                <div class="text-sm font-bold text-gray-900 mt-1" id="infoPeriode">-</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-slate-50">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Kelas</span>
                                <div class="text-sm font-bold text-gray-900 mt-1" id="infoKelas">-</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-slate-50">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Tahun Ajaran</span>
                                <div class="text-sm font-bold text-gray-900 mt-1" id="infoTahun">-</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-slate-50">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Status Export</span>
                                <div class="mt-1">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200" id="statusExport">
                                        Belum dibuat
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?= $pesan ?>

    <!-- Chart.js & DataTables JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        let chartKehadiran = null;
        let dataTablePreview = null;

        $(document).ready(function() {
            initChart();
            togglePeriode(false);

            dataTablePreview = $('#previewLaporan').DataTable({
                responsive: true,
                pageLength: 10,
                autoWidth: false,
                language: {
                    search: "Cari Siswa :",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    zeroRecords: "Data tidak ditemukan",
                    infoEmpty: "Tidak ada data",
                    paginate: {
                        previous: "←",
                        next: "→"
                    }
                }
            });

            $('#jenisLaporan').on('change', function() {
                togglePeriode(true);
            });
        });

        $('#formLaporan').on('submit', function(e) {
            e.preventDefault();
            let kelas = $('[name=kelas]').val();

            if (kelas == "") {
                Swal.fire('Peringatan', 'Silakan pilih kelas terlebih dahulu.', 'warning');
                return;
            }

            loadDashboard();
        });

        $('#btnDownload').click(function() {
            let tahun = $('[name=tahun]').val();
            let kelas = $('[name=kelas]').val();
            let jenis = $('[name=jenis]').val();
            let periode = $('[name=periode]').val();

            if (kelas == "") {
                Swal.fire('Peringatan', 'Silakan pilih kelas terlebih dahulu.', 'warning');
                return;
            }

            window.location = 'index.php?route=admin/export_laporan&tahun=' + tahun + '&kelas=' + kelas + '&jenis=' + jenis + '&periode=' + periode;
        });

        function initChart() {
            const ctx = document.getElementById('grafikKehadiran').getContext('2d');
            chartKehadiran = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Hadir', 'Izin', 'Sakit', 'Alfa', 'Terlambat'],
                    datasets: [{
                        label: 'Jumlah',
                        data: [0, 0, 0, 0, 0],
                        backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        function updateChart(hadir, izin, sakit, alfa, terlambat) {
            chartKehadiran.data.datasets[0].data = [hadir, izin, sakit, alfa, terlambat];
            chartKehadiran.update();
        }

        function togglePeriode(reset = false) {
            const jenis = $('#jenisLaporan').val();
            const current = $('#periode').val();
            let html = '';

            if (jenis === 'bulanan') {
                const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                bulan.forEach(function(nama, index) {
                    const value = index + 1;
                    html += `<option value="${value}">${nama}</option>`;
                });
            }

            if (jenis === 'semester') {
                html += `<option value="ganjil">Semester Ganjil</option><option value="genap">Semester Genap</option>`;
            }

            if (jenis === 'tahunan') {
                html += `<option value="tahun">1 Tahun Pelajaran</option>`;
            }

            $('#periode').html(html);

            if (!reset) {
                $('#periode').val(current);
            }
        }

        function loadDashboard() {
            let tahun = $('[name=tahun]').val();
            let kelas = $('[name=kelas]').val();
            let jenis = $('[name=jenis]').val();
            let periode = $('[name=periode]').val();

            if (kelas == "") {
                Swal.fire('Peringatan', 'Silakan pilih kelas.', 'warning');
                return;
            }

            $.ajax({
                url: 'index.php?route=admin/ajax_laporan',
                type: 'POST',
                dataType: 'json',
                data: {
                    tahun: tahun,
                    kelas: kelas,
                    jenis: jenis,
                    periode: periode
                },
                beforeSend: function() {
                    $('#btnDownload').prop('disabled', true);
                    if (dataTablePreview) {
                        dataTablePreview.clear().draw();
                    }
                    $('#bodyPreview').html(`
                        <tr>
                            <td colspan="9" class="text-center py-8 text-gray-400">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                            </td>
                        </tr>
                    `);
                },
                success: function(r) {
                    $('#btnDownload').prop('disabled', false);

                    $('#totalSiswa').text(r.jumlah_siswa);
                    $('#totalHadir').text(r.hadir);
                    $('#totalIzin').text(r.izin);
                    $('#totalSakit').text(r.sakit);
                    $('#totalAlfa').text(r.alfa);
                    $('#totalTerlambat').text(r.terlambat);

                    $('#hariEfektif').text(r.hari);
                    $('#persentaseHadir').text(r.persen + '%');

                    $('#infoPeriode').text(r.info_periode);
                    $('#infoKelas').text(r.info_kelas);
                    $('#infoTahun').text(r.info_tahun);

                    $('#statusExport')
                        .removeClass('bg-amber-50 text-amber-700 border-amber-200')
                        .addClass('bg-emerald-50 text-emerald-700 border-emerald-200')
                        .text('Siap Download');

                    updateChart(r.hadir, r.izin, r.sakit, r.alfa, r.terlambat);

                    if ($.fn.DataTable.isDataTable('#previewLaporan')) {
                        $('#previewLaporan').DataTable().destroy();
                    }

                    if ($.trim(r.preview) === '') {
                        $('#bodyPreview').html(`
                            <tr>
                                <td colspan="9" class="text-center py-8 text-gray-400">
                                    Tidak ada data.
                                </td>
                            </tr>
                        `);
                    } else {
                        $('#bodyPreview').html(r.preview);
                    }

                    dataTablePreview = $('#previewLaporan').DataTable({
                        responsive: true,
                        pageLength: 10,
                        autoWidth: false,
                        language: {
                            search: "Cari Siswa :",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            zeroRecords: "Data tidak ditemukan",
                            infoEmpty: "Tidak ada data",
                            paginate: {
                                previous: "←",
                                next: "→"
                            }
                        }
                    });
                },
                error: function(xhr) {
                    $('#btnDownload').prop('disabled', false);
                    console.log(xhr.responseText);
                    $('#bodyPreview').html(`
                        <tr>
                            <td colspan="9" class="text-center py-8 text-red-500">
                                Gagal mengambil data.
                            </td>
                        </tr>
                    `);
                    Swal.fire('Error', 'Gagal mengambil data laporan.', 'error');
                }
            });
        }
    </script>

</body>

</html>