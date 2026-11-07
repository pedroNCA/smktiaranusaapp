<?php
class GuruModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Mengambil semua data guru
    public function getAllGuru() {
        $stmt = $this->db->prepare("SELECT * FROM guru ORDER BY nama_guru ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Menambah data guru baru
    public function tambahGuru($nip, $nama_guru, $username, $password) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO guru (nip, nama_guru, username, password) VALUES (:nip, :nama, :username, :password)");
        return $stmt->execute([
            'nip' => $nip,
            'nama' => $nama_guru,
            'username' => $username,
            'password' => $hashed_password
        ]);
    }
}