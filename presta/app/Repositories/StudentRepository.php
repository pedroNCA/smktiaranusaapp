<?php

class StudentRepository
{
    /**
     * Total seluruh siswa
     */
    public static function countAll(mysqli $conn): int
    {
        $sql = "SELECT COUNT(*) total FROM siswa";

        $result = $conn->query($sql);

        return (int)$result->fetch_assoc()['total'];
    }

    /**
     * Total siswa berdasarkan kelas
     */
    public static function countByClass(
        mysqli $conn,
        int $kelasId
    ): int {

        $stmt = $conn->prepare("
            SELECT COUNT(*) total
            FROM siswa
            WHERE kelas_id = ?
        ");

        $stmt->bind_param(
            "i",
            $kelasId
        );

        $stmt->execute();

        return (int)$stmt
            ->get_result()
            ->fetch_assoc()['total'];
    }

    /**
     * Semua siswa per kelas
     */
    public static function getByClass(
        mysqli $conn,
        int $kelasId
    ): array {

        $stmt = $conn->prepare("
            SELECT
                id,
                nis,
                nama_siswa,
                jenis_kelamin,
                status
            FROM siswa
            WHERE kelas_id=?
            ORDER BY nama_siswa ASC
        ");

        $stmt->bind_param(
            "i",
            $kelasId
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cari siswa
     */
    public static function search(
        mysqli $conn,
        string $keyword
    ): array {

        $keyword = "%{$keyword}%";

        $stmt = $conn->prepare("
            SELECT
                *
            FROM siswa
            WHERE
                nis LIKE ?
            OR
                nama_siswa LIKE ?
            ORDER BY nama_siswa ASC
        ");

        $stmt->bind_param(
            "ss",
            $keyword,
            $keyword
        );

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cari berdasarkan ID
     */
    public static function find(
        mysqli $conn,
        int $id
    ): ?array {

        $stmt = $conn->prepare("
            SELECT *
            FROM siswa
            WHERE id=?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 0) {

            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * Tambah siswa
     */
    public static function create(
        mysqli $conn,
        array $data
    ): bool {

        $stmt = $conn->prepare("

            INSERT INTO siswa

            (

                nis,

                nama_siswa,

                jenis_kelamin,

                kelas_id,

                status

            )

            VALUES

            (?,?,?,?,?)

        ");

        $stmt->bind_param(

            "sssis",

            $data['nis'],

            $data['nama_siswa'],

            $data['jenis_kelamin'],

            $data['kelas_id'],

            $data['status']

        );

        return $stmt->execute();
    }

    /**
     * Update siswa
     */
    public static function update(
        mysqli $conn,
        int $id,
        array $data
    ): bool {

        $stmt = $conn->prepare("

            UPDATE siswa

            SET

                nis=?,

                nama_siswa=?,

                jenis_kelamin=?,

                kelas_id=?,

                status=?

            WHERE id=?

        ");

        $stmt->bind_param(

            "sssisi",

            $data['nis'],

            $data['nama_siswa'],

            $data['jenis_kelamin'],

            $data['kelas_id'],

            $data['status'],

            $id

        );

        return $stmt->execute();
    }

    /**
     * Hapus siswa
     */
    public static function delete(
        mysqli $conn,
        int $id
    ): bool {

        $stmt = $conn->prepare("
            DELETE FROM siswa
            WHERE id=?
        ");

        $stmt->bind_param(
            "i",
            $id
        );

        return $stmt->execute();
    }
}
