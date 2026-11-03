<?php
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::guru();

date_default_timezone_set('Asia/Jakarta');

$tanggal = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| Cek apakah guru merupakan wali kelas
|--------------------------------------------------------------------------
*/

$isWali = false;

$stmt = $conn->prepare("
SELECT id
FROM kelas
WHERE wali_kelas_id=?
LIMIT 1
");

if ($stmt) {
    $id = Auth::id();
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $isWali = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Statistik Hari Ini
|--------------------------------------------------------------------------
*/

$menunggu = 0;
$diverifikasi = 0;
$selesai = 0;
$total = 0;

$stmt = $conn->prepare("
SELECT status,COUNT(*) total
FROM verifikasi_kelas
WHERE tanggal=?
GROUP BY status
");

if ($stmt) {
    $stmt->bind_param("s", $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        switch ($row['status']) {
            case 'sekretaris':
                $menunggu = $row['total'];
                break;
            case 'guru':
                $diverifikasi = $row['total'];
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
vk.guru_at
FROM verifikasi_kelas vk
JOIN kelas k
ON k.id=vk.kelas_id
WHERE vk.guru_at IS NOT NULL
ORDER BY vk.guru_at DESC
LIMIT 8
";

$result = $conn->query($sql);

if ($result) {
    $aktivitas = $result->fetch_all(MYSQLI_ASSOC);
}

// --- Memanggil Layout Global ---
$pageTitle = "Dashboard Guru - PRESTA";
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
                    <?= htmlspecialchars(Auth::name()) ?>
                </h1>

                <div class="flex flex-wrap items-center gap-3 text-brand-100 text-sm md:text-base font-medium mb-4">
                    <span class="px-3 py-1 bg-white/10 rounded-lg text-xs font-semibold uppercase tracking-wider text-white">
                        <?= $isWali ? 'Guru & Wali Kelas' : 'Guru Mapel' ?>
                    </span>
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
                <div class="w-10 h-10 shrink-0 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Diverifikasi</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $diverifikasi ?></h2>
            </div>

            <!-- Selesai -->
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 shrink-0 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Selesai Finalisasi</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $selesai ?></h2>
            </div>

            <!-- Total -->
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 shrink-0 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Total</p>
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $total ?></h2>
            </div>

        </section>

        <!-- AKTIVITAS TERAKHIR TABLE CARD -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Aktivitas Terakhir</h2>
                <p class="text-sm text-gray-500">Riwayat verifikasi kelas terbaru hari ini.</p>
            </div>

            <div class="overflow-x-auto p-6 pt-2">
                <?php if (empty($aktivitas)): ?>
                    <div class="py-12 text-center text-gray-400 text-sm font-medium">
                        Belum ada aktivitas hari ini.
                    </div>
                <?php else: ?>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Kelas</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php foreach ($aktivitas as $row): ?>
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-4 px-4 font-bold text-gray-900">
                                        <?= htmlspecialchars($row['nama_kelas']) ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php
                                        $statusLabel = ucfirst($row['status']);
                                        $badgeStyle = 'bg-gray-100 text-gray-700';
                                        if ($row['status'] == 'selesai') $badgeStyle = 'bg-emerald-50 text-emerald-700';
                                        else if ($row['status'] == 'guru') $badgeStyle = 'bg-blue-50 text-blue-700';
                                        else if ($row['status'] == 'sekretaris') $badgeStyle = 'bg-amber-50 text-amber-700';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold <?= $badgeStyle ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 font-semibold text-gray-600">
                                        <?= date('H:i', strtotime($row['guru_at'])) ?> WIB
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>