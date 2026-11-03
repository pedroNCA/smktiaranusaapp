<?php
// actions/export_indikator.php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** @var PDO $pdo */

try {
    $stmt = $pdo->query("SELECT bidang, indikator, bobot, target, jenis_target, satuan, prioritas, frekuensi, pic FROM tb_indikator ORDER BY bidang ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data Indikator');

    $headers = ['Bidang', 'Indikator / Program', 'Bobot (%)', 'Target', 'Jenis Target', 'Satuan', 'Prioritas', 'Frekuensi', 'PIC'];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }

    $rowNum = 2;
    foreach ($data as $d) {
        $sheet->setCellValue('A' . $rowNum, $d['bidang']);
        $sheet->setCellValue('B' . $rowNum, $d['indikator']);
        $sheet->setCellValue('C' . $rowNum, $d['bobot']);
        $sheet->setCellValue('D' . $rowNum, $d['target']);
        $sheet->setCellValue('E' . $rowNum, $d['jenis_target']);
        $sheet->setCellValue('F' . $rowNum, $d['satuan']);
        $sheet->setCellValue('G' . $rowNum, $d['prioritas']);
        $sheet->setCellValue('H' . $rowNum, $d['frekuensi']);
        $sheet->setCellValue('I' . $rowNum, $d['pic']);
        $rowNum++;
    }

    foreach (range('A', 'I') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="data_indikator_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    die("Gagal export: " . $e->getMessage());
}
