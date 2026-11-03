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
$tanggal =$_GET['tanggal'] ?? date('Y-m-d');

$sql = "
SELECT
    vk.id,
    vk.kelas_id,
    vk.status,
    vk.tanggal,
    k.nama_kelas,
    COUNT(s.id) AS jumlah_siswa
FROM verifikasi_kelas vk
INNER JOIN kelas k ON k.id = vk.kelas_id
LEFT JOIN siswa s ON s.kelas_id = k.id
WHERE vk.tanggal = ?
AND vk.status IN ('sekretaris','guru','selesai')
GROUP BY vk.id, vk.kelas_id, vk.status, vk.tanggal, k.nama_kelas
ORDER BY k.nama_kelas ASC
";

$kelasList = [];$totalSemuaKelas = 0;
$totalKelas = 0;
$belumVerifikasi = 0;
$belumFinalisasi = 0;
$sudahFinalisasi = 0;

$stmt = $conn->prepare($sql);
if ($stmt) {$stmt->bind_param('s', $tanggal);$stmt->execute();
    $result =$stmt->get_result();
    $kelasList =$result ? $result->fetch_all(MYSQLI_ASSOC) : [];$stmt->close();

    $queryTotalKelas =$conn->query("SELECT COUNT(*) AS total FROM kelas");
    if ($queryTotalKelas) {
        $totalSemuaKelas = (int)$queryTotalKelas->fetch_assoc()['total'];
    }

    $totalKelas = count($kelasList);

    foreach ($kelasList as$k) {
        switch ($k['status']) {
            case 'sekretaris':
                $belumVerifikasi++;
                break;
            case 'guru':
                $belumFinalisasi++;
                break;
            case 'selesai':
                $sudahFinalisasi++;
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Hari Ini - PRESTA Admin</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
</head>

<body class="bg-slate-50 antialiased text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Header Hero & Info -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Presensi Hari Ini</h2>
                            <p class="text-xs text-gray-500 mt-1">Kelola dan pantau presensi seluruh kelas secara real-time</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-gray-600 bg-slate-50 px-4 py-2.5 rounded-xl border border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-school text-brand-600"></i>
                                <span>Total Kelas: <strong><?= $totalSemuaKelas ?></strong></span>
                            </div>
                            <div class="h-4 w-[1px] bg-gray-200"></div>
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-brand-600"></i>
                                <span><?= DateHelper::tanggal($tanggal) ?></span>
                            </div>
                            <div class="h-4 w-[1px] bg-gray-200"></div>
                            <div class="flex items-center gap-1.5 text-brand-600">
                                <i class="fas fa-clock"></i>
                                <span id="jamRealtime">--:--:--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Belum Verifikasi</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $belumVerifikasi ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Belum Finalisasi</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $belumFinalisasi ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-flag-checkered"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Sudah Finalisasi</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $sudahFinalisasi ?></h2>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
                                <i class="fas fa-school"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Kelas Masuk</span>
                                <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= $totalKelas ?></h2>
                            </div>
                        </div>
                    </div>

                    <!-- Card Daftar Kelas -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
                            <div>
                                <h3 class="font-bold text-gray-900 text-base">Daftar Kelas</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Lihat dan kelola data presensi seluruh kelas</p>
                            </div>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input type="text" id="searchClass" class="w-full sm:w-64 h-11 border border-gray-200 rounded-xl pl-10 pr-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Cari nama kelas...">
                                </div>
                                <select id="statusFilter" class="h-11 border border-gray-200 rounded-xl px-4 text-sm font-semibold text-gray-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white cursor-pointer">
                                    <option value="all">Semua Status</option>
                                    <option value="sekretaris">Belum Verifikasi</option>
                                    <option value="guru">Belum Finalisasi</option>
                                    <option value="selesai">Sudah Finalisasi</option>
                                </select>
                            </div>
                        </div>

                        <?php if (empty($kelasList)): ?>
                            <div class="py-16 text-center bg-slate-50 rounded-2xl border border-dashed border-gray-200">
                                <div class="w-16 h-16 bg-white text-gray-400 rounded-2xl shadow-sm mx-auto flex items-center justify-center text-2xl mb-3">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <h3 class="font-bold text-gray-800 text-base">Tidak ada data</h3>
                                <p class="text-xs text-gray-500 mt-1">Belum ada data presensi pada tanggal yang dipilih.</p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="kelasContainer">
                                <?php foreach ($kelasList as$kelas): ?>
                                    <?php
                                    switch ($kelas['status']) {
                                        case 'sekretaris':
                                            $badgeBg = 'bg-amber-50 text-amber-700 border-amber-200';$badgeText = 'Belum Verifikasi';
                                            break;
                                        case 'guru':
                                            $badgeBg = 'bg-blue-50 text-blue-700 border-blue-200';$badgeText = 'Belum Finalisasi';
                                            break;
                                        case 'selesai':
                                            $badgeBg = 'bg-emerald-50 text-emerald-700 border-emerald-200';$badgeText = 'Sudah Finalisasi';
                                            break;
                                        default:
                                            $badgeBg = 'bg-gray-50 text-gray-700 border-gray-200';$badgeText = '-';
                                    }
                                    ?>

                                    <div class="kelas-card bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between gap-4"
                                        data-status="<?= $kelas['status'] ?>"
                                        data-kelas="<?= strtolower($kelas['nama_kelas']) ?>">

                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-base shrink-0">
                                                    <i class="fas fa-school"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-gray-900 text-base"><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                                                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $badgeBg ?>">
                                                        <?= $badgeText ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-2 pt-2 border-t border-gray-50 text-xs text-gray-500 font-medium">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-users w-4 text-gray-400"></i>
                                                <span><?= $kelas['jumlah_siswa'] ?> Siswa</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-calendar-alt w-4 text-gray-400"></i>
                                                <span><?= DateHelper::tanggal($kelas['tanggal']) ?></span>
                                            </div>
                                        </div>

                                        <div>
                                            <a href="index.php?route=admin/presensi_detail&id=<?= $kelas['id'] ?>" class="w-full h-10 inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                                <i class="fas fa-arrow-right"></i> Lihat Detail
                                            </a>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const clockElem = document.getElementById('jamRealtime');
            if (clockElem) {
                clockElem.textContent = `${hours}:${minutes}:${seconds}`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchClass');
            const statusFilter = document.getElementById('statusFilter');
            const cards = document.querySelectorAll('.kelas-card');

            function filterCards() {
                const query = searchInput.value.toLowerCase();
                const status = statusFilter.value;

                cards.forEach(card => {
                    const kelasName = card.getAttribute('data-kelas');
                    const cardStatus = card.getAttribute('data-status');

                    const matchSearch = kelasName.includes(query);
                    const matchStatus = (status === 'all' || cardStatus === status);

                    if (matchSearch && matchStatus) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            if (searchInput) searchInput.addEventListener('input', filterCards);
            if (statusFilter) statusFilter.addEventListener('change', filterCards);
        });
    </script>
</body>
</html>