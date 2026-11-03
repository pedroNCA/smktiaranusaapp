<?php
// Matikan error HTML agar tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::piket();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verifikasiId = isset($_POST['verifikasi_id']) ? (int) $_POST['verifikasi_id'] : 0;
    $piketId = Auth::id(); // Ambil ID Guru Piket yang sedang login

    if ($verifikasiId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID verifikasi tidak valid.']);
        exit;
    }

    // Update status verifikasi kelas menjadi 'selesai' dan catat piket_id serta piket_at
    $stmt = $conn->prepare("
        UPDATE verifikasi_kelas 
        SET status = 'selesai', 
            piket_id = ?, 
            piket_at = NOW() 
        WHERE id = ?
    ");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query database: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $piketId, $verifikasiId);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Presensi kelas berhasil difinalisasi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status finalisasi di database.']);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}