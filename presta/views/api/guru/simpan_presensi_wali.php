<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::guru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id   = $_POST['siswa_id'] ?? null;
    $status     = $_POST['status'] ?? null;
    $jam_datang = $_POST['jam_datang'] ?? null;
    $tanggal    = date('Y-m-d');

    if (!$siswa_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Siswa ID tidak valid.']);
        exit;
    }

    // SANITASI JAM DATANG:
    // Jika status bukan Terlambat atau string jam kosong, pastikan nilainya NULL murni agar tidak masuk sebagai 00:00:00
    if ($status !== 'Terlambat' || empty(trim($jam_datang))) {
        $jam_datang = null;
    }

    // Cek apakah data absensi siswa pada tanggal tersebut sudah ada
    $stmt = $conn->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $siswa_id, $tanggal);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($status) || $status === 'Belum ada keterangan') {
        if ($existing) {
            $stmt = $conn->prepare("DELETE FROM absensi WHERE siswa_id = ? AND tanggal = ?");
            $stmt->bind_param("is", $siswa_id, $tanggal);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        if ($existing) {
            // Perhatikan tipe binding parameter: "ssis" berarti string, string, integer, string
            // Jika $jam_datang bernilai null, bind_param dengan tipe "s" di mysqli akan otomatis mengirimkan SQL NULL jika variabelnya bernilai null.
            $stmt = $conn->prepare("UPDATE absensi SET status = ?, jam_datang = ? WHERE siswa_id = ? AND tanggal = ?");
            $stmt->bind_param("ssis", $status, $jam_datang, $siswa_id, $tanggal);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, status, jam_datang) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $siswa_id, $tanggal, $status, $jam_datang);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Berhasil disimpan']);
    exit;
}