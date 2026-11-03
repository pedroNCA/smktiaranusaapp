<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::guru();

date_default_timezone_set('Asia/Jakarta');

$pageTitle = "Verifikasi Presensi";
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// ===============================
// DAFTAR KELAS SIAP DIVERIFIKASI
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
        AND s.status = 'Aktif'
    WHERE vk.tanggal = ?
      AND vk.status IN ('sekretaris', 'guru', 'selesai')
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

// Hitung statistik ringkasan kelas
$totalKelas = count($kelasList);
$menunggu = 0;
$diverifikasi = 0;
$selesai = 0;

foreach ($kelasList as $k) {
    switch ($k['status']) {
        case 'sekretaris':
            $menunggu++;
            break;
        case 'guru':
            $diverifikasi++;
            break;
        case 'selesai':
            $selesai++;
            break;
    }
}

// --- Memanggil Layout Header (Sudah mencakup head.php & sidebar.php) ---
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">

            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-3 text-white">
                    Verifikasi Presensi Seluruh Kelas
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-brand-100 text-sm md:text-base font-medium">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white"><?= $totalKelas ?> Kelas</span>
                        <span class="text-brand-300">Siap Diverifikasi</span>
                    </div>
                    <span class="text-brand-300">•</span>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="jamRealtime"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATISTIK GRID -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4 transform transition-all duration-700 delay-100 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- Total Kelas -->
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 shrink-0 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Total Kelas</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $totalKelas ?></h2>
            </div>

            <!-- Menunggu -->
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 shrink-0 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Menunggu Verifikasi</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $menunggu ?></h2>
            </div>

            <!-- Diverifikasi -->
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 shrink-0 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Diverifikasi</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $diverifikasi ?></h2>
            </div>

            <!-- Selesai -->
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 shrink-0 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Selesai Finalisasi</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $selesai ?></h2>
            </div>

        </section>

        <!-- DAFTAR KELAS CARD SECTION -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Daftar Kelas</h2>
                    <p class="text-sm text-gray-500">Pilih kelas yang akan diverifikasi.</p>
                </div>
                <span class="px-3 py-1 bg-brand-50 text-brand-600 text-xs font-bold rounded-lg border border-brand-100">
                    <?= $totalKelas ?> Kelas
                </span>
            </div>

            <!-- Toolbar Modern -->
            <div class="p-6 pb-2">
                <div class="toolbar-modern flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div class="search-box relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                            id="searchClass"
                            class="search-input w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all"
                            placeholder="Cari nama kelas...">
                    </div>

                    <select id="statusFilter"
                        class="status-filter px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all cursor-pointer">
                        <option value="all">Semua Status</option>
                        <option value="sekretaris">Menunggu</option>
                        <option value="guru">Diverifikasi</option>
                        <option value="selesai">Final</option>
                    </select>
                </div>
            </div>

            <!-- Grid Kelas -->
            <div class="p-6">
                <div class="kelas-grid modern-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (empty($kelasList)): ?>
                        <div class="col-span-full py-16 text-center text-gray-400 text-sm font-medium">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-50 flex items-center justify-center text-gray-300">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            Belum ada kelas yang siap diverifikasi pada tanggal ini.
                        </div>
                    <?php else: ?>
                        <?php foreach ($kelasList as $kelasItem): ?>
                            <?php
                            switch ($kelasItem['status']) {
                                case 'sekretaris':
                                    $badgeClass = 'bg-amber-50 text-amber-700 border border-amber-200';
                                    $badgeText = 'Menunggu Verifikasi';
                                    $buttonText = 'Verifikasi';
                                    $buttonClass = 'bg-amber-500 hover:bg-amber-600 text-white';
                                    break;
                                case 'guru':
                                    $badgeClass = 'bg-blue-50 text-blue-700 border border-blue-200';
                                    $badgeText = 'Sudah Diverifikasi';
                                    $buttonText = 'Buka';
                                    $buttonClass = 'bg-brand-600 hover:bg-brand-700 text-white';
                                    break;
                                case 'selesai':
                                    $badgeClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                    $badgeText = 'Final';
                                    $buttonText = 'Lihat';
                                    $buttonClass = 'bg-gray-600 hover:bg-gray-700 text-white';
                                    break;
                                default:
                                    $badgeClass = 'bg-gray-100 text-gray-700 border border-gray-200';
                                    $badgeText = '-';
                                    $buttonText = 'Buka';
                                    $buttonClass = 'bg-brand-600 hover:bg-brand-700 text-white';
                            }
                            ?>

                            <div class="kelas-card modern-card bg-white rounded-xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between"
                                data-status="<?= $kelasItem['status'] ?>"
                                data-kelas="<?= strtolower($kelasItem['nama_kelas']) ?>">

                                <div>
                                    <div class="kelas-header flex items-center gap-3.5 mb-4">
                                        <div class="kelas-icon w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <div class="kelas-title-group flex-1 min-w-0">
                                            <h3 class="text-base font-bold text-gray-900 truncate mb-0.5"><?= htmlspecialchars($kelasItem['nama_kelas']) ?></h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold <?= $badgeClass ?>">
                                                <?= $badgeText ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="kelas-body space-y-2 mb-5 text-xs font-semibold text-gray-500">
                                        <div class="info-row flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span><?= $kelasItem['jumlah_siswa'] ?> Siswa Aktif</span>
                                        </div>
                                        <div class="info-row flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span><?= DateHelper::tanggal($kelasItem['tanggal']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="kelas-footer pt-3 border-t border-gray-50">
                                    <a href="index.php?route=guru/verifikasi_detail&id=<?= $kelasItem['id'] ?>"
                                        class="btn <?= $buttonClass ?> btn-full w-full py-2.5 px-4 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-2 shadow-sm transition-all cursor-pointer">
                                        <span><?= $buttonText ?></span>
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </section>

    </div>
</div>

<script>
    function updateJam() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');
        const el = document.getElementById('jamRealtime');
        if (el) {
            el.innerHTML = hh + ":" + mm + ":" + ss + " WIB";
        }
    }

    updateJam();
    setInterval(updateJam, 1000);

    // Live Search & Filter Kelas
    function filterKelas() {
        const keyword = document.getElementById('searchClass').value.toLowerCase();
        const statusVal = document.getElementById('statusFilter').value;
        const cards = document.querySelectorAll('.kelas-card');

        cards.forEach(card => {
            const namaKelas = card.getAttribute('data-kelas') || '';
            const statusKelas = card.getAttribute('data-status') || '';

            const matchKeyword = namaKelas.includes(keyword);
            const matchStatus = (statusVal === 'all' || statusKelas === statusVal);

            if (matchKeyword && matchStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    const searchClassInput = document.getElementById('searchClass');
    if (searchClassInput) {
        searchClassInput.addEventListener('keyup', filterKelas);
    }

    const statusFilterSelect = document.getElementById('statusFilter');
    if (statusFilterSelect) {
        statusFilterSelect.addEventListener('change', filterKelas);
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>