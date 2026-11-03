<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid.']);
    exit;
}

if (empty($_POST['data'])) {
    echo json_encode(['success' => false, 'message' => 'Data kosong.']);
    exit;
}

$data = json_decode($_POST['data'], true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Format data tidak valid.']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    $sql = "UPDATE absensi SET status = ?, jam_datang = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    foreach ($data as $row) {
        $id = (int)$row['absensi_id'];
        $status = trim($row['status']);
        $jam = isset($row['jam']) ? trim($row['jam']) : '';

        if ($jam === '') {
            $jam = null;
        }

        mysqli_stmt_bind_param($stmt, "ssi", $status, $jam, $id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Perubahan berhasil disimpan.'
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>