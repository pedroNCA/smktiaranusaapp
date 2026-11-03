<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Unauthorized');
}

$userId = $_SESSION['user_id'];
$tanggalHariIni = date('Y-m-d');

// Ambil data kelas wali
$stmt = $conn->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$kelasData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelasData) {
    exit('Bukan Wali Kelas');
}

// Query data siswa & absensi harian
$querySiswa = $conn->prepare("
    SELECT 
        s.nis, s.nama_siswa as nama_lengkap, 
        COALESCE(a.status, 'Belum ada keterangan') as status, 
        a.jam_datang, a.keterangan
    FROM siswa s
    LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal = ?
    WHERE s.kelas_id = ?
    ORDER BY s.nama_siswa ASC
");
$querySiswa->bind_param("si", $tanggalHariIni, $kelasData['id']);
$querySiswa->execute();
$listSiswa = $querySiswa->get_result()->fetch_all(MYSQLI_ASSOC);
$querySiswa->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Harian - <?= htmlspecialchars($kelasData['nama_kelas']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>REKAP KEHADIRAN SISWA HARI INI</h2>
        <p>Kelas: <strong><?= htmlspecialchars($kelasData['nama_kelas']) ?></strong></p>
        <p>Tanggal: <?= date('d-m-Y') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Status</th>
                <th>Jam Datang</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($listSiswa as $s): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($s['nis']) ?></td>
                <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($s['status']) ?></td>
                <td><?= htmlspecialchars($s['jam_datang'] ?? '-') ?></td>
                <td><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>