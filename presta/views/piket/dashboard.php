<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::piket();

date_default_timezone_set('Asia/Jakarta');
$tanggal = date('Y-m-d');

$pageTitle = 'Dashboard Guru Piket';

/*
|--------------------------------------------------------------------------
| Statistik Hari Ini
|--------------------------------------------------------------------------
*/
$menunggu = 0;
$selesai = 0;
$total = 0;

$stmt = $conn->prepare("
    SELECT status, COUNT(*) total
    FROM verifikasi_kelas
    WHERE tanggal = ?
    GROUP BY status
");

if ($stmt) {
    $stmt->bind_param("s", $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        switch ($row['status']) {
            case 'guru':
                $menunggu = $row['total'];
                break;
            case 'selesai':
                $selesai = $row['total'];
                break;
        }
        $total += $row['total'];
    }
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Aktivitas Terakhir
|--------------------------------------------------------------------------
*/
$aktivitas = [];

$sql = "
    SELECT
        k.nama_kelas,
        vk.status,
        vk.piket_at
    FROM verifikasi_kelas vk
    JOIN kelas k ON k.id = vk.kelas_id
    WHERE vk.piket_at IS NOT NULL
    ORDER BY vk.piket_at DESC
    LIMIT 8
";

$result = $conn->query($sql);

if ($result) {
    $aktivitas = $result->fetch_all(MYSQLI_ASSOC);
}

// --- Memanggil Layout Header Global (Mencakup head.php, sidebar.php, & topbar.php) ---
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-2 text-white">
                    <?= htmlspecialchars(Auth::name() ?? 'Guru Piket') ?>
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
                    <h2 class="text-2xl font-extrabold text-gray-900"><?= $total ?></h2>
                </div>
            </div>

        </section>

        <!-- AKTIVITAS TERAKHIR -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Aktivitas Terakhir</h2>
                    <p class="text-xs text-gray-400">Riwayat finalisasi presensi kelas oleh piket</p>
                </div>
            </div>

            <?php if (empty($aktivitas)): ?>
                <div class="p-12 text-center text-gray-400 text-sm">
                    Belum ada aktivitas hari ini.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50/50">
                                <th class="py-3.5 px-6">Kelas</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm font-medium text-gray-700">
                            <?php foreach ($aktivitas as $row): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-gray-900">
                                        <?= htmlspecialchars($row['nama_kelas']) ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-gray-500 font-mono text-xs">
                                        <?= date('H:i', strtotime($row['piket_at'])) ?> WIB
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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