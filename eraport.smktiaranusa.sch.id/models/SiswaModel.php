<?php
class SiswaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllSiswa() {
        $stmt = $this->db->query("SELECT * FROM siswa ORDER BY kelas ASC, nama_siswa ASC");
        return $stmt->fetchAll();
    }

    public function tambahSiswa($nis, $nisn, $nama, $kelas, $fase, $semester) {
        $stmt = $this->db->prepare("INSERT INTO siswa (nis, nisn, nama_siswa, kelas, fase, semester) VALUES (:nis, :nisn, :nama, :kelas, :fase, :semester)");
        return $stmt->execute([
            'nis' => $nis,
            'nisn' => $nisn,
            'nama' => $nama,
            'kelas' => $kelas,
            'fase' => $fase,
            'semester' => $semester
        ]);
    }
}