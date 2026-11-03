<?php

declare(strict_types=1);
require_once __DIR__ . '/ActivityLogService.php';

class AttendanceService
{
    public const STATUS_HADIR = 'Hadir';
    public const STATUS_SAKIT = 'Sakit';
    public const STATUS_IZIN = 'Izin';
    public const STATUS_ALFA = 'Alfa';
    public const STATUS_TERLAMBAT = 'Terlambat';
    public const STATUS_BELUM = 'Belum ada keterangan';
    public const WORKFLOW_DRAFT = 'draft';
    public const WORKFLOW_SEKRETARIS = 'sekretaris';
    public const WORKFLOW_GURU = 'guru';
    public const WORKFLOW_SELESAI = 'selesai';
    public const TEST_MODE = false; // Ubah ke true jika ingin bypass untuk testing

    /**
     * Memeriksa apakah waktu saat ini berada dalam rentang izin pengisian presensi
     */
    private static function validateTimeWindow(mysqli $conn): void
    {
        if (self::TEST_MODE) {
            return;
        }

        date_default_timezone_set('Asia/Jakarta');
        $hariAngka = (int)date('N'); // 1 (Senin) sampai 7 (Minggu)

        // Weekend (Sabtu & Minggu) otomatis libur total
        if ($hariAngka >= 6) {
            throw new Exception('Pengisian presensi diliburkan pada hari Sabtu dan Minggu.');
        }

        $namaHariMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat'
        ];
        $hariIniTeks = $namaHariMap[$hariAngka] ?? 'Senin';

        // Ambil jam mulai & tutup dari database berdasarkan hari ini
        $stmt = $conn->prepare("SELECT jam_mulai, jam_tutup FROM pengaturan_jam_presensi WHERE hari = ? LIMIT 1");
        $stmt->bind_param("s", $hariIniTeks);
        $stmt->execute();
        $setting = $stmt->get_result()->fetch_assoc();

        $jamMulai = $setting['jam_mulai'] ?? '06:00:00';
        $jamTutup = $setting['jam_tutup'] ?? '07:10:00';
        $jamSekarang = date('H:i:s');

        if ($jamSekarang < $jamMulai) {
            throw new Exception('Waktu pengisian presensi belum dimulai (Mulai pukul ' . substr($jamMulai, 0, 5) . ' WIB).');
        }

