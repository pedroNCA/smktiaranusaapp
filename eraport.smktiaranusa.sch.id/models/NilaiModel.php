<?php
class NilaiModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getNilaiBySiswa($siswa_id) {
        $stmt = $this->db->prepare("
            SELECT nilai.*, mata_pelajaran.nama_mapel, mata_pelajaran.kelompok_mapel
            FROM nilai
            INNER JOIN mata_pelajaran ON nilai.mapel_id = mata_pelajaran.id
            WHERE nilai.siswa_id = :siswa_id
        ");
        $stmt->execute(['siswa_id' => $siswa_id]);
        return $stmt->fetchAll();
    }

    public function simpanNilai($siswa_id, $mapel_id, $formatif, $sumatif, $akhir, $capaian) {
        $stmt = $this->db->prepare("
            INSERT INTO nilai (siswa_id, mapel_id, nilai_formatif, nilai_sumatif, nilai_akhir, capaian_kompetensi) 
            VALUES (:siswa_id, :mapel_id, :formatif, :sumatif, :akhir, :capaian)
            ON DUPLICATE KEY UPDATE nilai_formatif = :formatif, nilai_sumatif = :sumatif, nilai_akhir = :akhir, capaian_kompetensi = :capaian
        ");
        return $stmt->execute([
            'siswa_id' => $siswa_id,
            'mapel_id' => $mapel_id,
            'formatif' => $formatif,
            'sumatif' => $sumatif,
            'akhir' => $akhir,
            'capaian' => $capaian
        ]);
    }
}