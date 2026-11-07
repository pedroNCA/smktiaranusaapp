<?php
// proses/export_semua_jawaban.php

require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Pastikan autoloader composer dipanggil
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

try {
    // Ambil data siswa dari master_siswa digabung dengan hasil asesmen
    $stmt = $pdo->query("
        SELECT 
            ms.nama, ms.nis, ms.kelas, ms.jenis_kelamin, 
            ha.* 
        FROM master_siswa ms 
        JOIN hasil_asesmen ha ON ms.id = ha.siswa_id 
        ORDER BY ha.tanggal_pengisian DESC
    ");
    $data_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal memuat data: " . $e->getMessage());
}

// Inisiasi Spreadsheet Baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Asesmen');

// Definisi Header Kolom
$headers = [
    'A' => 'No',
    'B' => 'Tanggal Pengisian',
    'C' => 'Nama Lengkap',
    'D' => 'NIS',
    'E' => 'Kelas',
    'F' => 'Jenis Kelamin',
    'G' => 'Cita-cita',
    'H' => 'Kegiatan Favorit',
    'I' => 'Prestasi',
    'J' => 'Keterampilan',
    'K' => 'Dukungan',
    'L' => 'Akademik (M)',
    'M' => 'Sains & Tech (M)',
    'N' => 'Seni (M)',
    'O' => 'Bahasa (M)',
    'P' => 'Sosial (M)',
    'Q' => 'Olahraga (M)',
    'R' => 'Wirausaha (M)',
    'S' => 'Digital (M)',
    'T' => 'Kode Holland',
    'U' => 'Realistic (R)',
    'V' => 'Investigative (I)',
    'W' => 'Artistic (A)',
    'X' => 'Social (S)',
    'Y' => 'Enterprising (E)',
    'Z' => 'Conventional (C)'
];

// Set Nilai Header
foreach ($headers as $col => $title) {
    $sheet->setCellValue($col . '1', $title);
}

// Desain Tampilan Header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'], // Teks Putih
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF0D8ABC'], // Background Biru Primary
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A1:Z1')->applyFromArray($headerStyle);

// Masukkan Data Siswa
$rowNum = 2;
$no = 1;
foreach ($data_list as $row) {
    $sheet->setCellValue('A' . $rowNum, $no++);
    $sheet->setCellValue('B' . $rowNum, $row['tanggal_pengisian']);
    $sheet->setCellValue('C' . $rowNum, $row['nama']);

    // Format NIS wajib String agar 0 di depan tidak terpotong (misal 012345)
    $nis_terbaca = decryptData($row['nis']);
    $sheet->setCellValueExplicit('D' . $rowNum, $nis_terbaca, DataType::TYPE_STRING);

    $sheet->setCellValue('E' . $rowNum, $row['kelas']);
    $sheet->setCellValue('F' . $rowNum, $row['jenis_kelamin']);
    $sheet->setCellValue('G' . $rowNum, $row['cita_cita']);
    $sheet->setCellValue('H' . $rowNum, $row['kegiatan_favorit']);
    $sheet->setCellValue('I' . $rowNum, $row['prestasi']);
    $sheet->setCellValue('J' . $rowNum, $row['keterampilan']);
    $sheet->setCellValue('K' . $rowNum, $row['dukungan']);

    // Data Minat
    $sheet->setCellValue('L' . $rowNum, $row['m1_akademik'] ?? 0);
    $sheet->setCellValue('M' . $rowNum, $row['m2_sains'] ?? 0);
    $sheet->setCellValue('N' . $rowNum, $row['m3_seni'] ?? 0);
    $sheet->setCellValue('O' . $rowNum, $row['m4_bahasa'] ?? 0);
    $sheet->setCellValue('P' . $rowNum, $row['m5_sosial'] ?? 0);
    $sheet->setCellValue('Q' . $rowNum, $row['m6_olahraga'] ?? 0);
    $sheet->setCellValue('R' . $rowNum, $row['m7_wirausaha'] ?? 0);
    $sheet->setCellValue('S' . $rowNum, $row['m8_digital'] ?? 0);

    // Kode Holland
    $sheet->setCellValue('T' . $rowNum, $row['kode_holland']);
    $sheet->getStyle('T' . $rowNum)->getFont()->setBold(true);

    // Data RIASEC
    $sheet->setCellValue('U' . $rowNum, $row['r_realistic'] ?? 0);
    $sheet->setCellValue('V' . $rowNum, $row['i_investigative'] ?? 0);
    $sheet->setCellValue('W' . $rowNum, $row['a_artistic'] ?? 0);
    $sheet->setCellValue('X' . $rowNum, $row['s_social'] ?? 0);
    $sheet->setCellValue('Y' . $rowNum, $row['e_enterprising'] ?? 0);
    $sheet->setCellValue('Z' . $rowNum, $row['c_conventional'] ?? 0);

    // Atur perataan tengah untuk kolom angka dan tanggal
    $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('L' . $rowNum . ':Z' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $rowNum++;
}

// Terapkan Border Tipis ke Seluruh Data
if ($rowNum > 2) {
    $sheet->getStyle('A2:Z' . ($rowNum - 1))->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);
}

// Fitur Auto Size Kolom agar tidak ada teks yang menabrak batas
foreach (range('A', 'Z') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Eksekusi Output File ke Browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Rekap_Asesmen_Minat_Bakat_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0'); // Mencegah caching browser

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
