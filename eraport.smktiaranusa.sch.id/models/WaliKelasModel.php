<?php
class WaliKelasModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllWaliKelas() {
        $stmt = $this->db->query("
            SELECT wali_kelas.*, guru.nama_guru 
            FROM wali_kelas 
            INNER JOIN guru ON wali_kelas.guru_id = guru.id
            ORDER BY wali_kelas.kelas ASC
        ");
        return $stmt->fetchAll();
    }

    public function tambahWaliKelas($guru_id, $kelas, $tahun_pelajaran) {
        $stmt = $this->db->prepare("INSERT INTO wali_kelas (guru_id, kelas, tahun_pelajaran) VALUES (:guru_id, :kelas, :tahun_pelajaran)");
        return $stmt->execute([
            'guru_id' => $guru_id,
            'kelas' => $kelas,
            'tahun_pelajaran' => $tahun_pelajaran
        ]);
    }
}