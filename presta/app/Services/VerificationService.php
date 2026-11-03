<?php

require_once __DIR__ . '/ActivityLogService.php';

class VerificationService
{

    private const STATUS_DRAFT = 'draft';
    private const STATUS_SEKRETARIS = 'sekretaris';
    private const STATUS_GURU = 'mapel';
    private const STATUS_SELESAI = 'selesai';

    private const MODUL = 'verifikasi';

    private const PROGRESS = [
        self::STATUS_DRAFT => 0,
        self::STATUS_SEKRETARIS => 33,
        self::STATUS_GURU => 66,
        self::STATUS_SELESAI => 100,
    ];

    /**
     * Ambil data workflow
     */
    public static function get(
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
            ->fetch_assoc()
            ?: null;
    }

    /**
     * Status Workflow
     */
    public static function status(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): string {

        $workflow = self::get(
            $conn,
            $kelasId,
            $tanggal
        );

        return $workflow['status']
            ?? self::STATUS_DRAFT;
    }

    /**
     * Persentase Progress
     */
    public static function progress(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): int {
        return self::PROGRESS[self::status(
            $conn,
            $kelasId,
            $tanggal
        )] ?? 0;
    }

    private static function ensureWorkflowStatus(
        mysqli $conn,
        int $kelasId,
        string $tanggal,
        string $expected
    ): array {

        $workflow = self::get(
            $conn,
            $kelasId,
            $tanggal
        );

        if (!$workflow) {
            throw new Exception(
                'Workflow belum tersedia.'
            );
        }

        if ($workflow['status'] !== $expected) {
            throw new Exception(
                'Status workflow tidak valid.'
            );
        }

        return $workflow;
    }

    /**
     * Verifikasi Guru Mapel
     */
    public static function verifyMapel(

        mysqli $conn,

        int $userId,

        int $kelasId,

        string $tanggal,

        ?string $catatan = null

    ): bool {

        $conn->begin_transaction();

        try {

            self::ensureWorkflowStatus(
                $conn,
                $kelasId,
                $tanggal,
                self::STATUS_SEKRETARIS
            );

            $stmt = $conn->prepare("
UPDATE verifikasi_kelas
SET
    status=?,
    mapel_id=?,
    mapel_at=NOW()
WHERE
    kelas_id=?
AND
    tanggal=?
AND
    status=?

            ");

            $status = self::STATUS_GURU;
            $stmt->bind_param(
                "siis",
                $status,
                $userId,
                $kelasId,
                $tanggal
            );

            $stmt->execute();

            if ($stmt->affected_rows == 0) {

                throw new Exception(
                    "Workflow tidak valid."
                );
            }

            ActivityLogService::write(

                $conn,

                $userId,

                self::MODUL,

                'Verifikasi Guru Mapel',

                $kelasId,

                $catatan

            );

            $conn->commit();

            return true;
        } catch (Exception $e) {

            $conn->rollback();

            throw $e;
        }
    }


    /**
     * Approval Guru Piket
     */
    public static function approvePiket(
        mysqli $conn,
        int $userId,
        int $kelasId,
        string $tanggal,
        ?string $catatan = null
    ): bool {

        $conn->begin_transaction();

        self::ensureWorkflowStatus(
            $conn,
            $kelasId,
            $tanggal,
            self::STATUS_GURU
        );

        try {

            $stmt = $conn->prepare("
UPDATE verifikasi_kelas
SET
    status=?,
    piket_id=?,
    piket_at=NOW()
WHERE
    kelas_id=?
AND
    tanggal=?
AND
    status=?

            ");

            $status = self::STATUS_SELESAI;

            $stmt->bind_param(
                "siis",
                $status,
                $userId,
                $kelasId,
                $tanggal
            );

            $stmt->execute();

            if ($stmt->affected_rows == 0) {

                throw new Exception(
                    "Workflow tidak valid."
                );
            }

            ActivityLogService::write(

                $conn,

                $userId,

                self::MODUL,

                'Approval Guru Piket',

                $kelasId,

                $catatan

            );

            $conn->commit();

            return true;
        } catch (Exception $e) {

            $conn->rollback();

            throw $e;
        }
    }


    /**
     * Buka kembali verifikasi (Admin)
     */
    public static function reopen(
        mysqli $conn,
        int $userId,
        int $kelasId,
        string $tanggal
    ): bool {

        self::ensureWorkflowStatus(
            $conn,
            $kelasId,
            $tanggal,
            self::status(
                $conn,
                $kelasId,
                $tanggal
            )
        );
        $conn->begin_transaction();

        try {

            $stmt = $conn->prepare("

                UPDATE verifikasi_kelas

                SET

                    status=?,

                    sekretaris_id=NULL,
                    sekretaris_at=NULL,

                    mapel_id=NULL,
                    mapel_at=NULL,

                    piket_id=NULL,
                    piket_at=NULL

                WHERE

                    kelas_id=?

                AND

                    tanggal=?

            ");
            $status = self::STATUS_DRAFT;

            $stmt->bind_param(
                "sis",
                $status,
                $kelasId,
                $tanggal
            );

            $stmt->execute();

            ActivityLogService::write(

                $conn,

                $userId,

                self::MODUL,

                'Membuka kembali workflow',

                $kelasId,

                'Reset workflow'

            );

            $conn->commit();

            return true;
        } catch (Exception $e) {

            $conn->rollback();

            throw $e;
        }
    }


    /**
     * Kelas yang menunggu Guru Mapel
     */
    public static function pendingMapel(

        mysqli $conn,

        string $tanggal

    ): array {

        $data = [];

        $stmt = $conn->prepare("

            SELECT

                vk.*,

                k.nama_kelas

            FROM verifikasi_kelas vk

            INNER JOIN kelas k

                ON k.id=vk.kelas_id

            WHERE

                vk.tanggal=?

            AND

                vk.status=?

            ORDER BY

                k.nama_kelas

        ");

        $status = self::STATUS_SEKRETARIS;

        $stmt->bind_param(
            "ss",
            $tanggal,
            $status
        );

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $data[] = $row;
        }

        return $data;
    }


    /**
     * Kelas yang menunggu Guru Piket
     */
    public static function pendingPiket(

        mysqli $conn,

        string $tanggal

    ): array {

        $data = [];

        $stmt = $conn->prepare("

            SELECT

                vk.*,

                k.nama_kelas

            FROM verifikasi_kelas vk

            INNER JOIN kelas k

                ON k.id=vk.kelas_id

            WHERE

                vk.tanggal=?

            AND

                vk.status=?

            ORDER BY

                k.nama_kelas

        ");

        $status = self::STATUS_GURU;

        $stmt->bind_param(
            "ss",
            $tanggal,
            $status
        );

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $data[] = $row;
        }

        return $data;
    }
    public static function isFinished(
        mysqli $conn,
        int $kelasId,
        string $tanggal
    ): bool {

        return self::status(
            $conn,
            $kelasId,
            $tanggal
        ) === self::STATUS_SELESAI;
    }

    public static function canVerify(
        mysqli $conn,
        int $kelasId,
        string $tanggal,
        string $expectedStatus
    ): bool {

        return self::status(
            $conn,
            $kelasId,
            $tanggal
        ) === $expectedStatus;
    }
}
