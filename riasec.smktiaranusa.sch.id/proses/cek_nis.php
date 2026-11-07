<?php
// proses/cek_nis.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nis_input = isset($_GET['nis']) ? trim($_GET['nis']) : '';

if ($id && $nis_input) {
    // 1. Cek kecocokan NIS
    $stmt = $pdo->prepare("SELECT nis FROM master_siswa WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $nis_db = decryptData($row['nis']);
        
        if ($nis_input === $nis_db) {
            // 2. Jika NIS benar, cek apakah siswa sudah pernah mengerjakan
            $stmtCek = $pdo->prepare("SELECT id FROM hasil_asesmen WHERE siswa_id = ?");
            $stmtCek->execute([$id]);
            
            if ($stmtCek->fetch()) {
                echo json_encode(['valid' => false, 'message' => 'Anda sudah menyelesaikan asesmen ini sebelumnya.']);
                exit;
            }

            // Jika benar dan belum pernah mengisi
            echo json_encode(['valid' => true]);
            exit;
        } else {
            echo json_encode(['valid' => false, 'message' => 'NIS yang Anda masukkan SALAH. Silakan periksa kembali!']);
            exit;
        }
    }
}

echo json_encode(['valid' => false, 'message' => 'Data tidak valid atau tidak ditemukan.']);