<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::piket();

// ===============================
// TANGGAL
// ===============================
date_default_timezone_set('Asia/Jakarta');
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$pageTitle = 'Finalisasi Presensi';

// ===============================
// DAFTAR KELAS
// ===============================
$sql = "
SELECT
    vk.id,
    vk.kelas_id,
    vk.status,
    vk.tanggal,
    k.nama_kelas,
    COUNT(s.id) AS jumlah_siswa
FROM verifikasi_kelas vk
INNER JOIN kelas k
    ON k.id = vk.kelas_id
LEFT JOIN siswa s
    ON s.kelas_id = k.id
WHERE vk.tanggal = ?
AND vk.status IN ('guru','selesai')
GROUP BY
    vk.id,
    vk.kelas_id,
    vk.status,
    vk.tanggal,
    k.nama_kelas
ORDER BY k.nama_kelas ASC
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('s', $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    $kelasList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $kelasList = [];
}

// --- Memanggil Layout Header Global ---
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false, search: '', statusFilter: 'all' }" x-init="setTimeout(() => loaded = true, 100)">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-2 text-white">
                    Finalisasi Presensi Kelas
                </h1>
                <div class="flex items-center gap-2 text-brand-100 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="jamRealtime">--:--:-- WIB</span>
                </div>
            </div>
        </section>

        <?php
        $totalKelas = count($kelasList);
        $menunggu = 0;
        $selesai = 0;

        foreach ($kelasList as $k) {
            switch ($k['status']) {
                case 'guru':
                    $menunggu++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
            }
        }
        ?>

        <!-- STATISTIK / SUMMARY GRID -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-5 transform transition-all duration-700 delay-100 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- Menunggu Difinalisasi -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Menunggu Difinalisasi</p>
                    <h2 class="text-2xl font-extrabold text-gray-900"><?= $menunggu ?></h2>
                </div>
            </div>

            <!-- Sudah Difinalisasi -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Sudah Difinalisasi</p>
                    <h2 class="text-2xl font-extrabold text-gray-900"><?= $selesai ?></h2>
                </div>
            </div>

            <!-- Total Kelas -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Total Kelas</p>
                    <h2 class="text-2xl font-extrabold text-gray-900"><?= $totalKelas ?></h2>
                </div>
            </div>

        </section>

        <!-- KELAS SECTION -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6 transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-gray-100 mb-6">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Daftar Kelas</h2>
                    <p class="text-xs text-gray-400">Pilih kelas yang akan diverifikasi atau difinalisasi</p>
                </div>
                <span class="px-3 py-1 bg-brand-50 text-brand-700 text-xs font-bold rounded-lg">
                    <?= $totalKelas ?> Kelas
                </span>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" x-model="search" placeholder="Cari nama kelas..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <select x-model="statusFilter" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="all">Semua Status</option>
                    <option value="sekretaris">Menunggu</option>
                    <option value="guru">Diverifikasi</option>
                    <option value="selesai">Final</option>
                </select>
            </div>

            <?php if (empty($kelasList)): ?>
                <div class="p-12 text-center text-gray-400 text-sm">
                    Belum ada kelas yang siap diverifikasi pada tanggal ini.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($kelasList as $kelas): ?>
                        <?php
                        switch ($kelas['status']) {
                            case 'sekretaris':
                                $badgeBg = 'bg-amber-50 text-amber-700';
                                $badgeText = 'Menunggu Verifikasi';
                                $buttonText = 'Verifikasi';
                                $buttonBg = 'bg-amber-500 hover:bg-amber-600 text-white';
                                break;
                            case 'guru':
                                $badgeBg = 'bg-blue-50 text-blue-700';
                                $badgeText = 'Sudah Diverifikasi';
                                $buttonText = 'Buka';
                                $buttonBg = 'bg-brand-600 hover:bg-brand-700 text-white';
                                break;
                            case 'selesai':
                                $badgeBg = 'bg-emerald-50 text-emerald-700';
                                $badgeText = 'Final';
                                $buttonText = 'Lihat';
                                $buttonBg = 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                                break;
                            default:
                                $badgeBg = 'bg-gray-100 text-gray-600';
                                $badgeText = '-';
                                $buttonText = 'Buka';
                                $buttonBg = 'bg-brand-600 hover:bg-brand-700 text-white';
                        }
                        ?>
                        <div class="kelas-card border border-gray-100 rounded-2xl p-5 hover:shadow-md transition-all flex flex-col justify-between gap-4 bg-white"
                            x-show="(statusFilter === 'all' || '<?= $kelas['status'] ?>' === statusFilter) && ('<?= strtolower($kelas['nama_kelas']) ?>'.includes(search.toLowerCase()))">

                            <div>
                                <div class="flex justify-between items-start gap-2 mb-3">
                                    <h3 class="text-base font-bold text-gray-900"><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider <?= $badgeBg ?>">
                                        <?= $badgeText ?>
                                    </span>
                                </div>

                                <div class="space-y-1.5 text-xs text-gray-500 font-medium">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span><?= $kelas['jumlah_siswa'] ?> Siswa</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span><?= DateHelper::tanggal($kelas['tanggal']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <a href="index.php?route=piket/finalisasi_detail&id=<?= $kelas['id'] ?>" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all shadow-sm <?= $buttonBg ?>">
                                    <span><?= $buttonText ?></span>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<script>
    function updateJam() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');
        const jamEl = document.getElementById("jamRealtime");
        if (jamEl) {
            jamEl.innerHTML = hh + ":" + mm + ":" + ss + " WIB";
        }
    }
    updateJam();
    setInterval(updateJam, 1000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>