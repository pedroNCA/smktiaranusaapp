<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/laporan_helper.php';

Auth::guru();

$pageTitle = 'Laporan Absensi';
$userId = $_SESSION['user_id'];

$tahun   = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$kelas   = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$jenis   = $_GET['jenis'] ?? 'bulanan';
$periode = $_GET['periode'] ?? date('n');

/*
|--------------------------------------------------------------------------
| Tahun Ajaran
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
SELECT
    id,
    nama_tahun,
    tanggal_mulai,
    tanggal_selesai
FROM tahun_ajaran
ORDER BY tanggal_mulai DESC
");
$stmt->execute();
$listTahun = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$tahun && !empty($listTahun)) {
    $tahun = $listTahun[0]['id'];
}

/*
|--------------------------------------------------------------------------
| Kelas yang diampu wali kelas
|--------------------------------------------------------------------------
*/
$listKelas = [];
$stmt = $conn->prepare("
SELECT
    id,
    nama_kelas
FROM kelas
WHERE wali_kelas_id = ?
ORDER BY nama_kelas
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $listKelas[] = $row;
}
$stmt->close();

/*
|--------------------------------------------------------------------------
| Otomatis pilih kelas jika hanya satu
|--------------------------------------------------------------------------
*/
if (!$kelas && count($listKelas) == 1) {
    $kelas = $listKelas[0]['id'];
}

/*
|--------------------------------------------------------------------------
| Validasi kelas
|--------------------------------------------------------------------------
*/
if ($kelas) {
    $valid = false;
    foreach ($listKelas as $item) {
        if ($item['id'] == $kelas) {
            $valid = true;
            break;
        }
    }
    if (!$valid) {
        $kelas = count($listKelas) ? $listKelas[0]['id'] : 0;
    }
}

