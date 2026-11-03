<?php

class ActivityLogService
{
    /**
     * Simpan aktivitas pengguna
     */
    public static function write(
        mysqli $conn,
        int $userId,
        string $modul,
        string $aktivitas,
        ?int $referensiId = null,
        ?string $detail = null
    ): bool {

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt = $conn->prepare("
            INSERT INTO activity_logs
            (
                user_id,
                modul,
                aktivitas,
                referensi_id,
                detail,
                ip_address,
                user_agent
            )
            VALUES
            (?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ississs",
            $userId,
            $modul,
            $aktivitas,
            $referensiId,
            $detail,
            $ipAddress,
            $userAgent
        );

        return $stmt->execute();
    }

    /**
     * Ambil aktivitas terbaru
     */
    public static function latest(
        mysqli $conn,
        int $limit = 10
    ): array {

        $data = [];

        $limit = (int)$limit;

        $query = "
            SELECT
                al.*,
                u.nama_lengkap
            FROM activity_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
            ORDER BY
                al.created_at DESC
            LIMIT $limit
        ";

        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {

            $data[] = $row;
        }

        return $data;
    }
}
