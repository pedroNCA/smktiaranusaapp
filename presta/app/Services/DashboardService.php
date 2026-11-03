<?php

class DashboardService
{
    /**
     * Ringkasan Dashboard
     */
    public static function summary(
        mysqli $conn
    ): array {

        $summary = [

            'total_siswa' => 0,
            'total_kelas' => 0,
            'total_guru' => 0,
            'tahun_pelajaran' => '-'

        ];

        // Total Siswa
        $result = $conn->query("
            SELECT COUNT(*) total
            FROM siswa
        ");

        if ($result) {

            $summary['total_siswa']
                = (int)$result->fetch_assoc()['total'];
        }

        // Total Kelas
        $result = $conn->query("
            SELECT COUNT(*) total
            FROM kelas
        ");

        if ($result) {

            $summary['total_kelas']
                = (int)$result->fetch_assoc()['total'];
        }

        // Total Guru
        $result = $conn->query("
    SELECT COUNT(DISTINCT id) AS total
    FROM users
    WHERE role IN ('guru','piket')
");

        if ($result) {
            $summary['total_guru'] = (int) $result->fetch_assoc()['total'];
        }

        // Tahun Pelajaran
        $result = $conn->query("
    SELECT s.nama_tahun
    FROM tahun_ajaran s
    WHERE s.status='Aktif'
    LIMIT 1
");

        if ($result && $result->num_rows) {

            $summary['tahun_pelajaran']
                = $result->fetch_assoc()['nama_tahun'];
        }

        return $summary;
    }

    /**
     * Statistik Hari Ini
     */
    public static function todayAttendance(

        mysqli $conn,

        ?string $tanggal = null

    ): array {

        if (!$tanggal) {

            $tanggal = date('Y-m-d');
        }

        $stats = [

            'hadir' => 0,

            'izin' => 0,

            'sakit' => 0,

            'alfa' => 0,

            'terlambat' => 0,

            'belum' => 0

        ];

        $stmt = $conn->prepare("
SELECT
    SUM(status='Hadir') AS hadir,
    SUM(status='Izin') AS izin,
    SUM(status='Sakit') AS sakit,
    SUM(status='Alfa') AS alfa,
    SUM(status='Terlambat') AS terlambat,
    SUM(status='Belum ada keterangan') AS belum
FROM absensi
WHERE tanggal=?
");

        $stmt->bind_param(

            "s",

            $tanggal

        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows) {

            $row = $result->fetch_assoc();

            $stats = [

                'hadir'     => (int)($row['hadir'] ?? 0),
                'izin'      => (int)($row['izin'] ?? 0),
                'sakit'     => (int)($row['sakit'] ?? 0),
                'alfa'      => (int)($row['alfa'] ?? 0),
                'terlambat' => (int)($row['terlambat'] ?? 0),
                'belum'     => (int)($row['belum'] ?? 0)

            ];
        }

        return $stats;
    }

    /**
     * Persentase Kehadiran
     */
    public static function attendancePercentage(

        mysqli $conn,

        ?string $tanggal = null

    ): float {

        if (!$tanggal) {

            $tanggal = date('Y-m-d');
        }

        $stmt = $conn->prepare("

            SELECT

COUNT(*) total,

SUM(a.status='Hadir') hadir

FROM absensi a

WHERE a.tanggal=?

        ");

        $stmt->bind_param(

            "s",

            $tanggal

        );

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        if ($row['total'] == 0) {

            return 0;
        }

        return round(

            ($row['hadir'] / $row['total']) * 100,

            2

        );
    }

    public static function verificationProgress(
        mysqli $conn,
        ?string $tanggal = null
    ): array {

        if (!$tanggal) {
            $tanggal = date('Y-m-d');
        }

        $data = [];

        $stmt = $conn->prepare("
        SELECT
            vk.*,
            k.nama_kelas
        FROM verifikasi_kelas vk
        INNER JOIN kelas k
            ON k.id = vk.kelas_id
        WHERE vk.tanggal = ?
        ORDER BY k.nama_kelas ASC
    ");

        $stmt->bind_param("s", $tanggal);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            switch ($row['status']) {

                case 'sekretaris':
                    $row['progress'] = 33;
                    break;

                case 'mapel':
                    $row['progress'] = 66;
                    break;

                case 'selesai':
                    $row['progress'] = 100;
                    break;

                default:
                    $row['progress'] = 0;
            }

            $data[] = $row;
        }

        return $data;
    }

    public static function recentActivities(
        mysqli $conn,
        int $limit = 10
    ): array {

        require_once __DIR__ . '/ActivityLogService.php';

        return ActivityLogService::latest($conn, $limit);
    }

    public static function classDistribution(
        mysqli $conn
    ): array {

        $data = [];

        $result = $conn->query("
        SELECT
            k.id,
            k.nama_kelas,
            COUNT(s.id) total_siswa
        FROM kelas k
        LEFT JOIN siswa s
            ON s.kelas_id = k.id
        GROUP BY k.id
        ORDER BY k.nama_kelas ASC
    ");

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    public static function dashboardAdmin(
        mysqli $conn
    ): array {

        return [

            'summary'       => self::summary($conn),

            'attendance'    => self::todayAttendance($conn),

            'percentage'    => self::attendancePercentage($conn),

            'verification'  => self::verificationProgress($conn),

            'activities'    => self::recentActivities($conn),

            'distribution'  => self::classDistribution($conn)

        ];
    }

    /**
     * Dashboard Sekretaris
     */
    public static function dashboardSekretaris(
        mysqli $conn,
        int $userId,
        ?string $tanggal = null
    ): array {

        if (!$tanggal) {
            $tanggal = date('Y-m-d');
        }

        /*
    |--------------------------------------------------------------------------
    | Ambil Data Kelas Sekretaris
    |--------------------------------------------------------------------------
    */

        $stmt = $conn->prepare("
        SELECT
            k.id,
            k.nama_kelas,
            u.nama_lengkap AS wali_kelas
        FROM kelas k
        LEFT JOIN users u
            ON u.id = k.wali_kelas_id
        WHERE
            k.sekretaris_id = ?
        LIMIT 1
    ");

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        $kelas = $stmt->get_result()->fetch_assoc();

        if (!$kelas) {

            return [

                'kelas' => [
                    'id' => 0,
                    'nama_kelas' => '-',
                    'wali_kelas' => '-'
                ],

                'stats' => [
                    'hadir' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alfa' => 0,
                    'terlambat' => 0,
                    'belum' => 0
                ],

                'workflow' => [
                    'status' => 'draft'
                ],

                'verification' => [
                    'status' => 'draft'
                ],

                'total_siswa' => 0,

                'progress' => 0,

                'can_input' => false

            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Total Siswa
    |--------------------------------------------------------------------------
    */

        $stmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM siswa
        WHERE kelas_id=?
    ");

        $stmt->bind_param(
            "i",
            $kelas['id']
        );

        $stmt->execute();

        $totalSiswa = (int)$stmt
            ->get_result()
            ->fetch_assoc()['total'];

        /*
    |--------------------------------------------------------------------------
    | Statistik Hari Ini
    |--------------------------------------------------------------------------
    */

        $stats = [

            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alfa' => 0,
            'terlambat' => 0,
            'belum' => 0

        ];

        $stmt = $conn->prepare("

        SELECT

    SUM(a.status='Hadir') AS hadir,

    SUM(a.status='Izin') AS izin,

    SUM(a.status='Sakit') AS sakit,

    SUM(a.status='Alfa') AS alfa,

    SUM(a.status='Terlambat') AS terlambat,

    SUM(a.status='Belum ada keterangan') AS belum

FROM absensi a

INNER JOIN siswa s
    ON s.id = a.siswa_id

        WHERE

            s.kelas_id=?

        AND

            a.tanggal=?

    ");

        $stmt->bind_param(

            "is",

            $kelas['id'],

            $tanggal

        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows) {

            $row = $result->fetch_assoc();

            $stats = [

                'hadir'      => (int)($row['hadir'] ?? 0),
                'izin'       => (int)($row['izin'] ?? 0),
                'sakit'      => (int)($row['sakit'] ?? 0),
                'alfa'       => (int)($row['alfa'] ?? 0),
                'terlambat'  => (int)($row['terlambat'] ?? 0),
                'belum'      => (int)($row['belum'] ?? 0)

            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Workflow
    |--------------------------------------------------------------------------
    */

        require_once __DIR__ . '/VerificationService.php';

        $workflow = VerificationService::get(

            $conn,

            $kelas['id'],

            $tanggal

        );

        $progress = 0;

        if ($workflow) {

            switch ($workflow['status']) {

                case 'sekretaris':

                    $progress = 33;

                    break;

                case 'mapel':

                    $progress = 66;

                    break;

                case 'selesai':

                    $progress = 100;

                    break;
            }
        }

        if (!$workflow) {

            $workflow = [

                'status' => 'draft'

            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Boleh Input?
    |--------------------------------------------------------------------------
    */

        $canInput = true;

        if (

            $workflow

            &&

            in_array(

                $workflow['status'],

                ['sekretaris', 'mapel', 'selesai']

            )

        ) {

            $canInput = false;
        }
        $kelas['total_siswa'] = $totalSiswa;
        return [

            'kelas' => $kelas,

            'stats' => $stats,

            'workflow' => $workflow,

            'verification' => $workflow,

            'total_siswa' => $totalSiswa,

            'progress' => $progress,

            'can_input' => $canInput

        ];
    }
}
