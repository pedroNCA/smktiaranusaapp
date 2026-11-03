<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::sekretaris();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = $_POST['kelas_id'] ?? null;
    $tanggal = date('Y-m-d');

    if (!$kelas_id) {
        http_response_code(400);
        echo "ID Kelas tidak valid.";
        exit;
    }

    // Cek apakah data verifikasi kelas sudah ada hari ini
    $stmt = $conn->prepare("SELECT id FROM verifikasi_kelas WHERE kelas_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $kelas_id, $tanggal);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Update status verifikasi menjadi 'sekretaris' (telah dikirim oleh sekretaris)
        $stmt = $conn->prepare("UPDATE verifikasi_kelas SET status = 'sekretaris' WHERE kelas_id = ? AND tanggal = ?");
        $stmt->bind_param("is", $kelas_id, $tanggal);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert record baru verifikasi kelas
        $stmt = $conn->prepare("INSERT INTO verifikasi_kelas (kelas_id, tanggal, status) VALUES (?, ?, 'sekretaris')");
        $stmt->bind_param("is", $kelas_id, $tanggal);
        $stmt->execute();
        $stmt->close();
    }

    http_response_code(200);
    echo "Presensi berhasil dikirim.";
    exit;
}