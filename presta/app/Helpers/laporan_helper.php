<?php

declare(strict_types=1);

if (!defined('LAPORAN_HELPER')) {
    define('LAPORAN_HELPER', true);
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA TAHUN AJARAN
|--------------------------------------------------------------------------
*/

function getTahunAjaran(mysqli $conn, int $tahunId): array
{
    $stmt = $conn->prepare("
        SELECT
            id,
            nama_tahun,
            tanggal_mulai,
            tanggal_selesai
        FROM tahun_ajaran
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $tahunId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?? [];
}

/*
|--------------------------------------------------------------------------
| FILTER RENTANG TANGGAL
|--------------------------------------------------------------------------
*/

function buildFilterTanggal(
    mysqli $conn,
    int $tahunId,
    string $jenis,
    string|int $periode
): array {

    $tahun = getTahunAjaran($conn, $tahunId);

    if (!$tahun) {
        return [
            'sql' => '1=0',
            'types' => '',
            'params' => []
        ];
    }

    switch ($jenis) {

        case 'bulanan':
            $bulan = (int)$periode;
            
            // Tentukan tahun berdasarkan bulan (Bulan 7-12 masuk tahun awal ajaran, 1-6 masuk tahun akhir)
            $tahunAwal = (int)date('Y', strtotime($tahun['tanggal_mulai']));
            $tahunAkhir = (int)date('Y', strtotime($tahun['tanggal_selesai']));
            $tahunTarget = ($bulan >= 7) ? $tahunAwal : $tahunAkhir;

            $sql = "
                MONTH(a.tanggal) = ? 
                AND YEAR(a.tanggal) = ?
            ";

            return [
                'sql' => $sql,
                'types' => 'ii',
                'params' => [
                    $bulan,
                    $tahunTarget
                ]
            ];

        case 'semester':
            $mulai = $tahun['tanggal_mulai'];
            $selesai = $tahun['tanggal_selesai'];

            if ($periode === 'ganjil') {
                return [
                    'sql' => "a.tanggal BETWEEN ? AND ? AND MONTH(a.tanggal) BETWEEN 7 AND 12",
                    'types' => 'ss',
                    'params' => [$mulai, $selesai]
                ];
            }

            return [
                'sql' => "a.tanggal BETWEEN ? AND ? AND MONTH(a.tanggal) BETWEEN 1 AND 6",
                'types' => 'ss',
                'params' => [$mulai, $selesai]
            ];

        case 'tahunan':
        default:
            return [
                'sql' => "a.tanggal BETWEEN ? AND ?",
                'types' => 'ss',
                'params' => [
                    $tahun['tanggal_mulai'],
                    $tahun['tanggal_selesai']
                ]
            ];
    }
}

/*
|--------------------------------------------------------------------------
| HEADER TANGGAL
|--------------------------------------------------------------------------
*/

function getHeaderTanggal(
    mysqli $conn,
    int $tahunId,
    string $jenis,
    string|int $periode
): array {

    $tahun = getTahunAjaran(
        $conn,
        $tahunId
    );

    if (!$tahun) {
        return [];
    }

    switch ($jenis) {

        case 'bulanan':

            $bulan = (int)$periode;

            $tahunAwal = (int)date(
                'Y',
                strtotime($tahun['tanggal_mulai'])
            );

            $tahunAkhir = (int)date(
                'Y',
                strtotime($tahun['tanggal_selesai'])
            );

            $tahunHeader = $bulan >= 7
                ? $tahunAwal
                : $tahunAkhir;

            $jumlahHari = cal_days_in_month(
                CAL_GREGORIAN,
                $bulan,
                $tahunHeader
            );

            $header = [];

            for ($i = 1; $i <= $jumlahHari; $i++) {

                $header[] = [
                    'hari' => $i,
                    'tanggal' => sprintf(
                        '%04d-%02d-%02d',
                        $tahunHeader,
                        $bulan,
                        $i
                    )
                ];
            }

            return $header;

        case 'semester':

            $header = [];

            if ($periode === 'ganjil') {

                $mulai = new DateTime($tahun['tanggal_mulai']);
                $selesai = new DateTime(
                    $mulai->format('Y') . '-12-31'
                );
            } else {

                $mulai = new DateTime(
                    date(
                        'Y',
                        strtotime($tahun['tanggal_selesai'])
                    ) . '-01-01'
                );

                $selesai = new DateTime(
                    $tahun['tanggal_selesai']
                );
            }

            while ($mulai <= $selesai) {

                $header[] = [

                    'hari' => (int)$mulai->format('j'),

                    'tanggal' => $mulai->format('Y-m-d')

                ];

                $mulai->modify('+1 day');
            }

            return $header;

        case 'tahunan':

        default:

            $header = [];

            $mulai = new DateTime(
                $tahun['tanggal_mulai']
            );

            $selesai = new DateTime(
                $tahun['tanggal_selesai']
            );

            while ($mulai <= $selesai) {

                $header[] = [

                    'hari' => (int)$mulai->format('j'),

                    'tanggal' => $mulai->format('Y-m-d')

                ];

                $mulai->modify('+1 day');
            }

            return $header;
    }
}

/*
|--------------------------------------------------------------------------
| INFO LAPORAN
|--------------------------------------------------------------------------
*/

function getInfoLaporan(
    mysqli $conn,
    int $tahunId,
    int $kelasId
): array {

    $stmt = $conn->prepare("
        SELECT
            k.nama_kelas,
            ta.nama_tahun
        FROM kelas k
        CROSS JOIN tahun_ajaran ta
        WHERE
            k.id = ?
            AND ta.id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "ii",
        $kelasId,
        $tahunId
    );

    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?? [];
}

function getNamaPeriode(
    string $jenis,
    string|int $periode
): string {

    switch ($jenis) {

        case 'bulanan':

            $bulan = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];

            return $bulan[(int)$periode] ?? '-';

        case 'semester':

            return $periode === 'ganjil'
                ? 'Semester Ganjil'
                : 'Semester Genap';

        default:

            return 'Tahun Pelajaran';
    }
}

/*
|--------------------------------------------------------------------------
| SUMMARY LAPORAN
|--------------------------------------------------------------------------
*/

function getSummaryLaporan(
    mysqli $conn,
    int $tahunId,
    int $kelasId,
    string $jenis,
    string|int $periode
): array {

    $filter = buildFilterTanggal(
        $conn,
        $tahunId,
        $jenis,
        $periode
    );

    $sql = "
        SELECT
            SUM(LOWER(TRIM(a.status)) LIKE '%hadir%') AS hadir,
            SUM(LOWER(TRIM(a.status)) LIKE '%izin%') AS izin,
            SUM(LOWER(TRIM(a.status)) LIKE '%sakit%') AS sakit,
            SUM(LOWER(TRIM(a.status)) LIKE '%alfa%') AS alfa,
            SUM(LOWER(TRIM(a.status)) LIKE '%terlambat%') AS terlambat,

            COUNT(a.id) AS total,
            COUNT(DISTINCT a.tanggal) AS hari

        FROM siswa s

        LEFT JOIN absensi a
            ON a.siswa_id = s.id
            AND {$filter['sql']}

        WHERE
            s.kelas_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($filter['types'] === '') {
        $stmt->bind_param("i", $kelasId);
    } else {
        $types = $filter['types'] . "i";
        $params = [
            ...$filter['params'],
            $kelasId
        ];
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?? [];

    $hadir = (int)($row['hadir'] ?? 0);
    $izin = (int)($row['izin'] ?? 0);
    $sakit = (int)($row['sakit'] ?? 0);
    $alfa = (int)($row['alfa'] ?? 0);
    $terlambat = (int)($row['terlambat'] ?? 0);
    $total = (int)($row['total'] ?? 0);
    $hari = (int)($row['hari'] ?? 0);

    return [
        'hadir' => $hadir,
        'izin' => $izin,
        'sakit' => $sakit,
        'alfa' => $alfa,
        'terlambat' => $terlambat,
        'hari' => $hari,
        'total' => $total,
        'persen' => $total > 0
            ? round(($hadir / $total) * 100, 2)
            : 0
    ];
}

/*
|--------------------------------------------------------------------------
| PREVIEW LAPORAN
|--------------------------------------------------------------------------
*/

function getPreviewLaporan(
    mysqli $conn,
    int $tahunId,
    int $kelasId,
    string $jenis,
    string|int $periode
): array {

    $filter = buildFilterTanggal(
        $conn,
        $tahunId,
        $jenis,
        $periode
    );

    $sql = "
        SELECT
            s.id,
            s.nis,
            s.nama_siswa,

            SUM(LOWER(TRIM(a.status)) LIKE '%hadir%') AS hadir,
            SUM(LOWER(TRIM(a.status)) LIKE '%izin%') AS izin,
            SUM(LOWER(TRIM(a.status)) LIKE '%sakit%') AS sakit,
            SUM(LOWER(TRIM(a.status)) LIKE '%alfa%') AS alfa,
            SUM(LOWER(TRIM(a.status)) LIKE '%terlambat%') AS terlambat,

            COUNT(a.id) AS total

        FROM siswa s

        LEFT JOIN absensi a
            ON a.siswa_id = s.id
            AND {$filter['sql']}

        WHERE
            s.kelas_id = ?

        GROUP BY
            s.id,
            s.nis,
            s.nama_siswa

        ORDER BY
            s.nama_siswa ASC
    ";

    $stmt = $conn->prepare($sql);

    if ($filter['types'] === '') {
        $stmt->bind_param("i", $kelasId);
    } else {
        $types = $filter['types'] . "i";
        $params = [
            ...$filter['params'],
            $kelasId
        ];
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $hadir = (int)($row['hadir'] ?? 0);
        $izin = (int)($row['izin'] ?? 0);
        $sakit = (int)($row['sakit'] ?? 0);
        $alfa = (int)($row['alfa'] ?? 0);
        $terlambat = (int)($row['terlambat'] ?? 0);
        $total = (int)($row['total'] ?? 0);

        $row['hadir'] = $hadir;
        $row['izin'] = $izin;
        $row['sakit'] = $sakit;
        $row['alfa'] = $alfa;
        $row['terlambat'] = $terlambat;
        $row['total'] = $total;

        $row['persentase'] = $total > 0
            ? round(($hadir / $total) * 100, 2)
            : 0;

        $rows[] = $row;
    }

    return $rows;
}

/*
|--------------------------------------------------------------------------
| LAPORAN HARIAN
|--------------------------------------------------------------------------
*/

function getLaporanHarian(
    mysqli $conn,
    int $tahunId,
    int $kelasId,
    string $jenis,
    string|int $periode
): array {

    $filter = buildFilterTanggal(
        $conn,
        $tahunId,
        $jenis,
        $periode
    );

    $sql = "
        SELECT
            s.id,
            s.nis,
            s.nama_siswa,

            a.tanggal,
            a.status

        FROM siswa s

        LEFT JOIN absensi a
            ON a.siswa_id = s.id
            AND {$filter['sql']}

        WHERE
            s.kelas_id = ?

        ORDER BY
            s.nama_siswa ASC,
            a.tanggal ASC
    ";

    $stmt = $conn->prepare($sql);

    if ($filter['types'] === '') {

        $stmt->bind_param(
            "i",
            $kelasId
        );
    } else {

        $types = $filter['types'] . "i";

        $params = [
            ...$filter['params'],
            $kelasId
        ];

        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $rows = [];

    while ($row = $result->fetch_assoc()) {

        $id = (int)$row['id'];

        if (!isset($rows[$id])) {

            $rows[$id] = [

                'id' => $id,

                'nis' => $row['nis'],

                'nama_siswa' => $row['nama_siswa'],

                'tanggal' => [],

                'hadir' => 0,

                'izin' => 0,

                'sakit' => 0,

                'alfa' => 0,

                'terlambat' => 0,

                'total' => 0,

                'persentase' => 0

            ];
        }

        if (empty($row['tanggal'])) {
            continue;
        }

        $tanggal = $row['tanggal'];

        switch ($row['status']) {

            case 'Hadir':

                $kode = 'H';

                $rows[$id]['hadir']++;

                break;

            case 'Izin':

                $kode = 'I';

                $rows[$id]['izin']++;

                break;

            case 'Sakit':

                $kode = 'S';

                $rows[$id]['sakit']++;

                break;

            case 'Alfa':

                $kode = 'A';

                $rows[$id]['alfa']++;

                break;

            case 'Terlambat':

                $kode = 'T';

                $rows[$id]['terlambat']++;

                break;

            default:

                $kode = '-';

                break;
        }

        $rows[$id]['tanggal'][$tanggal] = $kode;

        $rows[$id]['total']++;
    }

    foreach ($rows as &$row) {

        ksort($row['tanggal']);

        $row['persentase'] = $row['total'] > 0
            ? round(
                ($row['hadir'] / $row['total']) * 100,
                2
            )
            : 0;
    }

    unset($row);

    return array_values($rows);
}

/*
|--------------------------------------------------------------------------
| BUILD HTML PREVIEW
|--------------------------------------------------------------------------
*/

function buildPreviewTable(array $rows): string
{
    if (empty($rows)) {

        return '
            <tr>
                <td colspan="9" style="text-align:center;padding:30px;">
                    Tidak ada data absensi.
                </td>
            </tr>
        ';
    }

    $html = '';

    $no = 1;

    foreach ($rows as $row) {

        $html .= '
            <tr>

                <td class="text-center">'
            . $no .
            '</td>

                <td>'
            . htmlspecialchars((string)$row['nis']) .
            '</td>

                <td>'
            . htmlspecialchars((string)$row['nama_siswa']) .
            '</td>

                <td class="text-center">'
            . (int)$row['hadir'] .
            '</td>

                <td class="text-center">'
            . (int)$row['izin'] .
            '</td>

                <td class="text-center">'
            . (int)$row['sakit'] .
            '</td>

                <td class="text-center">'
            . (int)$row['alfa'] .
            '</td>

                <td class="text-center">'
            . (int)$row['terlambat'] .
            '</td>

                <td class="text-center">'
            . round((float)$row['persentase'], 2) .
            '%</td>

            </tr>
        ';

        $no++;
    }

    return $html;
}