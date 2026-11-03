<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Unauthorized');
}

$userId = $_SESSION['user_id'];
$tanggalHariIni = date('Y-m-d');

// Ambil data kelas wali
$stmt = $conn->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$kelasData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelasData) {
    exit('Bukan Wali Kelas');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Harian');

// Header Dokumen Excel
$sheet->setCellValue('A1', 'REKAP KEHADIRAN SISWA - HARI INI');
$sheet->setCellValue('A2', 'Kelas: ' . $kelasData['nama_kelas'] . ' | Tanggal: ' . date('d-m-Y'));

// Header Tabel
$sheet->setCellValue('A4', 'No');
$sheet->setCellValue('B4', 'NIS');
$sheet->setCellValue('C4', 'Nama Siswa');
$sheet->setCellValue('D4', 'Status');
$sheet->setCellValue('E4', 'Jam Datang');
$sheet->setCellValue('F4', 'Keterangan');

// Query data siswa & absensi harian berdasarkan ERD
$querySiswa = $conn->prepare("
    SELECT 
        s.nis, s.nama_siswa as nama_lengkap, 
        COALESCE(a.status, 'Belum ada keterangan') as status, 
        a.jam_datang, a.keterangan
    FROM siswa s
    LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal = ?
    WHERE s.kelas_id = ?
    ORDER BY s.nama_siswa ASC
");
$querySiswa->bind_param("si", $tanggalHariIni, $kelasData['id']);
$querySiswa->execute();
$result = $querySiswa->get_result();

$rowNum = 5;
$no = 1;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowNum, $no++);
    $sheet->setCellValue('B' . $rowNum, $row['nis']);
    $sheet->setCellValue('C' . $rowNum, $row['nama_lengkap']);
    $sheet->setCellValue('D' . $rowNum, $row['status']);
    $sheet->setCellValue('E' . $rowNum, $row['jam_datang'] ?? '-');
    $sheet->setCellValue('F' . $rowNum, $row['keterangan'] ?? '-');
    $rowNum++;
}

// Output file excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Rekap_Harian_' . $kelasData['nama_kelas'] . '_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;