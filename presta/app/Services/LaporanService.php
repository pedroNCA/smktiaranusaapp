<?php

class LaporanService
{

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public static function dashboard(mysqli $conn)
    {

        return [

            'tahun' => self::tahunPelajaran($conn),

            'kelas' => self::kelas($conn)

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DATA TAHUN
    |--------------------------------------------------------------------------
    */

    public static function tahunPelajaran(mysqli $conn)
    {

        $data = [];

        $q = $conn->query("
        SELECT
        id,
        nama_tahun,
        status
        FROM tahun_ajaran
        ORDER BY id DESC
        ");

        while ($r = $q->fetch_assoc()) {

            $data[] = $r;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | DATA KELAS
    |--------------------------------------------------------------------------
    */

    public static function kelas(mysqli $conn)
    {

        $data = [];

        $q = $conn->query("
        SELECT
        id,
        nama_kelas
        FROM kelas
        ORDER BY nama_kelas
        ");

        while ($r = $q->fetch_assoc()) {

            $data[] = $r;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL SISWA
    |--------------------------------------------------------------------------
    */

    public static function totalSiswa(mysqli $conn, $kelas)
    {

        $stmt = $conn->prepare("
        SELECT COUNT(*)
        total
        FROM siswa
        WHERE kelas_id=?
        ");

        $stmt->bind_param("i", $kelas);

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc()['total'];
    }

    /*
    |--------------------------------------------------------------------------
    | REKAP STATUS
    |--------------------------------------------------------------------------
    */

    public static function rekapStatus(
        mysqli $conn,
        $kelas,
        $bulan,
        $tahun
    ) {

        $stmt = $conn->prepare("
        SELECT
        status_kehadiran,
        COUNT(*) jumlah
        FROM presensi
        WHERE
        kelas_id=?
        AND MONTH(tanggal)=?
        AND YEAR(tanggal)=?
        GROUP BY status_kehadiran
        ");

        $stmt->bind_param(
            "iii",
            $kelas,
            $bulan,
            $tahun
        );

        $stmt->execute();

        $hasil = [

            'Hadir' => 0,

            'Izin' => 0,

            'Sakit' => 0,

            'Alfa' => 0,

            'Terlambat' => 0

        ];

        while ($r = $stmt
            ->get_result()
            ->fetch_assoc()
        ) {

            $hasil[$r['status_kehadiran']]
                = $r['jumlah'];
        }

        return $hasil;
    }

    /*
    |--------------------------------------------------------------------------
    | DAFTAR SISWA
    |--------------------------------------------------------------------------
    */

    public static function siswa(
        mysqli $conn,
        $kelas
    ) {

        $stmt = $conn->prepare("
        SELECT
        id,
        nis,
        nama_siswa
        FROM siswa
        WHERE kelas_id=?
        ORDER BY nama_siswa
        ");

        $stmt->bind_param(
            "i",
            $kelas
        );

        $stmt->execute();

        $data = [];

        while ($r = $stmt
            ->get_result()
            ->fetch_assoc()
        ) {

            $data[] = $r;
        }

        return $data;
    }
}
