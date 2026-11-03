<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../../app/Helpers/laporan_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$tahun = isset($_POST['tahun']) ? (int)$_POST['tahun'] : 0;
$kelas = isset($_POST['kelas']) ? (int)$_POST['kelas'] : 0;
$jenis = $_POST['jenis'] ?? 'bulanan';
$periode = $_POST['periode'] ?? date('n');

if ($tahun === 0 || $kelas === 0) {
    echo json_encode([
        'jumlah_siswa' => 0,
        'hadir' => 0,
        'izin' => 0,
        'sakit' => 0,
        'alfa' => 0,
        'terlambat' => 0,
        'hari' => 0,
        'persen' => 0,
        'info_periode' => '-',
        'info_kelas' => '-',
        'info_tahun' => '-',
        'preview' => ''
    ]);
    exit;
}

$info = getInfoLaporan($conn, $tahun, $kelas);
$summary = getSummaryLaporan($conn, $tahun, $kelas, $jenis, $periode);
$rows = getLaporanHarian($conn, $tahun, $kelas, $jenis, $periode);

$tahunAjaran = getTahunAjaran($conn, $tahun);
if ($jenis === 'bulanan') {
    $tahunMulai = (int) date('Y', strtotime($tahunAjaran['tanggal_mulai']));
    $tahunSelesai = (int) date('Y', strtotime($tahunAjaran['tanggal_selesai']));
    $tahunPeriode = ((int)$periode >= 7) ? $tahunMulai : $tahunSelesai;
    $namaPeriode = getNamaPeriode('bulanan', $periode) . ' ' . $tahunPeriode;
} elseif ($jenis === 'semester') {
    $namaPeriode = getNamaPeriode('semester', $periode);
} else {
    $namaPeriode = $tahunAjaran['nama_tahun'];
}

$htmlPreview = '';
$no = 1;
foreach ($rows as $item) {
    $htmlPreview .= '<tr class="hover:bg-slate-50/60 transition-colors">';
    $htmlPreview .= '<td class="py-4 px-4 text-gray-500">' . $no++ . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-gray-900 font-bold">' . htmlspecialchars($item['nis']) . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-gray-900">' . htmlspecialchars($item['nama_siswa']) . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-center text-emerald-600 font-bold">' . $item['hadir'] . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-center text-blue-600">' . $item['izin'] . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-center text-purple-600">' . $item['sakit'] . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-center text-red-600">' . $item['alfa'] . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-center text-amber-600">' . $item['terlambat'] . '</td>';
    $htmlPreview .= '<td class="py-4 px-4 text-center font-bold text-gray-900">' . $item['persentase'] . '%</td>';
    $htmlPreview .= '</tr>';
}

echo json_encode([
    'jumlah_siswa' => $summary['jumlah_siswa'] ?? 0,
    'hadir' => $summary['hadir'] ?? 0,
    'izin' => $summary['izin'] ?? 0,
    'sakit' => $summary['sakit'] ?? 0,
    'alfa' => $summary['alfa'] ?? 0,
    'terlambat' => $summary['terlambat'] ?? 0,
    'hari' => $summary['hari_efektif'] ?? 0,
    'persen' => $summary['persen'] ?? 0,
    'info_periode' => $namaPeriode,
    'info_kelas' => $info['nama_kelas'] ?? '-',
    'info_tahun' => $info['nama_tahun'] ?? '-',
    'preview' => $htmlPreview
]);
exit;