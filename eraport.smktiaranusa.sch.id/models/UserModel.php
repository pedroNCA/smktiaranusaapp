<?php
class UserModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function createUser($guru_id, $username, $password, $nama, $role, $is_walikelas) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (guru_id, username, password, nama, role, is_walikelas) 
            VALUES (:guru_id, :username, :password, :nama, :role, :is_walikelas)
        ");
        return $stmt->execute([
            'guru_id' => $guru_id,
            'username' => $username,
            'password' => $hashed_password,
            'nama' => $nama,
            'role' => $role,
            'is_walikelas' => $is_walikelas
        ]);
    }
}