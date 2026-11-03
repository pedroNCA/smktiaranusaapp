<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Auth.php';
require_once __DIR__ . '/../app/Services/AttendanceService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

Auth::sekretaris();

$aksi = $_POST['aksi'] ?? 'submit'; // 'submit' atau 'init_hadir_semua'
$kelasId = (int)($_POST['kelas_id'] ?? 0);
$tanggal = date('Y-m-d');

try {
    if ($kelasId <= 0) {
        throw new Exception("ID Kelas tidak valid.");
    }

    if ($aksi === 'submit') {
        // Logika 1: Submit Presensi via Service
        AttendanceService::submitPresensi($conn, $kelasId, Auth::id(), $tanggal);
        
        echo json_encode(['success' => true]);

    } elseif ($aksi === 'init_hadir_semua') {
        // Logika 2: Inisialisasi absensi massal (Default Hadir)
        if (!AttendanceService::canEdit($conn, $kelasId, $tanggal)) {
            throw new Exception('Presensi sudah dikirim, tidak dapat diubah.');
        }

        $conn->begin_transaction();

        // Buat data absensi yang belum ada
        $stmt = $conn->prepare("
            INSERT INTO absensi (siswa_id, tanggal, status, jam_datang)
            SELECT s.id, ?, 'Hadir', NULL
            FROM siswa s
            WHERE s.kelas_id = ? AND s.status = 'Aktif'
            AND NOT EXISTS(
                SELECT 1 FROM absensi a WHERE a.siswa_id = s.id AND a.tanggal = ?
            )
        ");
        $stmt->bind_param("sis", $tanggal, $kelasId, $tanggal);
        $stmt->execute();

        // Update seluruh data menjadi Hadir
        $stmt2 = $conn->prepare("
            UPDATE absensi a
            INNER JOIN siswa s ON s.id = a.siswa_id
            SET a.status = 'Hadir', a.jam_datang = NULL
            WHERE s.kelas_id = ? AND s.status = 'Aktif' AND a.tanggal = ?
        ");
        $stmt2->bind_param("is", $kelasId, $tanggal);
        $stmt2->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Aksi tidak dikenali.");
    }

} catch (Exception $e) {
    // Hanya rollback jika ada transaksi yang aktif (error pada blok init_hadir_semua)
    if (isset($conn) && $conn->errno !== 0) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>