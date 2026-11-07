<?php
class AbsensiModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAbsensiBySiswa($siswa_id, $semester, $tahun_pelajaran) {
        $stmt = $this->db->prepare("SELECT * FROM absensi WHERE siswa_id = :siswa_id AND semester = :semester AND tahun_pelajaran = :tahun_pelajaran");
        $stmt->execute([
            'siswa_id' => $siswa_id,
            'semester' => $semester,
            'tahun_pelajaran' => $tahun_pelajaran
        ]);
        return $stmt->fetch();
    }

    public function simpanAbsensi($siswa_id, $sakit, $izin, $alpa, $semester, $tahun) {
        $stmt = $this->db->prepare("
            INSERT INTO absensi (siswa_id, sakit, izin, tanpa_keterangan, semester, tahun_pelajaran) 
            VALUES (:siswa_id, :sakit, :izin, :alpa, :semester, :tahun)
            ON DUPLICATE KEY UPDATE sakit = :sakit, izin = :izin, tanpa_keterangan = :alpa
        ");
        return $stmt->execute([
            'siswa_id' => $siswa_id,
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
            'semester' => $semester,
            'tahun' => $tahun
        ]);
    }
}