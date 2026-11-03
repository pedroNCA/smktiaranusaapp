<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::admin();

// Panggil autoloader PhpSpreadsheet
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

date_default_timezone_set('Asia/Jakarta');
$tanggal_hari_ini = date('Y-m-d');

$spreadsheet = new Spreadsheet();
// Hapus sheet default pertama agar bisa kita buat secara kustom
$spreadsheet->removeSheetByIndex(0);

// 1. Ambil seluruh data kelas
$qKelas = $conn->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$daftarKelas = $qKelas->fetch_all(MYSQLI_ASSOC);

$rekapGabungan = [];

// Style Global
$styleHeader = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$styleBorderAll = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]]
];

foreach ($daftarKelas as $index => $kelas) {
    $kelasId = $kelas['id'];
    $namaKelas = $kelas['nama_kelas'];

    // Buat Sheet baru untuk masing-masing kelas
    $sheet = $spreadsheet->createSheet($index);
    // Bersihkan karakter terlarang untuk nama sheet Excel (maks 31 karakter, tanpa simbol khusus)
    $safeSheetName = preg_replace('/[\\/\?*\[\]:]/', '', $namaKelas);
    $sheet->setTitle($safeSheetName);

    // Judul Sheet
    $sheet->setCellValue('A1', 'REKAP PRESENSI SISWA - KELAS ' . strtoupper($namaKelas));
    $sheet->setCellValue('A2', 'Tanggal: ' . date('d-m-Y', strtotime($tanggal_hari_ini)));
    $sheet->mergeCells('A1:E1');
    $sheet->mergeCells('A2:E2');

    // Header Tabel
    $headers = ['No', 'NIS', 'Nama Siswa', 'Status Kehadiran', 'Jam Datang'];
    $sheet->fromArray($headers, NULL, 'A4');
    $sheet->getStyle('A4:E4')->applyFromArray($styleHeader);

    // Ambil data siswa & absensi hari ini per kelas
    $qSiswa = $conn->prepare("
        SELECT s.nis, s.nama_siswa, COALESCE(a.status, 'Belum ada keterangan') AS status, a.jam_datang
        FROM siswa s
        LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
        WHERE s.kelas_id = ?
        ORDER BY s.nama_siswa ASC
    ");
    $qSiswa->bind_param('si', $tanggal_hari_ini, $kelasId);
    $qSiswa->execute();
    $dataSiswa = $qSiswa->get_result()->fetch_all(MYSQLI_ASSOC);
    $qSiswa->close();

    $rowNum = 5;
    $no = 1;
    $statHadir = 0;
    $statIzin = 0;
    $statSakit = 0;
    $statAlfa = 0;
    $statTerlambat = 0;
    $statBelum = 0;

    if (!empty($dataSiswa)) {
        foreach ($dataSiswa as $s) {
            // Hitung statistik untuk rekap sheet terakhir
            if ($s['status'] == 'Hadir') $statHadir++;
            elseif ($s['status'] == 'Izin') $statIzin++;
            elseif ($s['status'] == 'Sakit') $statSakit++;
            elseif ($s['status'] == 'Alfa') $statAlfa++;
            elseif ($s['status'] == 'Terlambat') $statTerlambat++;
            else $statBelum++;

            $sheet->setCellValue('A' . $rowNum, $no++);
            $sheet->setCellValue('B' . $rowNum, $s['nis']);
            $sheet->setCellValue('C' . $rowNum, $s['nama_siswa']);
            $sheet->setCellValue('D' . $rowNum, $s['status']);
            $sheet->setCellValue('E' . $rowNum, $s['jam_datang'] ? date('H:i:s', strtotime($s['jam_datang'])) : '-');

            $rowNum++;
        }

        // Beri border untuk data tabel kelas ini
        $endRow = $rowNum - 1;
        $sheet->getStyle('A4:E' . $endRow)->applyFromArray($styleBorderAll);
    } else {
        $sheet->setCellValue('A5', 'Tidak ada data siswa pada kelas ini.');
        $sheet->mergeCells('A5:E5');
    }

    // Simpan data untuk sheet rekap akhir
    $rekapGabungan[] = [
        'nama_kelas' => $namaKelas,
        'total_siswa' => count($dataSiswa),
        'hadir' => $statHadir,
        'izin' => $statIzin,
        'sakit' => $statSakit,
        'alfa' => $statAlfa,
        'terlambat' => $statTerlambat,
        'belum' => $statBelum
    ];
}

// 2. Buat Sheet Terakhir: Rekap Keseluruhan Seluruh Kelas
$sheetRekap = $spreadsheet->createSheet(count($daftarKelas));
$sheetRekap->setTitle('Rekap Keseluruhan');

$sheetRekap->setCellValue('A1', 'REKAPITULASI KEHADIRAN SELURUH KELAS');
$sheetRekap->setCellValue('A2', 'Tanggal: ' . date('d-m-Y', strtotime($tanggal_hari_ini)));
$sheetRekap->mergeCells('A1:H1');
$sheetRekap->mergeCells('A2:H2');

$headersRekap = ['No', 'Nama Kelas', 'Total Siswa', 'Hadir', 'Izin', 'Sakit', 'Alfa', 'Terlambat'];
$sheetRekap->fromArray($headersRekap, NULL, 'A4');
$sheetRekap->getStyle('A4:H4')->applyFromArray($styleHeader);

$rowNum = 5;
$no = 1;
foreach ($rekapGabungan as $rg) {
    $sheetRekap->setCellValue('A' . $rowNum, $no++);
    $sheetRekap->setCellValue('B' . $rowNum, $rg['nama_kelas']);
    $sheetRekap->setCellValue('C' . $rowNum, $rg['total_siswa']);
    $sheetRekap->setCellValue('D' . $rowNum, $rg['hadir']);
    $sheetRekap->setCellValue('E' . $rowNum, $rg['izin']);
    $sheetRekap->setCellValue('F' . $rowNum, $rg['sakit']);
    $sheetRekap->setCellValue('G' . $rowNum, $rg['alfa']);
    $sheetRekap->setCellValue('H' . $rowNum, $rg['terlambat']);
    $rowNum++;
}

$endRowRekap = $rowNum - 1;
if ($endRowRekap >= 4) {
    $sheetRekap->getStyle('A4:H' . $endRowRekap)->applyFromArray($styleBorderAll);
}

// Set active sheet pertama kali dibuka ke sheet rekap keseluruhan
$spreadsheet->setActiveSheetIndex(count($daftarKelas));

// Output file excel untuk didownload
$filename = 'Rekap_Presensi_Hari_Ini_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
