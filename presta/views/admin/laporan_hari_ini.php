<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

// Keamanan: Pastikan yang akses adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php?route=auth/login");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$tanggal_hari_ini = date('Y-m-d');

// Ambil data rekap seluruh kelas untuk hari ini
$sql = "
SELECT 
    k.id AS kelas_id,
    k.nama_kelas,
    COUNT(s.id) AS total_siswa,
    SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
    SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS izin,
    SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
    SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) AS alfa,
    SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat
FROM kelas k
LEFT JOIN siswa s ON s.kelas_id = k.id
LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
GROUP BY k.id, k.nama_kelas
ORDER BY k.nama_kelas ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $tanggal_hari_ini);
$stmt->execute();
$rekapKelas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Hari Ini - PRESTA Admin</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
</head>

<body class="bg-slate-50 antialiased text-gray-800">
    <!-- Wrapper Utama Layout Admin -->
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Header & Tombol Download -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Rekap Presensi Hari Ini</h2>
                            <p class="text-xs text-gray-500 mt-1">Tanggal: <?= DateHelper::tanggal($tanggal_hari_ini) ?></p>
                        </div>
                        <a href="index.php?route=admin/export_laporan_hari_ini" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Excel Seluruh Kelas (.xlsx)
                        </a>
                    </div>

                    <!-- Tabel Preview Rekap Per Kelas -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Ringkasan Kehadiran Tiap Kelas</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Akumulasi kehadiran seluruh siswa berdasarkan kelas hari ini.</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table id="tabelRekapHariIni" class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-4" width="60">No</th>
                                        <th class="py-3 px-4">Nama Kelas</th>
                                        <th class="py-3 px-4 text-center">Total Siswa</th>
                                        <th class="py-3 px-4 text-center">Hadir</th>
                                        <th class="py-3 px-4 text-center">Izin</th>
                                        <th class="py-3 px-4 text-center">Sakit</th>
                                        <th class="py-3 px-4 text-center">Alfa</th>
                                        <th class="py-3 px-4 text-center">Terlambat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                    <?php if (empty($rekapKelas)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-8 text-gray-400">Belum ada data kelas.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1;
                                        foreach ($rekapKelas as $row): ?>
                                            <tr class="hover:bg-slate-50/60 transition-colors">
                                                <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                                <td class="py-4 px-4 text-gray-900 font-bold"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                                <td class="py-4 px-4 text-center"><?= $row['total_siswa'] ?></td>
                                                <td class="py-4 px-4 text-center text-emerald-600 font-bold"><?= $row['hadir'] ?></td>
                                                <td class="py-4 px-4 text-center text-blue-600"><?= $row['izin'] ?></td>
                                                <td class="py-4 px-4 text-center text-purple-600"><?= $row['sakit'] ?></td>
                                                <td class="py-4 px-4 text-center text-red-600"><?= $row['alfa'] ?></td>
                                                <td class="py-4 px-4 text-center text-amber-600"><?= $row['terlambat'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#tabelRekapHariIni').DataTable({
                responsive: true,
                pageLength: 10,
                autoWidth: false,
                language: {
                    search: "",
                    searchPlaceholder: "Cari kelas...",
                    zeroRecords: "Tidak ada data.",
                    info: "Menampilkan _TOTAL_ data",
                    infoEmpty: "",
                    paginate: { previous: "←", next: "→" }
                }
            });
        });
    </script>
</body>
</html>