        if ($jamSekarang >= $jamTutup) {
            throw new Exception('Batas waktu pengisian presensi telah berakhir (Ditutup pukul ' . substr($jamTutup, 0, 5) . ' WIB).');
        }
    }

    private static function allowedStatus(): array
    {
        return [
            self::STATUS_HADIR,
            self::STATUS_SAKIT,
            self::STATUS_IZIN,
            self::STATUS_ALFA,
            self::STATUS_TERLAMBAT,
            self::STATUS_BELUM
        ];
    }

    private static function validateStatus(
        string $status,
        ?string &$jamDatang = null
    ): void {

        if (!in_array($status, self::allowedStatus(), true)) {
            throw new Exception('Status presensi tidak valid.');
        }

        if (
            $status === self::STATUS_TERLAMBAT &&
            empty($jamDatang)
        ) {
            throw new Exception('Jam datang wajib diisi.');
        }

        if ($status !== self::STATUS_TERLAMBAT) {
            $jamDatang = null;
        }
    }

    private static function checkEditPermission(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): void {

        // Validasi batasan waktu jam operasional harian
        self::validateTimeWindow($conn);

        if (!self::canEdit(
            $conn,
            $kelasId,
            $tanggal
        )) {
            throw new Exception(
                'Presensi sudah selesai diverifikasi.'
            );
        }
    }

    private static function workflowStatus(
        ?array $workflow
    ): string {

        return $workflow['status']
            ?? self::WORKFLOW_DRAFT;
    }

    /**
     * Ambil seluruh data absensi berdasarkan kelas & tanggal
     */
    public static function getByClass(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): array {

        $stmt = $conn->prepare("
        SELECT
            a.id,
            a.siswa_id,
            a.status,
            a.jam_datang,
            a.keterangan,
            s.nis,
            s.nama_siswa,
            k.nama_kelas
        FROM siswa s
        INNER JOIN kelas k
            ON k.id=s.kelas_id
        LEFT JOIN absensi a
            ON a.siswa_id=s.id
            AND a.tanggal=?
        WHERE
            s.kelas_id=?
        AND
            s.status='Aktif'
        ORDER BY
            s.nama_siswa ASC
    ");

        $stmt->bind_param(
            "si",
            $tanggal,
            $kelasId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];

        while ($row = $result->fetch_assoc()) {

            if ($row['status'] === null) {
                $row['status'] = self::STATUS_BELUM;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Simpan Draft Absensi
     */
    public static function saveDraft(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): bool {

        self::checkEditPermission(
            $conn,
            $kelasId,
            $tanggal
        );

        return true;
    }

    /**
     * Ambil Ringkasan Kehadiran
     */
    public static function getSummary(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): array {

        $summary = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alfa' => 0,
            'terlambat' => 0,
            'belum' => 0
        ];

        $stmt = $conn->prepare("
        SELECT
            SUM(a.status=?) hadir,
            SUM(a.status=?) sakit,
            SUM(a.status=?) izin,
            SUM(a.status=?) alfa,
            SUM(a.status=?) terlambat,
            SUM(a.status IS NULL OR a.status=?) belum
        FROM siswa s
        LEFT JOIN absensi a
            ON a.siswa_id=s.id
            AND a.tanggal=?
        WHERE
            s.kelas_id=?
        AND
            s.status='Aktif'
    ");

        $hadir = self::STATUS_HADIR;
        $sakit = self::STATUS_SAKIT;
        $izin = self::STATUS_IZIN;
        $alfa = self::STATUS_ALFA;
        $terlambat = self::STATUS_TERLAMBAT;
        $belum = self::STATUS_BELUM;

        $stmt->bind_param(
            "sssssssi",
            $hadir,
            $sakit,
            $izin,
            $alfa,
            $terlambat,
            $belum,
            $tanggal,
            $kelasId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows) {
            $summary = array_merge(
                $summary,
                $result->fetch_assoc()
            );
        }

        return $summary;
    }

    public static function progress(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): float {

        $summary = self::getSummary(
            $conn,
            $kelasId,
            $tanggal
        );

        $total =
            $summary['hadir']
            + $summary['izin']
            + $summary['sakit']
            + $summary['alfa']
            + $summary['terlambat']
            + $summary['belum'];

        if ($total == 0) {
            return 0;
        }

        $selesai =
            $summary['hadir']
            + $summary['izin']
            + $summary['sakit']
            + $summary['alfa']
            + $summary['terlambat'];

        return round(
            ($selesai / $total) * 100,
            2
        );
    }

    public static function hasWorkflow(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): bool {

        return self::getWorkflow(
            $conn,
            $kelasId,
            $tanggal
        ) !== null;
    }

    /**
     * Update status satu siswa
     */
    public static function updateStudent(
        mysqli $conn,
        int $absensiId,
        string $status,
        ?string $jamDatang = null
    ): bool {
        // Tambahan keamanan di level update siswa
        date_default_timezone_set('Asia/Jakarta');
        $hariAngka = (int)date('N');
        if ($hariAngka >= 6 && !self::TEST_MODE) {
            throw new Exception('Pengisian presensi diliburkan pada akhir pekan.');
        }

        self::validateStatus(
            $status,
            $jamDatang
        );

        $stmt = $conn->prepare("
        UPDATE absensi
        SET
            status=?,
            jam_datang=?
        WHERE
            id=?
    ");

        $stmt->bind_param(
            "ssi",
            $status,
            $jamDatang,
            $absensiId
        );

        return $stmt->execute();
    }

    public static function getPercentage(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): float {

        return self::progress(
            $conn,
            $kelasId,
            $tanggal
        );
    }

    public static function getWorkflow(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): ?array {

        $stmt = $conn->prepare("
        SELECT *
        FROM verifikasi_kelas
        WHERE
            kelas_id=?
        AND
            tanggal=?
        LIMIT 1
    ");

        $stmt->bind_param(
            "is",
            $kelasId,
            $tanggal
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_assoc() ?: null;
    }

    public static function canEdit(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): bool {

        $workflow = self::getWorkflow(
            $conn,
            $kelasId,
            $tanggal
        );

        if (!$workflow) {
            return true;
        }

        return $workflow['status'] !== self::WORKFLOW_SELESAI;
    }

    public static function isCompleted(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): bool {

        $workflow = self::getWorkflow(
            $conn,
            $kelasId,
            $tanggal
        );

        return $workflow &&
            $workflow['status'] === self::WORKFLOW_SELESAI;
    }

    public static function autoAlfa(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): void {
        date_default_timezone_set('Asia/Jakarta');
        $hariAngka = (int)date('N');
        if ($hariAngka >= 6 && !self::TEST_MODE) {
            return;
        }

        $namaHariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];
        $hariIniTeks = $namaHariMap[$hariAngka] ?? 'Senin';

        $stmtSetting = $conn->prepare("SELECT jam_tutup FROM pengaturan_jam_presensi WHERE hari = ? LIMIT 1");
        $stmtSetting->bind_param("s", $hariIniTeks);
        $stmtSetting->execute();
        $setting = $stmtSetting->get_result()->fetch_assoc();
        $batasPresensi = $setting['jam_tutup'] ?? '07:10:00';

        if (
            !self::TEST_MODE &&
            date('H:i:s') < $batasPresensi
        ) {
            return;
        }

        $stmt = $conn->prepare("
        INSERT INTO absensi
        (
            siswa_id,
            tanggal,
            status
        )
        SELECT
            s.id,
            ?,
            ?
        FROM siswa s
        WHERE
            s.kelas_id=?
        AND
            s.status='Aktif'
        AND NOT EXISTS(
            SELECT 1
            FROM absensi a
            WHERE
                a.siswa_id=s.id
            AND
                a.tanggal=?
        )
    ");

        $alfa = self::STATUS_ALFA;

        $stmt->bind_param(
            "ssis",
            $tanggal,
            $alfa,
            $kelasId,
            $tanggal
        );

        $stmt->execute();

        $stmt = $conn->prepare("
        UPDATE absensi a
        INNER JOIN siswa s
            ON s.id=a.siswa_id
        SET
            a.status=?,
            a.jam_datang=NULL
        WHERE
            s.kelas_id=?
        AND
            a.tanggal=?
        AND
            a.status=?
    ");

        $belum = self::STATUS_BELUM;

        $stmt->bind_param(
            "siss",
            $alfa,
            $kelasId,
            $tanggal,
            $belum
        );

        $stmt->execute();
    }

    public static function autoSave(
        mysqli $conn,
        int $siswaId,
        string $tanggal,
        string $status,
        ?string $jamDatang = null
    ): bool {

        // Validasi waktu ketat di backend sebelum menyimpan
        self::validateTimeWindow($conn);

        self::validateStatus(
            $status,
            $jamDatang
        );

        $stmt = $conn->prepare("
        SELECT
            kelas_id
        FROM siswa
        WHERE id=?
        LIMIT 1
    ");

        $stmt->bind_param(
            "i",
            $siswaId
        );

        $stmt->execute();

        $kelas = $stmt
            ->get_result()
            ->fetch_assoc();

        if (!$kelas) {
            throw new Exception(
                'Data siswa tidak ditemukan.'
            );
        }

        $workflow = self::getWorkflow(
            $conn,
            $kelas['kelas_id'],
            $tanggal
        );

        if (
            $workflow &&
            $workflow['status'] !== self::WORKFLOW_DRAFT
        ) {
            throw new Exception(
                'Presensi sudah dikirim.'
            );
        }

        $cek = $conn->prepare("
        SELECT id
        FROM absensi
        WHERE
            siswa_id=?
        AND
            tanggal=?
        LIMIT 1
    ");

        $cek->bind_param(
            "is",
            $siswaId,
            $tanggal
        );

        $cek->execute();

        $result = $cek->get_result();

        if ($result->num_rows) {

            $row = $result->fetch_assoc();

            $update = $conn->prepare("
            UPDATE absensi
            SET
                status=?,
                jam_datang=?
            WHERE
                id=?
        ");

            $update->bind_param(
                "ssi",
                $status,
                $jamDatang,
                $row['id']
            );

            $update->execute();
        } else {

            $insert = $conn->prepare("
            INSERT INTO absensi
            (
                siswa_id,
                tanggal,
                status,
                jam_datang
            )
            VALUES
            (
                ?,?,?,?
            )
        ");

            $insert->bind_param(
                "isss",
                $siswaId,
                $tanggal,
                $status,
                $jamDatang
            );

            $insert->execute();
        }

        return true;
    }

    public static function submitPresensi(
        mysqli $conn,
        int $kelasId,
        int $sekretarisId,
        string $tanggal
    ): bool {
        
        // Validasi waktu ketat sebelum submit
        self::validateTimeWindow($conn);

        self::autoAlfa(
            $conn,
            $kelasId,
            $tanggal
        );

        $conn->begin_transaction();

        try {

            $workflow = self::getWorkflow(
                $conn,
                $kelasId,
                $tanggal
            );

            if ($workflow) {

                $stmt = $conn->prepare("
                UPDATE verifikasi_kelas
                SET
                    status=?,
                    sekretaris_id=?,
                    sekretaris_at=NOW()
                WHERE id=?
            ");

                $status = self::WORKFLOW_SEKRETARIS;

                $stmt->bind_param(
                    "sii",
                    $status,
                    $sekretarisId,
                    $workflow['id']
                );
            } else {

                $stmt = $conn->prepare("
                INSERT INTO verifikasi_kelas
                (
                    kelas_id,
                    tanggal,
                    status,
                    sekretaris_id,
                    sekretaris_at
                )
                VALUES
                (
                    ?,?,?,?,NOW()
                )
            ");

                $status = self::WORKFLOW_SEKRETARIS;

                $stmt->bind_param(
                    "issi",
                    $kelasId,
                    $tanggal,
                    $status,
                    $sekretarisId
                );
            }

            $stmt->execute();

            ActivityLogService::write(
                $conn,
                $sekretarisId,
                'absensi',
                'Submit Presensi',
                $kelasId,
                'Mengirim presensi untuk proses verifikasi'
            );

            $conn->commit();

            return true;
        } catch (Exception $e) {

            $conn->rollback();

            throw $e;
        }
    }
}