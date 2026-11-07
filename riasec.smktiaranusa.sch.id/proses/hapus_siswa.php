<?php
// proses/hapus_siswa.php

require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Karena tabel siswa sudah dihapus dan identitas asli ada di master_siswa,
        // kita HANYA menghapus riwayat pengisiannya dari tabel hasil_asesmen.
        // Hal ini memungkinkan siswa tersebut untuk mengisi ulang asesmen di kemudian hari.
        $stmt = $pdo->prepare("DELETE FROM hasil_asesmen WHERE siswa_id = ?");
        $stmt->execute([$id]);

        // Redirect kembali ke halaman daftar asesmen dengan pesan sukses
        header("Location: ../pages/bk/asesmen.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        die("Gagal menghapus data: " . $e->getMessage());
    }
} else {
    die("ID tidak valid.");
}
