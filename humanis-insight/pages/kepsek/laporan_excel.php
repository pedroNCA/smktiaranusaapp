<?php
// TANGKAP SEMUA OUTPUT SEJAK BARIS PERTAMA (Mencegah File Excel Corrupt)
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

/**
 * Skrip Export Rekapitulasi & Detail Kinerja ke Format .xlsx Resmi menggunakan PhpSpreadsheet
 * Diperbarui: Penambahan kolom "Realisasi Aktual" di Sheet Detail
 */
require_once '../../config/database.php';
require_once '../../includes/app_config.php'; // Ambil data sekolah dinamis
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** @var PDO $pdo */
$nama_sekolah = $app_data['nama_sekolah'] ?? 'SMK Tiara Nusa';

try {
    // 1. QUERY REKAPITULASI (PER BIDANG)
    // Menggunakan LEFT JOIN dengan laporan terakhir agar akurat
    $stmtRekap = $pdo->query("
        SELECT 
            i.bidang, 
            COUNT(DISTINCT i.id_indikator) as total_indikator,
            COUNT(p.id_pelaporan) as total_laporan,
            AVG(COALESCE(p.capaian, 0)) as rata_capaian
        FROM tb_indikator i
        LEFT JOIN (
            SELECT id_indikator, MAX(id_pelaporan) as max_id 
            FROM tb_pelaporan 
            GROUP BY id_indikator
        ) p_max ON i.id_indikator = p_max.id_indikator
        LEFT JOIN tb_pelaporan p ON p_max.max_id = p.id_pelaporan
        GROUP BY i.bidang 
        ORDER BY i.bidang ASC
    ");
    $rekapData = $stmtRekap->fetchAll(PDO::FETCH_ASSOC);

    // 2. QUERY DETAIL KESELURUHAN (SEMUA KPI, BUKTI, KENDALA & TINDAK LANJUT)
    // Ditambahkan: p.realisasi
    $stmtDetail = $pdo->query("
        SELECT 
            i.bidang, i.pic, i.indikator, i.target, i.satuan,
            p.capaian, p.realisasi, p.nilai_humanis, p.status_verifikasi, p.bukti_gdrive, 
            p.refleksi_kendala, p.rencana_tindak_lanjut, p.arahan_kepsek
        FROM tb_indikator i
        LEFT JOIN (
            SELECT p1.* 
            FROM tb_pelaporan p1
            INNER JOIN (
                SELECT id_indikator, MAX(id_pelaporan) as max_id 
                FROM tb_pelaporan 
                GROUP BY id_indikator
            ) p2 ON p1.id_pelaporan = p2.max_id
        ) p ON i.id_indikator = p.id_indikator
        ORDER BY i.bidang ASC, i.pic ASC
    ");
    $detailData = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rekapData = [];
    $detailData = [];
}

// Inisialisasi Spreadsheet baru
$spreadsheet = new Spreadsheet();

// =========================================================================
// SHEET 1 : REKAPITULASI PER BIDANG & KESELURUHAN
// =========================================================================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Rekap Per Bidang');

// Styling Header Judul Sheet 1
$sheet1->mergeCells('A1:F1');
$sheet1->setCellValue('A1', 'REKAPITULASI KINERJA SEKOLAH - ' . strtoupper($nama_sekolah));
$sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet1->mergeCells('A2:F2');
$sheet1->setCellValue('A2', 'Periode Tahun Ajaran ' . htmlspecialchars($app_data['tahun_ajaran'] ?? (date('Y') . '/' . date('Y', strtotime('+1 year')))) . ' (Humanis Insight)');
$sheet1->getStyle('A2')->getFont()->setSize(10)->setItalic(true);
$sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header Kolom Tabel Rekap
$headers1 = ['No', 'Bidang Struktural', 'Total Indikator', 'Laporan Masuk', 'Rata-rata Capaian', 'Status Mutu'];
$sheet1->fromArray($headers1, NULL, 'A4');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1D4ED8'] // Warna Primary Blue
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheet1->getStyle('A4:F4')->applyFromArray($headerStyle);
$sheet1->getRowDimension(4)->setRowHeight(25);

// Masukkan Data Rekap
$rowNum = 5;
$no = 1;
$total_all_indikator = 0;
$total_all_laporan = 0;
$sum_capaian = 0;
$count_bidang = 0;

if (!empty($rekapData)) {
    foreach ($rekapData as $row) {
        $avg = round((float)$row['rata_capaian'], 2);
        $statusMutu = $avg >= 85 ? 'Optimal (Tinggi)' : ($avg >= 50 ? 'Cukup (Sedang)' : 'Perlu Atensi (Rendah)');

        $sheet1->setCellValue('A' . $rowNum, $no++);
        $sheet1->setCellValue('B' . $rowNum, $row['bidang']);
        $sheet1->setCellValue('C' . $rowNum, $row['total_indikator']);
        $sheet1->setCellValue('D' . $rowNum, $row['total_laporan']);
        $sheet1->setCellValue('E' . $rowNum, $avg . '%');
        $sheet1->setCellValue('F' . $rowNum, $statusMutu);

        $sheet1->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Untuk kalkulasi total keseluruhan sekolah
        $total_all_indikator += $row['total_indikator'];
        $total_all_laporan += $row['total_laporan'];
        $sum_capaian += $avg;
        $count_bidang++;

        $rowNum++;
    }

    // Baris Total Keseluruhan
    $sheet1->mergeCells('A' . $rowNum . ':B' . $rowNum);
    $sheet1->setCellValue('A' . $rowNum, 'TOTAL KESELURUHAN (RATA-RATA SEKOLAH)');
    $sheet1->setCellValue('C' . $rowNum, $total_all_indikator);
    $sheet1->setCellValue('D' . $rowNum, $total_all_laporan);

    $avg_sekolah = $count_bidang > 0 ? round($sum_capaian / $count_bidang, 2) : 0;
    $statusSekolah = $avg_sekolah >= 85 ? 'Optimal (Tinggi)' : ($avg_sekolah >= 50 ? 'Cukup (Sedang)' : 'Perlu Atensi (Rendah)');

    $sheet1->setCellValue('E' . $rowNum, $avg_sekolah . '%');
    $sheet1->setCellValue('F' . $rowNum, $statusSekolah);

    // Styling Baris Total
    $sheet1->getStyle('A' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
    $sheet1->getStyle('A' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
    $sheet1->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $rowNum++;
} else {
    $sheet1->mergeCells('A5:F5');
    $sheet1->setCellValue('A5', 'Belum ada data rekapitulasi yang tersedia.');
    $sheet1->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $rowNum++;
}

// Berikan Border Sheet 1
$tableBorder = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
];
$sheet1->getStyle('A4:F' . ($rowNum - 1))->applyFromArray($tableBorder);

foreach (range('A', 'F') as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}


// =========================================================================
// SHEET 2 : DETAIL SELURUH KPI (DENGAN REALISASI, BUKTI, KENDALA, TINDAK LANJUT)
// =========================================================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Detail Seluruh KPI');

$sheet2->mergeCells('A1:M1'); // Diperlebar sampai kolom M
$sheet2->setCellValue('A1', 'DETAIL SELURUH INDIKATOR KINERJA (KPI) - ' . strtoupper($nama_sekolah));
$sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header Kolom Tabel Detail (Ditambah Kolom Realisasi Aktual)
$headers2 = [
    'No', 'Bidang', 'Nama PIC', 'Indikator Kinerja', 'Nilai HUMANIS', 
    'Target', 'Realisasi Aktual', 'Capaian', 'Status Laporan', 'Bukti / Link Dokumen', 
    'Refleksi / Kendala', 'Rencana Tindak Lanjut', 'Arahan Tindak Lanjut Kepsek'
];
$sheet2->fromArray($headers2, NULL, 'A3');
$sheet2->getStyle('A3:M3')->applyFromArray($headerStyle);
$sheet2->getRowDimension(3)->setRowHeight(25);

$rowNum2 = 4;
$no2 = 1;

if (!empty($detailData)) {
    foreach ($detailData as $row) {
        $sheet2->setCellValue('A' . $rowNum2, $no2++);
        $sheet2->setCellValue('B' . $rowNum2, $row['bidang']);
        $sheet2->setCellValue('C' . $rowNum2, $row['pic']);
        $sheet2->setCellValue('D' . $rowNum2, $row['indikator']);
        $sheet2->setCellValue('E' . $rowNum2, $row['nilai_humanis'] ?? '-'); 
        $sheet2->setCellValue('F' . $rowNum2, $row['target'] . ' ' . $row['satuan']);

        // Data Realisasi Aktual
        $realisasiText = (isset($row['realisasi']) && trim($row['realisasi']) !== '') ? floatval($row['realisasi']) . ' ' . $row['satuan'] : 'Belum diinput';
        $sheet2->setCellValue('G' . $rowNum2, $realisasiText);

        // Penanganan capaian jika kosong (belum lapor)
        $capaianText = ($row['capaian'] !== null) ? $row['capaian'] . '%' : '0% (Belum Lapor)';
        $sheet2->setCellValue('H' . $rowNum2, $capaianText);

        $sheet2->setCellValue('I' . $rowNum2, $row['status_verifikasi'] ?? 'Menunggu');

        // Penanganan Link Bukti GDrive agar bisa di-klik
        if (!empty($row['bukti_gdrive'])) {
            $sheet2->setCellValue('J' . $rowNum2, 'Buka Dokumen');
            $sheet2->getCell('J' . $rowNum2)->getHyperlink()->setUrl($row['bukti_gdrive']);
            $sheet2->getStyle('J' . $rowNum2)->getFont()->getColor()->setRGB('2563EB'); // Warna biru link
            $sheet2->getStyle('J' . $rowNum2)->getFont()->setUnderline(true);
        } else {
            $sheet2->setCellValue('J' . $rowNum2, '-');
        }

        // Teks Refleksi/Kendala, Rencana Tindak Lanjut PIC, & Arahan Kepsek
        $sheet2->setCellValue('K' . $rowNum2, $row['refleksi_kendala'] ?? '-');
        $sheet2->setCellValue('L' . $rowNum2, $row['rencana_tindak_lanjut'] ?? '-');
        $sheet2->setCellValue('M' . $rowNum2, $row['arahan_kepsek'] ?? '-');

        // Alignment
        $sheet2->getStyle('A' . $rowNum2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('E' . $rowNum2 . ':J' . $rowNum2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Agar teks yang panjang otomatis turun/wrap text (Kolom D, K, L, M)
        $sheet2->getStyle('D' . $rowNum2)->getAlignment()->setWrapText(true);
        $sheet2->getStyle('K' . $rowNum2)->getAlignment()->setWrapText(true);
        $sheet2->getStyle('L' . $rowNum2)->getAlignment()->setWrapText(true);
        $sheet2->getStyle('M' . $rowNum2)->getAlignment()->setWrapText(true);

        $rowNum2++;
    }
} else {
    $sheet2->mergeCells('A4:M4');
    $sheet2->setCellValue('A4', 'Tidak ada data indikator kinerja ditemukan.');
    $sheet2->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $rowNum2++;
}

// Berikan Border Sheet 2 (A sampai M)
$sheet2->getStyle('A3:M' . ($rowNum2 - 1))->applyFromArray($tableBorder);

// Auto-size kolom di Sheet 2, khusus kolom teks panjang (D, K, L, M) kita set lebar manual
foreach (range('A', 'M') as $col) {
    if (in_array($col, ['D', 'K', 'L', 'M'])) {
        $sheet2->getColumnDimension($col)->setWidth(40); // Lebar fixed untuk teks panjang agar rapi
    } else {
        $sheet2->getColumnDimension($col)->setAutoSize(true);
    }
}

// Set Sheet pertama (Rekap) agar yang terbuka pertama kali adalah Rekap
$spreadsheet->setActiveSheetIndex(0);

// =========================================================================
// SAPU BERSIH SEMUA TINGKATAN OUTPUT BUFFER SEBELUM HEADER EXCEL DIKIRIM
// =========================================================================
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Proses Output Download File .xlsx
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Rekap_Kinerja_Lengkap_' . str_replace(' ', '_', $nama_sekolah) . '_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;