/*
|--------------------------------------------------------------------------
| Statistik awal
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
    SELECT COUNT(*) total
    FROM siswa
    WHERE kelas_id = ?
    ");
    $stmt->bind_param("i", $kelas);
    $stmt->execute();
    $totalSiswa = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// --- Memanggil Layout Global Header ---
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<style>
    .tailwind-scope {
        font-family: 'Poppins', sans-serif;
    }

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

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-2 text-white">
                    Laporan Absensi Kelas
                </h1>
                <p class="text-brand-100 text-sm md:text-base font-medium">
                    Rekap kehadiran siswa pada kelas yang Anda ampu
                </p>
            </div>
        </section>

        <!-- FILTER FORM -->
        <section class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm transform transition-all duration-700 delay-100 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <form id="formLaporan" method="GET">
                <input type="hidden" name="sumber_modul" value="laporan_guru">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Tahun Ajaran -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tahun Ajaran</label>
                        <select name="tahun"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all cursor-pointer">
                            <?php foreach ($listTahun as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $tahun == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nama_tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Kelas</label>
                        <select name="kelas"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all cursor-pointer">
                            <?php foreach ($listKelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= $kelas == $k['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Jenis Laporan -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Jenis Laporan</label>
                        <select name="jenis"
                            id="jenisLaporan"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all cursor-pointer">
                            <option value="bulanan" <?= $jenis == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                            <option value="semester" <?= $jenis == 'semester' ? 'selected' : '' ?>>Semester</option>
                            <option value="tahunan" <?= $jenis == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                        </select>
                    </div>

                    <!-- Periode -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Periode</label>
                        <select name="periode"
                            id="periode"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all cursor-pointer">
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
                                foreach ($bulan as $id => $nama) {
                                ?>
                                    <option value="<?= $id ?>" <?= $periode == $id ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-4 border-t border-gray-100 flex-wrap">
                    <button type="submit"
                        class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Tampilkan
                    </button>

                    <button type="button"
                        id="btnDownload"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Excel
                    </button>
                </div>
            </form>
        </section>

        <!-- STATISTIK GRID -->
        <section class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 transform transition-all duration-700 delay-150 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- Total Siswa -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Siswa</span>
                <div id="totalSiswa" class="text-xl font-extrabold text-gray-900"><?= number_format($totalSiswa) ?></div>
            </div>

            <!-- Hadir -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Hadir</span>
                <div id="totalHadir" class="text-xl font-extrabold text-emerald-600"><?= number_format($totalHadir) ?></div>
            </div>

            <!-- Izin -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Izin</span>
                <div id="totalIzin" class="text-xl font-extrabold text-amber-600"><?= number_format($totalIzin) ?></div>
            </div>

            <!-- Sakit -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Sakit</span>
                <div id="totalSakit" class="text-xl font-extrabold text-blue-600"><?= number_format($totalSakit) ?></div>
            </div>

            <!-- Alfa -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Alfa</span>
                <div id="totalAlfa" class="text-xl font-extrabold text-red-600"><?= number_format($totalAlfa) ?></div>
            </div>

            <!-- Terlambat -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Terlambat</span>
                <div id="totalTerlambat" class="text-xl font-extrabold text-orange-600"><?= number_format($totalTerlambat) ?></div>
            </div>

            <!-- Persentase Kehadiran -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Persentase</span>
                <div id="persentaseHadir" class="text-xl font-extrabold text-brand-600"><?= number_format($persentase, 2) ?>%</div>
            </div>

            <!-- Hari Efektif -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm col-span-1 md:col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Hari Efektif</span>
                <div id="hariEfektif" class="text-xl font-extrabold text-gray-900">0</div>
            </div>

        </section>

        <!-- GRAFIK & RINGKASAN -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- Grafik Kehadiran -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Grafik Kehadiran</h3>
                </div>
                <div class="flex-1 relative min-h-[250px]">
                    <canvas id="chartLaporan"></canvas>
                </div>
            </div>

            <!-- Ringkasan Laporan -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Ringkasan Laporan</h3>
                </div>
                <div id="reportSummary" class="space-y-4 flex-1 flex flex-col justify-center">
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Periode</span>
                        <strong class="text-gray-900">-</strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Kelas</span>
                        <strong class="text-gray-900">-</strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Tahun Ajaran</span>
                        <strong class="text-gray-900">-</strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Total Siswa</span>
                        <strong class="text-gray-900">0</strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2">
                        <span class="text-gray-500 font-medium">Status</span>
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 text-xs font-semibold rounded-lg border border-amber-200">
                            Belum dimuat
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- PREVIEW REKAP ABSENSI -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-300 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Preview Rekap Absensi</h3>
            </div>

            <div class="p-6 w-full">
                <div id="previewContainer" class="text-sm font-medium w-full">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-50 flex items-center justify-center text-gray-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    Pilih filter kemudian klik <strong class="text-gray-700">Tampilkan</strong> untuk melihat preview laporan.
                </div>
            </div>

        </section>

    </div>
</div>

<!-- DataTables JS & Chart.js -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let chartLaporan = null;

    function loadPeriode() {
        const jenis = $('#jenisLaporan').val();
        let html = '';

        if (jenis === 'bulanan') {
            const bulan = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            bulan.forEach(function(nama, index) {
                html += `<option value="${index+1}">${nama}</option>`;
            });
        } else if (jenis === 'semester') {
            html += `
                <option value="ganjil">Semester Ganjil</option>
                <option value="genap">Semester Genap</option>
            `;
        } else {
            html += `
                <option value="tahun">1 Tahun Pelajaran</option>
            `;
        }

        $('#periode').html(html);
    }

    $('#jenisLaporan').on('change', loadPeriode);

    $('#formLaporan').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            // UBAH URL MENJADI ROUTE API YANG BENAR:
            url: 'index.php?route=api/laporan',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#previewContainer').html(`
                    <div class="py-16 text-center text-gray-500 font-medium">
                        <svg class="w-6 h-6 animate-spin mx-auto mb-3 text-brand-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Memuat laporan...
                    </div>
                `);
            },
            success: function(res) {
                if (!res.success) {
                    alert(res.message);
                    return;
                }

                $('#previewContainer').html(res.preview);

                // Inisialisasi DataTables
                if ($('#previewContainer table').length > 0) {
                    $('#previewContainer table').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [10, 25, 50, 100],
                        "scrollX": true,
                        "autoWidth": false,
                        "language": {
                            "search": "Cari:",
                            "lengthMenu": "Tampilkan _MENU_ data per halaman",
                            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                            "infoEmpty": "Tidak ada data tersedia",
                            "zeroRecords": "Tidak ditemukan data yang sesuai",
                            "paginate": {
                                "first": "Pertama",
                                "last": "Terakhir",
                                "next": "Selanjutnya",
                                "previous": "Sebelumnya"
                            }
                        }
                    });
                }

                $('#reportSummary').html(res.summary);

                $('#totalSiswa').text(res.stats.total_siswa);
                $('#totalHadir').text(res.stats.hadir);
                $('#totalIzin').text(res.stats.izin);
                $('#totalSakit').text(res.stats.sakit);
                $('#totalAlfa').text(res.stats.alfa);
                $('#totalTerlambat').text(res.stats.terlambat);
                $('#persentaseHadir').text(res.stats.persentase + '%');
                $('#hariEfektif').text(res.stats.hari_efektif);

                renderChart(res.chart);
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                alert('Terjadi kesalahan pada server.');
            }
        });
    });

    function renderChart(data) {
        const ctx = document.getElementById('chartLaporan');

        if (chartLaporan) {
            chartLaporan.destroy();
        }

        chartLaporan = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alfa', 'Terlambat'],
                datasets: [{
                    label: 'Jumlah',
                    data: [
                        data.hadir,
                        data.izin,
                        data.sakit,
                        data.alfa,
                        data.terlambat
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#3b82f6',
                        '#ef4444',
                        '#f97316'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });
    }

    $('#btnDownload').on('click', function() {
        const query = $('#formLaporan').serialize();
        window.location = '../../export/export_laporan.php?' + query;
    });

    $(function() {
        $('#formLaporan').trigger('submit');
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>