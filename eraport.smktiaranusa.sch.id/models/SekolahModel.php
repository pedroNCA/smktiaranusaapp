<?php
class SekolahModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getSekolah() {
        $stmt = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        return $stmt->fetch();
    }

    public function updateSekolah($data) {
        $stmt = $this->db->prepare("
            UPDATE sekolah SET 
            nama_asesmen = :asesmen, nama_sekolah = :sekolah, nama_kepala_sekolah = :kepsek,
            tahun_pelajaran = :tahun, semester = :semester, alamat_sekolah = :alamat,
            nrks = :nrks, kkm_kktp = :kkm
            WHERE id = 1
        ");
        return $stmt->execute([
            'asesmen' => $data['nama_asesmen'],
            'sekolah' => $data['nama_sekolah'],
            'kepsek' => $data['nama_kepala_sekolah'],
            'tahun' => $data['tahun_pelajaran'],
            'semester' => $data['semester'],
            'alamat' => $data['alamat_sekolah'],
            'nrks' => $data['nrks'],
            'kkm' => $data['kkm_kktp']
        ]);
    }
}