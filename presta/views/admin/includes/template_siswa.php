<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Data Siswa');

$sheet->setCellValue('A1', 'NIS');
$sheet->setCellValue('B1', 'Nama Siswa');
$sheet->setCellValue('C1', 'Nama Kelas');

$sheet->setCellValue('A2', '240001');
$sheet->setCellValue('B2', 'Ahmad Fauzi');
$sheet->setCellValue('C2', '10 TJKT 1');

$sheet->setCellValue('A3', 'SMK-002');
$sheet->setCellValue('B3', 'Budi Santoso');
$sheet->setCellValue('C3', '10 MPLB 1');

$sheet->getStyle('A1:C1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '04B2D9'
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
]);

$sheet->getStyle('A2:C500')->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
]);

foreach (range('A', 'C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$sheet->freezePane('A2');

/*
|--------------------------------------------------------------------------
| Sheet Petunjuk
|--------------------------------------------------------------------------
*/

$petunjuk = $spreadsheet->createSheet();

$petunjuk->setTitle('Petunjuk');

$petunjuk->setCellValue('A1', 'PETUNJUK IMPORT DATA SISWA');

$petunjuk->setCellValue('A3', '1. Jangan mengubah nama header.');
$petunjuk->setCellValue('A4', '2. NIS bebas menggunakan angka, huruf maupun simbol.');
$petunjuk->setCellValue('A5', '3. Nama kelas harus sama persis dengan data pada menu Kelas.');
$petunjuk->setCellValue('A6', '4. Jangan menghapus baris header.');
$petunjuk->setCellValue('A7', '5. Simpan dalam format XLSX.');

$petunjuk->getStyle('A1')->getFont()->setBold(true);
$petunjuk->getStyle('A1')->getFont()->setSize(16);

$petunjuk->getColumnDimension('A')->setWidth(80);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Template_Import_Siswa.xlsx"');
header('Cache-Control:max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
