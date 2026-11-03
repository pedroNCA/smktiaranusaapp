<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$sumber_modul = $_POST['sumber_modul'] ?? 'guru'; // 'guru' atau 'piket'
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID verifikasi tidak valid']);
    exit;
}

// Cek otorisasi berdasarkan modul
if ($sumber_modul === 'piket') {
    Auth::piket();
    $statusUpdate = 'selesai';
    $fieldUser = 'piket_id';
    $fieldTime = 'piket_at';
    $level = 'piket';
} else {
    Auth::guru();
    $statusUpdate = 'guru';
    $fieldUser = 'guru_id';
    $fieldTime = 'guru_at';
    $level = 'guru';
}

$conn->begin_transaction();

try {
    // 1. Ambil data verifikasi kelas
    $stmt = $conn->prepare("SELECT kelas_id, tanggal FROM verifikasi_kelas WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        throw new Exception("Data verifikasi tidak ditemukan");
    }

    // 2. Hitung total siswa aktif di kelas tersebut
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM siswa WHERE kelas_id=?");
    $stmt->bind_param("i", $data['kelas_id']);
    $stmt->execute();
    $totalSiswa = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // 3. Hitung absensi yang sudah diinput
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM absensi a 
        JOIN siswa s ON s.id = a.siswa_id 
        WHERE s.kelas_id=? AND a.tanggal=?
    ");
    $stmt->bind_param("is", $data['kelas_id'], $data['tanggal']);
    $stmt->execute();
    $totalAbsen = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // 4. Validasi Kelengkapan
    if ($totalSiswa != $totalAbsen) {
        throw new Exception("Gagal! Masih ada siswa yang belum diinput presensinya.");
    }

    $userId = Auth::id();

    // 5. Update tabel verifikasi_kelas
    $sql_update = "UPDATE verifikasi_kelas SET status=?, $fieldUser=?, $fieldTime=NOW() WHERE id=?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sii", $statusUpdate, $userId, $id);
    $stmt->execute();
    $stmt->close();

    // 6. Masukkan log ke absensi_verifikasi
    $sql_insert = "
        INSERT INTO absensi_verifikasi (absensi_id, level, verified_by, verified_at)
        SELECT a.id, ?, ?, NOW()
        FROM absensi a
        JOIN siswa s ON s.id = a.siswa_id
        WHERE s.kelas_id = ? AND a.tanggal = ?
    ";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("siis", $level, $userId, $data['kelas_id'], $data['tanggal']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
