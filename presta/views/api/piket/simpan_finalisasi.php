<?php
// Matikan tampilan error HTML di layar agar tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';

Auth::piket();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswaId = isset($_POST['siswa_id']) ? (int) $_POST['siswa_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $jamDatang = isset($_POST['jam_datang']) ? trim($_POST['jam_datang']) : null;

    if ($siswaId <= 0 || empty($status)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau ID siswa tidak valid.']);
        exit;
    }

    // 1. Ambil kelas_id dari tabel siswa
    $stmtKelas = $conn->prepare("SELECT kelas_id FROM siswa WHERE id = ? LIMIT 1");
    if (!$stmtKelas) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query database.']);
        exit;
    }
    $stmtKelas->bind_param("i", $siswaId);
    $stmtKelas->execute();
    $resKelas = $stmtKelas->get_result()->fetch_assoc();
    $stmtKelas->close();

    if (!$resKelas) {
        echo json_encode(['status' => 'error', 'message' => 'Data siswa tidak ditemukan di database.']);
        exit;
    }

    $kelasId = $resKelas['kelas_id'];

    // 2. Ambil tanggal hari ini atau tanggal verifikasi kelas yang bersangkutan
    $tanggalHariIni = date('Y-m-d');
    $stmtVk = $conn->prepare("SELECT tanggal FROM verifikasi_kelas WHERE kelas_id = ? ORDER BY id DESC LIMIT 1");
    $stmtVk->bind_param("i", $kelasId);
    $stmtVk->execute();
    $resVk = $stmtVk->get_result()->fetch_assoc();
    $stmtVk->close();

    $tanggal = $resVk['tanggal'] ?? $tanggalHariIni;

    // Normalize status 'Belum ada keterangan' menjadi kosong/null di database jika perlu
    if ($status === 'Belum ada keterangan') {
        $status = '';
    }

    // 3. Cek apakah data absensi siswa pada tanggal tersebut sudah ada
    $stmtCheck = $conn->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ? LIMIT 1");
    $stmtCheck->bind_param("is", $siswaId, $tanggal);
    $stmtCheck->execute();
    $existing = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($existing) {
        // Update absensi yang sudah ada
        $absensiId = $existing['id'];
        $stmtUpdate = $conn->prepare("UPDATE absensi SET status = ?, jam_datang = ? WHERE id = ?");
        $stmtUpdate->bind_param("ssi", $status, $jamDatang, $absensiId);
        $success = $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        // Insert absensi baru jika belum ada baris sama sekali
        $stmtInsert = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, status, jam_datang) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("isss", $siswaId, $tanggal, $status, $jamDatang);
        $success = $stmtInsert->execute();
        $stmtInsert->close();
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Presensi berhasil disimpan.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengeksekusi penyimpanan ke database.']);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}