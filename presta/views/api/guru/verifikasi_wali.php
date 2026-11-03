<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::guru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = $_POST['kelas_id'] ?? null;
    $tanggal  = date('Y-m-d');
    $guru_id  = Auth::id(); // Ambil ID guru yang sedang login

    if (!$kelas_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Kelas tidak valid.']);
        exit;
    }

    // Update status verifikasi_kelas serta simpan guru_id dan waktu verifikasi
    $stmt = $conn->prepare("UPDATE verifikasi_kelas SET status = 'guru', guru_id = ?, guru_at = NOW() WHERE kelas_id = ? AND tanggal = ?");
    $stmt->bind_param("iis", $guru_id, $kelas_id, $tanggal);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Verifikasi berhasil disimpan.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
    }
    $stmt->close();
    exit;
}
