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

$userRole = $_SESSION['role'] ?? 'admin';
$isGuru = $userRole === 'guru' || $userRole === 'admin';
$isPiket = $userRole === 'piket' || $userRole === 'admin';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?route=admin/presensi");
    exit;
}

$id = (int) $_GET['id'];
$sql = "
SELECT
    vk.id,
    vk.kelas_id,
    vk.status,
    vk.tanggal,
    vk.guru_id,
    vk.guru_at,
    vk.piket_id,
    vk.piket_at,
    k.nama_kelas,
    u.nama_lengkap AS nama_wali
FROM verifikasi_kelas vk
JOIN kelas k ON k.id = vk.kelas_id
LEFT JOIN users u ON u.id = k.wali_kelas_id
WHERE vk.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$kelas = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelas) {
    header("Location: index.php?route=admin/presensi");
    exit;
}

$sql = "
SELECT
    s.id,
    s.nis,
    s.nama_siswa AS nama,
    s.jenis_kelamin,
    a.id AS absensi_id,
    a.status,
    a.jam_datang
FROM siswa s
LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
WHERE s.kelas_id = ?
ORDER BY s.nama_siswa
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $kelas['tanggal'], $kelas['kelas_id']);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$jumlahSiswa = count($siswa);
$totalHadir = 0;
$totalIzin = 0;
$totalSakit = 0;
$totalAlfa = 0;
$totalTerlambat = 0;

foreach ($siswa as $item) {
    switch ($item['status']) {
        case 'Hadir': $totalHadir++; break;
        case 'Izin': $totalIzin++; break;
        case 'Sakit': $totalSakit++; break;
        case 'Alfa': $totalAlfa++; break;
        case 'Terlambat': $totalTerlambat++; break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Presensi <?= htmlspecialchars($kelas['nama_kelas']) ?> - PRESTA Admin</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
</head>

<body class="bg-slate-50 antialiased text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Header Hero & Info Kelas -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                        <div class="space-y-4">
                            <a href="index.php?route=admin/presensi" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-gray-700 text-xs font-bold rounded-xl transition-colors">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($kelas['nama_kelas']) ?></h2>
                                <p class="text-xs text-gray-500 mt-1">Detail dan verifikasi data kehadiran siswa kelas.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-gray-600 bg-slate-50 px-4 py-2.5 rounded-xl border border-gray-100">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-calendar-alt text-brand-600"></i>
                                    <span><?= DateHelper::tanggal($kelas['tanggal']) ?></span>
                                </div>
                                <div class="h-4 w-[1px] bg-gray-200"></div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-user-tie text-brand-600"></i>
                                    <span>Wali Kelas: <strong><?= htmlspecialchars($kelas['nama_wali'] ?: '-') ?></strong></span>
                                </div>
                                <div class="h-4 w-[1px] bg-gray-200"></div>
                                <div class="flex items-center gap-1.5 text-brand-600">
                                    <i class="fas fa-clock"></i>
                                    <span id="jamRealtime">--:--:-- WIB</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-brand-50 border border-brand-100 p-5 rounded-2xl text-center min-w-[150px] shrink-0">
                            <span class="block text-3xl font-extrabold text-brand-600"><?= $jumlahSiswa ?></span>
                            <span class="block text-[11px] font-bold text-brand-700 uppercase tracking-wider mt-1">Total Siswa</span>
                        </div>
                    </div>

                    <!-- Workflow Verifikasi -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Workflow Verifikasi</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Status proses presensi hari ini.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                            <?php $status = $kelas['status']; ?>

                            <div class="flex items-center gap-3 p-4 rounded-xl border <?= in_array($status, ['sekretaris', 'guru', 'selesai']) ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : 'bg-slate-50 border-gray-100 text-gray-400' ?>">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 <?= in_array($status, ['sekretaris', 'guru', 'selesai']) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' ?>">
                                    <?= in_array($status, ['sekretaris', 'guru', 'selesai']) ? '<i class="fas fa-check"></i>' : '1' ?>
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold uppercase tracking-wider block opacity-75">Tahap 1</span>
                                    <h4 class="font-bold text-sm">Sekretaris</h4>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 rounded-xl border <?= in_array($status, ['guru', 'selesai']) ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : ($status == 'sekretaris' ? 'bg-amber-50/50 border-amber-200 text-amber-900' : 'bg-slate-50 border-gray-100 text-gray-400') ?>">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 <?= in_array($status, ['guru', 'selesai']) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' ?>">
                                    <?= in_array($status, ['guru', 'selesai']) ? '<i class="fas fa-check"></i>' : '2' ?>
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold uppercase tracking-wider block opacity-75">Tahap 2</span>
                                    <h4 class="font-bold text-sm">Wali Kelas / Guru Mapel</h4>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 rounded-xl border <?= $status == 'selesai' ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : 'bg-slate-50 border-gray-100 text-gray-400' ?>">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 <?= $status == 'selesai' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' ?>">
                                    <?= $status == 'selesai' ? '<i class="fas fa-check"></i>' : '3' ?>
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold uppercase tracking-wider block opacity-75">Tahap 3</span>
                                    <h4 class="font-bold text-sm">Guru Piket</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards Kehadiran -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Hadir</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $totalHadir ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Izin</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $totalIzin ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-notes-medical"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Sakit</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $totalSakit ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Alfa</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $totalAlfa ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Terlambat</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $totalTerlambat ?></h2>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar Tabel Siswa -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
                            <div>
                                <h3 class="font-bold text-gray-900 text-base">Data Presensi Siswa</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Kelola dan verifikasi kehadiran siswa.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-4" width="60">No</th>
                                        <th class="py-3 px-4" width="120">NIS</th>
                                        <th class="py-3 px-4 min-w-[180px]">Nama Siswa</th>
                                        <th class="py-3 px-4 min-w-[160px]">Status</th>
                                        <th class="py-3 px-4 min-w-[140px]">Jam</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                    <?php
                                    $no = 1;
                                    foreach ($siswa as $row):
                                    ?>
                                        <tr class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                            <td class="py-4 px-4 text-gray-900 font-bold"><?= htmlspecialchars($row['nis']) ?></td>
                                            <td class="py-4 px-4 text-gray-900"><?= htmlspecialchars($row['nama']) ?></td>
                                            <td class="py-4 px-4 font-semibold text-gray-700">
                                                <?= htmlspecialchars($row['status'] ?? 'Belum ada keterangan') ?>
                                            </td>
                                            <td class="py-4 px-4 text-gray-600">
                                                <?= !empty($row['jam_datang']) ? date('H:i:s', strtotime($row['jam_datang'])) : '-' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function updateJam() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const clockElem = document.getElementById("jamRealtime");
            if (clockElem) {
                clockElem.innerHTML = hh + ":" + mm + ":" + ss + " WIB";
            }
        }
        updateJam();
        setInterval(updateJam, 1000);
    </script>
</body>
</html>