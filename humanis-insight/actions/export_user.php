<?php
// actions/export_user.php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** @var PDO $pdo */

try {
    $stmt = $pdo->query("SELECT nama_lengkap, username, email, role, bidang FROM tb_users ORDER BY role, nama_lengkap");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data Pengguna');

    // Header
    $headers = ['Nama Lengkap', 'Username', 'Email', 'Role / Peran', 'Bidang Struktural'];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }

    // Data Rows
    $rowNum = 2;
    foreach ($users as $u) {
        $sheet->setCellValue('A' . $rowNum, $u['nama_lengkap']);
        $sheet->setCellValue('B' . $rowNum, $u['username']);
        $sheet->setCellValue('C' . $rowNum, $u['email'] ?? '-');
        $sheet->setCellValue('D' . $rowNum, $u['role']);
        $sheet->setCellValue('E' . $rowNum, $u['bidang']);
        $rowNum++;
    }

    foreach (range('A', 'E') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="data_pengguna_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    die("Gagal melakukan export: " . $e->getMessage());
}
