<?php
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Template Guru');

$sheet->setCellValue('A1', 'Nama Lengkap');
$sheet->setCellValue('B1', 'Username');
$sheet->setCellValue('C1', 'Password');

$sheet->setCellValue('A2', 'Ahmad Fauzi');
$sheet->setCellValue('B2', 'ahmad');
$sheet->setCellValue('C2', '123456');

$sheet->setCellValue('A3', 'Budi Santoso');
$sheet->setCellValue('B3', 'budi');
$sheet->setCellValue('C3', 'budi123');

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

$sheet->getStyle('A2:C100')->applyFromArray([
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

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Template_Import_Guru.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
