<?php
// actions/download_template_indikator.php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** @var PDO $pdo */
try {
    $listBidang = $pdo->query("SELECT Bidang FROM tb_bidang ORDER BY Bidang ASC")->fetchAll(PDO::FETCH_COLUMN);
    $listPic = $pdo->query("SELECT nama_lengkap FROM tb_users ORDER BY nama_lengkap ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $listBidang = ['Kurikulum', 'Kesiswaan'];
    $listPic = ['Operator'];
}

$spreadsheet = new Spreadsheet();

// ==========================================
// 1. SHEET UTAMA: TEMPLATE INDIKATOR
// ==========================================
Accessibility:
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Indikator');

$headers = ['Bidang', 'Indikator / Program', 'Bobot (%)', 'Target', 'Jenis Target', 'Satuan', 'Prioritas', 'Frekuensi', 'PIC'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $col++;
}

// Contoh Data Baris 2
$sheet->setCellValue('A2', !empty($listBidang) ? $listBidang[0] : 'Kurikulum');
$sheet->setCellValue('B2', 'Perencanaan pembelajaran (Modul Ajar/ATP)');
$sheet->setCellValue('C2', '10');
$sheet->setCellValue('D2', '100');
$sheet->setCellValue('E2', '=');
$sheet->setCellValue('F2', '%');
$sheet->setCellValue('G2', 'P3');
$sheet->setCellValue('H2', 'Semester');
$sheet->setCellValue('I2', !empty($listPic) ? $listPic[0] : 'Nama PIC');


// ==========================================
// 2. SHEET TERSEMBUNYI: DATA MASTER (PENCEGAH BUG KOMAC
// ==========================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('DataMaster');

// Masukkan data Bidang di Kolom A Sheet2
$rowNum = 1;
foreach ($listBidang as $b) {
    $sheet2->setCellValue('A' . $rowNum, $b);
    $rowNum++;
}

// Masukkan data PIC di Kolom B Sheet2
$rowNum = 1;
foreach ($listPic as $p) {
    $sheet2->setCellValue('B' . $rowNum, $p);
    $rowNum++;
}

// Sembunyikan sheet DataMaster agar rapi dan tidak mengganggu operator
$sheet2->setSheetState(Worksheet::SHEETSTATE_HIDDEN);


// ==========================================
// 3. PENERAPAN DATA VALIDATION (DROPDOWN)
// ==========================================

// A. Dropdown Bidang (Kolom A) - Merujuk ke Sheet DataMaster A1:An
if (!empty($listBidang)) {
    $valBidang = $sheet->getCell('A2')->getDataValidation();
    $valBidang->setType(DataValidation::TYPE_LIST);
    $valBidang->setErrorStyle(DataValidation::STYLE_STOP);
    $valBidang->setShowDropDown(true);
    $valBidang->setFormula1('DataMaster!$A$1:$A$' . count($listBidang));

    for ($i = 2; $i <= 500; $i++) {
        $sheet->getCell("A{$i}")->setDataValidation(clone $valBidang);
    }
}

// B. Dropdown Jenis Target (Kolom E)
$valJenis = $sheet->getCell('E2')->getDataValidation();
$valJenis->setType(DataValidation::TYPE_LIST);
$valJenis->setErrorStyle(DataValidation::STYLE_STOP);
$valJenis->setShowDropDown(true);
$valJenis->setFormula1('"=,≥,≤"');
for ($i = 2; $i <= 500; $i++) {
    $sheet->getCell("E{$i}")->setDataValidation(clone $valJenis);
}

// C. Dropdown Prioritas (Kolom G)
$valPrio = $sheet->getCell('G2')->getDataValidation();
$valPrio->setType(DataValidation::TYPE_LIST);
$valPrio->setErrorStyle(DataValidation::STYLE_STOP);
$valPrio->setShowDropDown(true);
$valPrio->setFormula1('"P1,P2,P3"');
for ($i = 2; $i <= 500; $i++) {
    $sheet->getCell("G{$i}")->setDataValidation(clone $valPrio);
}

// D. Dropdown Frekuensi (Kolom H)
$valFreq = $sheet->getCell('H2')->getDataValidation();
$valFreq->setType(DataValidation::TYPE_LIST);
$valFreq->setErrorStyle(DataValidation::STYLE_STOP);
$valFreq->setShowDropDown(true);
$valFreq->setFormula1('"Bulanan,Triwulan,Semester,Tahunan"');
for ($i = 2; $i <= 500; $i++) {
    $sheet->getCell("H{$i}")->setDataValidation(clone $valFreq);
}

// E. Dropdown PIC (Kolom I) - Merujuk ke Sheet DataMaster B1:Bn (Aman dari koma nama)
if (!empty($listPic)) {
    $valPic = $sheet->getCell('I2')->getDataValidation();
    $valPic->setType(DataValidation::TYPE_LIST);
    $valPic->setErrorStyle(DataValidation::STYLE_STOP);
    $valPic->setShowDropDown(true);
    $valPic->setFormula1('DataMaster!$B$1:$B$' . count($listPic));

    for ($i = 2; $i <= 500; $i++) {
        $sheet->getCell("I{$i}")->setDataValidation(clone $valPic);
    }
}

// Auto-size kolom agar rapi
foreach (range('A', 'I') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Output file ke browser untuk didownload
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_import_indikator.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
