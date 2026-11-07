<?php
// proses/aksi_master_siswa.php
require_once __DIR__ . '/../config/session.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $nis = encryptData($_POST['nis']);
    $nama = $_POST['nama'];
    $kelas = $_POST['kelas'];
    $jk = $_POST['jenis_kelamin'];

    $stmt = $pdo->prepare("INSERT INTO master_siswa (nis, nama, kelas, jenis_kelamin) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nis, $nama, $kelas, $jk]);
    header("Location: ../pages/bk/master_siswa.php?msg=added");
    exit;

} elseif ($action === 'edit') {
    $id = (int)$_POST['id'];
    $nis = encryptData($_POST['nis']);
    $nama = $_POST['nama'];
    $kelas = $_POST['kelas'];
    $jk = $_POST['jenis_kelamin'];

    $stmt = $pdo->prepare("UPDATE master_siswa SET nis=?, nama=?, kelas=?, jenis_kelamin=? WHERE id=?");
    $stmt->execute([$nis, $nama, $kelas, $jk, $id]);
    header("Location: ../pages/bk/master_siswa.php?msg=updated");
    exit;

} elseif ($action === 'delete') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM master_siswa WHERE id=?");
    $stmt->execute([$id]);
    header("Location: ../pages/bk/master_siswa.php?msg=deleted");
    exit;

} elseif ($action === 'bulk_delete') {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids)) {
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM master_siswa WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    }
    header("Location: ../pages/bk/master_siswa.php?msg=deleted");
    exit;
}