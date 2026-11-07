<?php
// proses/import_master_siswa.php
require_once __DIR__ . '/../config/session.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- 1. DOWNLOAD TEMPLATE EXCEL ---
if ($action === 'template') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template Data Siswa');

    // Header Template dengan urutan baru
    $headers = [
        'A' => 'No',
        'B' => 'Nama Siswa',
        'C' => 'NIS',
        'D' => 'JK (L/P)',
        'E' => 'Kelas'
    ];

    foreach ($headers as $col => $title) {
        $sheet->setCellValue($col . '1', $title);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
    }

    // Contoh Data
    $sheet->setCellValue('A2', '1');
    $sheet->setCellValue('B2', 'Pedro Jose');
    // Set NIS secara eksplisit sebagai string agar angka 0 di depan tidak hilang
    $sheet->setCellValueExplicit('C2', '2627.00.000.0001', DataType::TYPE_STRING);
    $sheet->setCellValue('D2', 'L');
    $sheet->setCellValue('E2', 'X TJKT 1');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Master_Siswa.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// --- 2. PROSES IMPORT EXCEL ---
if ($action === 'import' && isset($_FILES['file_excel']['name'])) {
    $file_mimes = ['application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

    if (in_array($_FILES['file_excel']['type'], $file_mimes)) {
        try {
            $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO master_siswa (nis, nama, kelas, jenis_kelamin) VALUES (?, ?, ?, ?)");

            // Loop dari baris ke-2 (Baris 1 adalah Header)
            for ($i = 2; $i <= count($sheetData); $i++) {
                // Kolom A (No) diabaikan karena database menggunakan Auto Increment
                $nama    = trim($sheetData[$i]['B'] ?? '');
                $nis_raw = trim($sheetData[$i]['C'] ?? '');
                $jk      = trim($sheetData[$i]['D'] ?? '');
                $kelas   = trim($sheetData[$i]['E'] ?? '');

                if (!empty($nis_raw) && !empty($nama)) {
                    $nis_encrypt = encryptData($nis_raw);
                    // Pastikan JK sesuai format ENUM
                    $jk_valid = (strtolower($jk) == 'laki-laki' || strtolower($jk) == 'l') ? 'Laki-laki' : 'Perempuan';
                    $stmt->execute([$nis_encrypt, $nama, $kelas, $jk_valid]);
                }
            }

            $pdo->commit();
            header("Location: ../pages/bk/master_siswa.php?msg=imported");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: ../pages/bk/master_siswa.php?msg=error");
            exit;
        }
    } else {
        header("Location: ../pages/bk/master_siswa.php?msg=error");
        exit;
    }
}
