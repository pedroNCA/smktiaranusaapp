<?php

session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/laporan_helper.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

$tahun = (int) ($_GET['tahun'] ?? 0);
$kelas = (int) ($_GET['kelas'] ?? 0);
$jenis = $_GET['jenis'] ?? 'bulanan';
$periode = $_GET['periode'] ?? date('n');

if ($tahun === 0 || $kelas === 0) {
    exit('Parameter tidak lengkap.');
}

$spreadsheet = new Spreadsheet();

function buildSheet(
    Spreadsheet $spreadsheet,
    Worksheet $sheet,
    mysqli $conn,
    int $tahun,
    int $kelas,
    string $jenis,
    string|int $periode
) {

    $info = getInfoLaporan(
        $conn,
        $tahun,
        $kelas
    );

    $summary = getSummaryLaporan(
        $conn,
        $tahun,
        $kelas,
        $jenis,
        $periode
    );

    $rows = getLaporanHarian(
        $conn,
        $tahun,
        $kelas,
        $jenis,
        $periode
    );

    $headers = getHeaderTanggal(
        $conn,
        $tahun,
        $jenis,
        $periode
    );

    $tahunAjaran = getTahunAjaran(
        $conn,
        $tahun
    );

    if ($jenis === 'bulanan') {

        $tahunMulai = (int) date(
            'Y',
            strtotime($tahunAjaran['tanggal_mulai'])
        );

        $tahunSelesai = (int) date(
            'Y',
            strtotime($tahunAjaran['tanggal_selesai'])
        );

        $tahunPeriode = ((int) $periode >= 7)
            ? $tahunMulai
            : $tahunSelesai;

        $namaPeriode =
            getNamaPeriode('bulanan', $periode)
            . ' '
            . $tahunPeriode;
    } elseif ($jenis === 'semester') {

        $namaPeriode = getNamaPeriode(
            'semester',
            $periode
        );
    } else {

        $namaPeriode = $tahunAjaran['nama_tahun'];
    }

    $spreadsheet->getDefaultStyle()
        ->getFont()
        ->setName('Arial')
        ->setSize(10);

    $sheet->getDefaultRowDimension()
        ->setRowHeight(20);

    $sheet->setCellValue('B3', 'Kelas');
    $sheet->setCellValue('C3', ':');
    $sheet->setCellValue('D3', $info['nama_kelas']);

    $sheet->setCellValue('B4', 'Tahun Ajaran');
    $sheet->setCellValue('C4', ':');
    $sheet->setCellValue('D4', $info['nama_tahun']);

    $sheet->setCellValue('B5', 'Periode');
    $sheet->setCellValue('C5', ':');
    $sheet->setCellValue('D5', $namaPeriode);

    /*
|--------------------------------------------------------------------------
| HEADER TABEL
|--------------------------------------------------------------------------
*/

    $rowHeader1 = 8;
    $rowHeader2 = 9;

    $sheet->mergeCells("A{$rowHeader1}:A{$rowHeader2}");
    $sheet->mergeCells("B{$rowHeader1}:B{$rowHeader2}");
    $sheet->mergeCells("C{$rowHeader1}:C{$rowHeader2}");

    $sheet->setCellValue("A{$rowHeader1}", "No");
    $sheet->setCellValue("B{$rowHeader1}", "NIS");
    $sheet->setCellValue("C{$rowHeader1}", "Nama Siswa");

    $jumlahTanggal = count($headers);

    $colAwalTanggal = 4; // D

    $colAkhirTanggal = $colAwalTanggal + $jumlahTanggal - 1;

    $hurufAwal = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
        $colAwalTanggal
    );

    $hurufAkhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
        $colAkhirTanggal
    );

    $sheet->mergeCells(
        "{$hurufAwal}{$rowHeader1}:{$hurufAkhir}{$rowHeader1}"
    );

    $sheet->setCellValue(
        "{$hurufAwal}{$rowHeader1}",
        "TANGGAL"
    );

    $col = $colAwalTanggal;

    foreach ($headers as $header) {

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        $sheet->setCellValue(
            "{$huruf}{$rowHeader2}",
            $header['hari']
        );

        $sheet->getColumnDimension($huruf)
            ->setWidth(4);

        $col++;
    }

    $rekap = [

        'H',
        'I',
        'S',
        'A',
        'T',
        '%'

    ];

    foreach ($rekap as $judul) {

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        $sheet->mergeCells(
            "{$huruf}{$rowHeader1}:{$huruf}{$rowHeader2}"
        );

        $sheet->setCellValue(
            "{$huruf}{$rowHeader1}",
            $judul
        );

        $sheet->getColumnDimension($huruf)
            ->setWidth(6);

        $col++;
    }

    $hurufTerakhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
        $col - 1
    );

    /*
|--------------------------------------------------------------------------
| JUDUL LAPORAN
|--------------------------------------------------------------------------
*/

    $sheet->mergeCells("A1:{$hurufTerakhir}1");

    $sheet->setCellValue(
        'A1',
        'LAPORAN REKAP PRESENSI SISWA'
    );

    $sheet->getStyle('A1')->applyFromArray([

        'font' => [

            'bold' => true,
            'size' => 14

        ],

        'alignment' => [

            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER

        ]

    ]);

    $sheet->getStyle(
        "A{$rowHeader1}:{$hurufTerakhir}{$rowHeader2}"
    )->applyFromArray([

        'font' => [

            'bold' => true

        ],

        'alignment' => [

            'horizontal' => Alignment::HORIZONTAL_CENTER,

            'vertical' => Alignment::VERTICAL_CENTER

        ],

        'fill' => [

            'fillType' => Fill::FILL_SOLID,

            'startColor' => [

                'rgb' => 'D9D9D9'

            ]

        ],

        'borders' => [

            'allBorders' => [

                'borderStyle' => Border::BORDER_THIN

            ]

        ]

    ]);

    $sheet->getColumnDimension('A')->setWidth(6);

    $sheet->getColumnDimension('B')->setWidth(18);

    $sheet->getColumnDimension('C')->setWidth(35);

    /*
|--------------------------------------------------------------------------
| DATA SISWA
|--------------------------------------------------------------------------
*/

    $row = 10;
    $no = 1;

    foreach ($rows as $item) {

        $sheet->setCellValue("A{$row}", $no);
        $sheet->setCellValue("B{$row}", $item['nis']);
        $sheet->setCellValue("C{$row}", $item['nama_siswa']);

        $col = $colAwalTanggal;

        foreach ($headers as $header) {

            $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

            $kode = $item['tanggal'][$header['tanggal']] ?? '';

            $sheet->setCellValue(
                "{$huruf}{$row}",
                $kode
            );

            $col++;
        }

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
        $sheet->setCellValue("{$huruf}{$row}", $item['hadir']);

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
        $sheet->setCellValue("{$huruf}{$row}", $item['izin']);

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
        $sheet->setCellValue("{$huruf}{$row}", $item['sakit']);

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
        $sheet->setCellValue("{$huruf}{$row}", $item['alfa']);

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
        $sheet->setCellValue("{$huruf}{$row}", $item['terlambat']);

        $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
        $sheet->setCellValue("{$huruf}{$row}", $item['persentase'] . '%');

        $no++;
        $row++;
    }

    $sheet->getStyle(
        "A10:{$hurufTerakhir}" . ($row - 1)
    )->applyFromArray([

        'borders' => [

            'allBorders' => [

                'borderStyle' => Border::BORDER_THIN

            ]

        ]

    ]);

    $sheet->getStyle(
        "A10:A" . ($row - 1)
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );

    $sheet->getStyle(
        "B10:B" . ($row - 1)
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );

    $sheet->getStyle(
        "D10:{$hurufTerakhir}" . ($row - 1)
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );

    $sheet->getStyle(
        "A10:{$hurufTerakhir}" . ($row - 1)
    )->getAlignment()->setVertical(
        Alignment::VERTICAL_CENTER
    );

    for ($i = 10; $i < $row; $i++) {

        $sheet->getRowDimension($i)
            ->setRowHeight(22);
    }

    /*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

    $sheet->mergeCells("A{$row}:{$hurufAkhir}{$row}");

    $sheet->setCellValue(
        "A{$row}",
        "TOTAL"
    );

    $sheet->getStyle("A{$row}:{$hurufAkhir}{$row}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $col = $colAkhirTanggal + 1;

    $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
    $sheet->setCellValue("{$huruf}{$row}", $summary['hadir']);

    $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
    $sheet->setCellValue("{$huruf}{$row}", $summary['izin']);

    $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
    $sheet->setCellValue("{$huruf}{$row}", $summary['sakit']);

    $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
    $sheet->setCellValue("{$huruf}{$row}", $summary['alfa']);

    $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
    $sheet->setCellValue("{$huruf}{$row}", $summary['terlambat']);

    $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
    $sheet->setCellValue("{$huruf}{$row}", $summary['persen'] . '%');

    $sheet->getStyle(
        "A{$row}:{$hurufTerakhir}{$row}"
    )->applyFromArray([

        'font' => [

            'bold' => true

        ],

        'fill' => [

            'fillType' => Fill::FILL_SOLID,

            'startColor' => [

                'rgb' => 'FFF2CC'

            ]

        ],

        'borders' => [

            'allBorders' => [

                'borderStyle' => Border::BORDER_THIN

            ]

        ]

    ]);

    $sheet->getStyle(
        "A1:{$hurufTerakhir}{$row}"
    )->getAlignment()->setWrapText(true);

    $sheet->getStyle(
        "A1:{$hurufTerakhir}{$row}"
    )->getAlignment()->setVertical(
        Alignment::VERTICAL_CENTER
    );
}

switch ($jenis) {

    case 'bulanan':

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            getNamaPeriode('bulanan', $periode)
        );

        buildSheet(
            $spreadsheet,
            $sheet,
            $conn,
            $tahun,
            $kelas,
            'bulanan',
            $periode
        );

        break;

    case 'semester':

        $bulanList = ($periode === 'ganjil')
            ? [7, 8, 9, 10, 11, 12]
            : [1, 2, 3, 4, 5, 6];

        foreach ($bulanList as $index => $bulan) {

            if ($index == 0) {

                $sheet = $spreadsheet->getActiveSheet();
            } else {

                $sheet = $spreadsheet->createSheet();
            }

            $sheet->setTitle(
                getNamaPeriode('bulanan', $bulan)
            );

            buildSheet(
                $spreadsheet,
                $sheet,
                $conn,
                $tahun,
                $kelas,
                'bulanan',
                $bulan
            );
        }

        break;

    case 'tahunan':

        $bulanTahunAjaran = [

            7,
            8,
            9,
            10,
            11,
            12,
            1,
            2,
            3,
            4,
            5,
            6

        ];

        foreach ($bulanTahunAjaran as $index => $bulan) {

            if ($index == 0) {

                $sheet = $spreadsheet->getActiveSheet();
            } else {

                $sheet = $spreadsheet->createSheet();
            }

            $sheet->setTitle(
                getNamaPeriode('bulanan', $bulan)
            );

            buildSheet(
                $spreadsheet,
                $sheet,
                $conn,
                $tahun,
                $kelas,
                'bulanan',
                $bulan
            );
        }

        break;
}

$spreadsheet->setActiveSheetIndex(0);

$info = getInfoLaporan(
    $conn,
    $tahun,
    $kelas
);

$namaKelas = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $info['nama_kelas']
);

switch ($jenis) {

    case 'bulanan':

        $namaExport =
            'rekap_presensi_' .
            $namaKelas .
            '_' .
            strtolower(getNamaPeriode('bulanan', $periode));

        break;

    case 'semester':

        $namaExport =
            'rekap_presensi_' .
            $namaKelas .
            '_semester_' .
            ($periode === 'ganjil' ? 'ganjil' : 'genap');

        break;

    default:

        $namaExport =
            'rekap_presensi_' .
            $namaKelas .
            '_' .
            str_replace('/', '-', $info['nama_tahun']);

        break;
}

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $namaExport . '.xlsx"'
);

header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;