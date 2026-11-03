<?php
// actions/download_template_user.php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

/** @var PDO $pdo */

try {
    // Ambil data master bidang dari database
    $stmt = $pdo->query("SELECT Bidang FROM tb_bidang ORDER BY Bidang ASC");
    $listBidang = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $listBidang[] = 'Semua';
    $listBidang[] = 'Sistem';
    $listBidang = array_unique($listBidang);
} catch (Exception $e) {
    $listBidang = ['Semua', 'Sistem'];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template User');

// 1. HEADER TABEL (BARIS 1)
$headers = ['Nama Lengkap', 'Username', 'Email', 'Password', 'Role', 'Bidang'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $col++;
}

// 2. CONTOH DATA (BARIS 2)
$sheet->setCellValue('A2', 'Budi Santoso, S.Pd');
$sheet->setCellValue('B2', 'budi_guru');
$sheet->setCellValue('C2', 'budi@sekolah.sch.id');
$sheet->setCellValue('D2', 'P@ssw0rd123');
$sheet->setCellValue('E2', 'Guru/PIC');
$sheet->setCellValue('F2', 'Semua');

// ==========================================
// 3. DROPDOWN DATA VALIDATION (KOLOM BIDANG - F)
// ==========================================
if (count($listBidang) > 0) {
    $validation = $sheet->getCell('F2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(false);
    $validation->setShowDropDown(true);
    $validation->setErrorTitle('Input tidak valid');
    $validation->setError('Pilih nama bidang yang tersedia dari daftar dropdown.');
    $validation->setFormula1('"' . implode(',', $listBidang) . '"');

    // Terapkan ke bawah (sampai baris 500)
    for ($i = 2; $i <= 500; $i++) {
        $sheet->getCell("F{$i}")->setDataValidation(clone $validation);
    }
}

// Auto-size kolom agar rapi
foreach (range('A', 'F') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Output file ke browser untuk didownload
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_import_user.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
