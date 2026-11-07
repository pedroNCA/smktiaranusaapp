<?php
class MapelModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllMapel() {
        $stmt = $this->db->query("
            SELECT mata_pelajaran.*, guru.nama_guru 
            FROM mata_pelajaran 
            LEFT JOIN guru ON mata_pelajaran.guru_id = guru.id
            ORDER BY mata_pelajaran.kelompok_mapel ASC, mata_pelajaran.nama_mapel ASC
        ");
        return $stmt->fetchAll();
    }

    public function tambahMapel($kelompok, $nama, $guru_id = null) {
        $stmt = $this->db->prepare("INSERT INTO mata_pelajaran (kelompok_mapel, nama_mapel, guru_id) VALUES (:kelompok, :nama, :guru_id)");
        return $stmt->execute([
            'kelompok' => $kelompok,
            'nama' => $nama,
            'guru_id' => $guru_id
        ]);
    }
}