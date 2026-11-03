<?php
// Pastikan session sudah aktif dan koneksi ada
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id   = $_POST['siswa_id'] ?? null;
    $status     = $_POST['status'] ?? null;
    $jam_datang = $_POST['jam_datang'] ?? null;
    $tanggal    = date('Y-m-d');

    if (!$siswa_id) {
        http_response_code(400);
        echo "Siswa ID tidak valid.";
        exit;
    }

    // Sanitasi: Jika status bukan Terlambat atau jam kosong, pastikan menjadi NULL murni
    if ($status !== 'Terlambat' || empty(trim($jam_datang))) {
        $jam_datang = null;
    }

    // Cek apakah data absensi siswa pada tanggal tersebut sudah ada
    $stmt = $conn->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $siswa_id, $tanggal);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Jika status kosong/direset, hapus dari database atau update jadi kosong
    if (empty($status)) {
        if ($existing) {
            $stmt = $conn->prepare("DELETE FROM absensi WHERE siswa_id = ? AND tanggal = ?");
            $stmt->bind_param("is", $siswa_id, $tanggal);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        if ($existing) {
            // Update data absensi
            $stmt = $conn->prepare("UPDATE absensi SET status = ?, jam_datang = ? WHERE siswa_id = ? AND tanggal = ?");
            $stmt->bind_param("ssis", $status, $jam_datang, $siswa_id, $tanggal);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert data absensi baru
            $stmt = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, status, jam_datang) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $siswa_id, $tanggal, $status, $jam_datang);
            $stmt->execute();
            $stmt->close();
        }
    }

    http_response_code(200);
    echo "Berhasil disimpan";
    exit;
}