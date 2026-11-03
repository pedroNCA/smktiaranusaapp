<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::piket();

date_default_timezone_set('Asia/Jakarta');
$tanggal = date('Y-m-d');
$pageTitle = 'Monitoring Presensi Hari Ini';

// ===============================
// AMBIL DATA MONITORING KELAS
// ===============================
$sql = "
SELECT 
    k.id AS kelas_id,
    k.nama_kelas,
    vk.status,
    vk.guru_at,
    vk.piket_at,
    COUNT(s.id) AS total_siswa,
    SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
    SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS izin,
    SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
    SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) AS alfa,
    SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat
FROM kelas k
LEFT JOIN siswa s ON s.kelas_id = k.id
LEFT JOIN verifikasi_kelas vk ON vk.kelas_id = k.id AND vk.tanggal = ?
LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
GROUP BY k.id, k.nama_kelas, vk.status, vk.guru_at, vk.piket_at
ORDER BY k.nama_kelas ASC
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('ss', $tanggal, $tanggal);
    $stmt->execute();
    $rekapKelas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rekapKelas = [];
}

$totalKelas = count($rekapKelas);
$selesai = 0;
$menungguFinalisasi = 0;
$menungguVerifikasi = 0;
$belum = 0;

foreach ($rekapKelas as $rk) {
    if ($rk['status'] == 'selesai') {
        $selesai++;
    } elseif ($rk['status'] == 'guru') {
        $menungguFinalisasi++;
    } elseif ($rk['status'] == 'sekretaris') {
        $menungguVerifikasi++;
    } else {
        $belum++;
    }
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
                    Monitoring Presensi
                </h1>
                <div class="flex items-center gap-2 text-brand-100 text-sm">
                    
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="jamRealtime">--:--:-- WIB</span>
                </div>
            </div>
        </section>

        <!-- STATISTIK / SUMMARY GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 transform transition-all duration-700 delay-100 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- Belum Absen -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Belum Absen</p>
                    <h2 class="text-xl font-extrabold text-gray-900"><?= $belum ?> Kelas</h2>
                </div>
            </div>

            <!-- Menunggu Verifikasi -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Menunggu Verifikasi</p>
                    <h2 class="text-xl font-extrabold text-gray-900"><?= $menungguVerifikasi ?> Kelas</h2>
                </div>
            </div>

            <!-- Menunggu Finalisasi -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Menunggu Finalisasi</p>
                    <h2 class="text-xl font-extrabold text-gray-900"><?= $menungguFinalisasi ?> Kelas</h2>
                </div>
            </div>

            <!-- Selesai (Final) -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Selesai</p>
                    <h2 class="text-xl font-extrabold text-gray-900"><?= $selesai ?> Kelas</h2>
                </div>
            </div>

        </section>

        <!-- DAFTAR MONITORING SECTION -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6 transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-gray-100 mb-6">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Status Absensi Seluruh Kelas</h2>
                    <p class="text-xs text-gray-400">Pantau progres pengisian kehadiran siswa hari ini.</p>
                </div>
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
                    <option value="belum">Belum Absen</option>
                    <option value="verifikasi">Menunggu Verifikasi</option>
                    <option value="finalisasi">Menunggu Finalisasi</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>

            <?php if (empty($rekapKelas)): ?>
                <div class="p-12 text-center text-gray-400 text-sm">
                    Belum ada data kelas yang dapat ditampilkan.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <?php foreach ($rekapKelas as $kelas): ?>
                        <?php
                        if (empty($kelas['status'])) {
                            $statusKode = 'belum';
                        } elseif ($kelas['status'] === 'sekretaris') {
                            $statusKode = 'verifikasi';
                        } elseif ($kelas['status'] === 'guru') {
                            $statusKode = 'finalisasi';
                        } else {
                            $statusKode = 'selesai';
                        }

                        switch ($statusKode) {
                            case 'belum':
                                $badgeBg = 'bg-red-50 text-red-700 border border-red-200';
                                $badgeText = 'Belum Absen';
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
                                break;
                            case 'verifikasi':
                                $badgeBg = 'bg-sky-50 text-sky-700 border border-sky-200';
                                $badgeText = 'Menunggu Verifikasi';
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />';
                                break;
                            case 'finalisasi':
                                $badgeBg = 'bg-amber-50 text-amber-700 border border-amber-200';
                                $badgeText = 'Menunggu Finalisasi';
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />';
                                break;
                            case 'selesai':
                                $badgeBg = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                $badgeText = 'Selesai';
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />';
                                break;
                        }
                        ?>
                        <div class="border border-gray-100 rounded-2xl p-5 hover:shadow-lg hover:border-brand-100 transition-all flex flex-col justify-between bg-white group"
                            x-show="(statusFilter === 'all' || '<?= $statusKode ?>' === statusFilter) && ('<?= strtolower($kelas['nama_kelas']) ?>'.includes(search.toLowerCase()))">

                            <div>
                                <div class="flex justify-between items-start gap-2 mb-4">
                                    <h3 class="text-base font-bold text-gray-900 group-hover:text-brand-600 transition-colors">
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </h3>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider <?= $badgeBg ?>">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <?= $icon ?>
                                        </svg>
                                        <?= $badgeText ?>
                                    </span>
                                </div>

                                <!-- Statistik Absen -->
                                <div class="grid grid-cols-4 gap-2 mb-4">
                                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase">H</span>
                                        <span class="block text-sm font-extrabold text-emerald-600"><?= $kelas['hadir'] ?? 0 ?></span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase">I</span>
                                        <span class="block text-sm font-extrabold text-blue-600"><?= $kelas['izin'] ?? 0 ?></span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase">S</span>
                                        <span class="block text-sm font-extrabold text-purple-600"><?= $kelas['sakit'] ?? 0 ?></span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase">A</span>
                                        <span class="block text-sm font-extrabold text-red-600"><?= $kelas['alfa'] ?? 0 ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                                <span>Total Siswa: <b><?= $kelas['total_siswa'] ?></b></span>
                                <?php if ($statusKode === 'selesai' && !empty($kelas['piket_at'])): ?>
                                    <span class="text-[10px] bg-gray-100 px-2 py-1 rounded">Final: <?= date('H:i', strtotime($kelas['piket_at'])) ?></span>
                                <?php elseif ($statusKode === 'finalisasi' && !empty($kelas['guru_at'])): ?>
                                    <span class="text-[10px] bg-gray-100 px-2 py-1 rounded">Guru: <?= date('H:i', strtotime($kelas['guru_at'])) ?></span>
                                <?php else: ?>
                                    <span class="text-[10px] text-gray-300">Belum ada aktivitas</span>
                                <?php endif; ?>
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