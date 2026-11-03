<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::piket();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangkap data JSON yang dikirim melalui fetch body (JSON.stringify)
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Fallback jika dikirim via $_POST biasa
    $kelas_id = $inputData['kelas_id'] ?? $_POST['kelas_id'] ?? null;
    $tanggal  = $inputData['tanggal'] ?? $_POST['tanggal'] ?? date('Y-m-d');
    $absensi_data = $inputData['absensi'] ?? $_POST['absensi'] ?? [];

    if (!$kelas_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Kelas tidak valid.']);
        exit;
    }

    // Mulai Database Transaction untuk keamanan data
    $conn->begin_transaction();

    try {
        // 1. Simpan / Update data absensi setiap siswa
        foreach ($absensi_data as $row) {
            $siswa_id   = $row['siswa_id'] ?? null;
            $status     = $row['status'] ?? null;
            $jam_datang = $row['jam_datang'] ?? null;

            if (!$siswa_id) continue;

            if ($status !== 'Terlambat' || empty(trim($jam_datang))) {
                $jam_datang = null;
            }

            // Cek apakah sudah ada data absensi
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
        }

        // 2. Update atau Insert status verifikasi_kelas menjadi 'selesai'
        $stmt = $conn->prepare("SELECT id FROM verifikasi_kelas WHERE kelas_id = ? AND tanggal = ?");
        $stmt->bind_param("is", $kelas_id, $tanggal);
        $stmt->execute();
        $v_existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $piket_id = Auth::id(); // Mengambil ID user piket yang login

        if ($v_existing) {
            $stmt = $conn->prepare("UPDATE verifikasi_kelas SET status = 'selesai', piket_id = ?, updated_at = NOW() WHERE kelas_id = ? AND tanggal = ?");
            $stmt->bind_param("iis", $piket_id, $kelas_id, $tanggal);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO verifikasi_kelas (kelas_id, tanggal, status, piket_id) VALUES (?, ?, 'selesai', ?)");
            $stmt->bind_param("isi", $kelas_id, $tanggal, $piket_id);
            $stmt->execute();
            $stmt->close();
        }

        // Commit transaksi jika semua sukses
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Presensi berhasil disimpan dan difinalisasi.']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit;
}