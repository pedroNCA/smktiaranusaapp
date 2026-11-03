<?php

/**
 * Halaman Dashboard Executive Kepala Sekolah (Dinamis mencakup seluruh indikator termasuk yang bernilai 0%)
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */

// 1. Ambil daftar semua bidang unik dari tb_indikator
try {
    $stmtBidang = $pdo->query("SELECT DISTINCT bidang FROM tb_indikator WHERE bidang IS NOT NULL AND bidang != '' ORDER BY bidang ASC");
    $listBidang = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $listBidang = [];
}

// 2. Hitung Rata-rata Kinerja per Bidang (TERMASUK Indikator 0% yang belum dilaporkan)
$bidangLabels = [];
$bidangScores = [];

foreach ($listBidang as $namaBidang) {
    $bidangLabels[] = $namaBidang;

    try {
        $stmtAvg = $pdo->prepare("
            SELECT AVG(COALESCE(p.capaian, 0)) as rata 
            FROM tb_indikator i
            LEFT JOIN (
                SELECT id_indikator, capaian 
                FROM tb_pelaporan p1
                WHERE id_pelaporan = (SELECT MAX(id_pelaporan) FROM tb_pelaporan WHERE id_indikator = p1.id_indikator)
            ) p ON i.id_indikator = p.id_indikator
            WHERE i.bidang = :bidang
        ");
        $stmtAvg->execute([':bidang' => $namaBidang]);
        $res = $stmtAvg->fetch(PDO::FETCH_ASSOC);
        $bidangScores[] = $res && $res['rata'] !== null ? round((float)$res['rata'], 2) : 0;
    } catch (Exception $e) {
        $bidangScores[] = 0;
    }
}

// 3. Hitung Statistik Keseluruhan (Indeks Kinerja Sekolah & Proporsi Status Mencakup 0%)
try {
    $stmtGlobal = $pdo->query("
        SELECT AVG(COALESCE(p.capaian, 0)) as global_avg, COUNT(i.id_indikator) as total_indikator
        FROM tb_indikator i
        LEFT JOIN (
            SELECT id_indikator, capaian 
            FROM tb_pelaporan p1
            WHERE id_pelaporan = (SELECT MAX(id_pelaporan) FROM tb_pelaporan WHERE id_indikator = p1.id_indikator)
        ) p ON i.id_indikator = p.id_indikator
    ");
    $statGlobal = $stmtGlobal->fetch(PDO::FETCH_ASSOC);

    $totalIndikator = $statGlobal['total_indikator'] ?? 0;
    $indeksSekolah = round((float)($statGlobal['global_avg'] ?? 0), 2);

    // Proporsi Status (Tinggi: >= 85%, Sedang: 50-84%, Rendah/Belum: < 50%)
    $stmtStatus = $pdo->query("
        SELECT 
            SUM(CASE WHEN COALESCE(p.capaian, 0) >= 85 THEN 1 ELSE 0 END) as hijau,
            SUM(CASE WHEN COALESCE(p.capaian, 0) >= 50 AND COALESCE(p.capaian, 0) < 85 THEN 1 ELSE 0 END) as kuning,
            SUM(CASE WHEN COALESCE(p.capaian, 0) < 50 THEN 1 ELSE 0 END) as merah
        FROM tb_indikator i
        LEFT JOIN (
            SELECT id_indikator, capaian 
            FROM tb_pelaporan p1
            WHERE id_pelaporan = (SELECT MAX(id_pelaporan) FROM tb_pelaporan WHERE id_indikator = p1.id_indikator)
        ) p ON i.id_indikator = p.id_indikator
    ");
    $counts = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    $countHijau = $counts['hijau'] ?? 0;
    $countKuning = $counts['kuning'] ?? 0;
    $countMerah = $counts['merah'] ?? 0;

    if ($totalIndikator > 0) {
        $pctHijau = round(($countHijau / $totalIndikator) * 100);
        $pctKuning = round(($countKuning / $totalIndikator) * 100);
        $pctMerah = round(($countMerah / $totalIndikator) * 100);
    } else {
        $pctHijau = 0;
        $pctKuning = 0;
        $pctMerah = 0;
    }
} catch (Exception $e) {
    $indeksSekolah = 0;
    $totalIndikator = 0;
    $pctHijau = 0;
    $pctKuning = 0;
    $pctMerah = 0;
    $countHijau = 0;
    $countMerah = 0;
}

// 4. Ambil Top Prioritas Perbaikan (Laporan dengan Capaian Rendah atau Ada Kendala)
try {
    $stmtPrioritas = $pdo->prepare("
        SELECT p.*, i.indikator, i.target, i.satuan 
        FROM tb_pelaporan p 
        JOIN tb_indikator i ON p.id_indikator = i.id_indikator 
        WHERE p.capaian < 50 OR (p.refleksi_kendala IS NOT NULL AND p.refleksi_kendala != '') 
        ORDER BY p.capaian ASC, p.id_pelaporan DESC 
        LIMIT 5
    ");
    $stmtPrioritas->execute();
    $listPrioritas = $stmtPrioritas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listPrioritas = [];
}
?>

<!-- Header Konten Halaman -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-display font-bold text-slate-900">Dashboard Executive</h2>
        <p class="text-sm text-slate-500 mt-1">Pemantauan kinerja berkelanjutan SMK Tiara Nusa mencakup seluruh indikator dan data real-time PIC</p>
    </div>

</div>

<!-- 4 Kartu Metrik Utama (Dinamis Makro) -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">

    <!-- Card 1: Indeks Kinerja Sekolah -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Indeks Kinerja Sekolah</p>

        </div>
        <div class="mt-4 flex items-baseline justify-between">
            <div class="text-3xl font-display font-bold tracking-tight text-slate-900"><?= $indeksSekolah ?>%</div>
            <span class="text-xs font-semibold text-emerald-600"><?= $indeksSekolah >= 80 ? 'Sangat Baik' : ($indeksSekolah >= 70 ? 'Baik' : 'Perlu Atensi') ?></span>
        </div>
        <div class="mt-2 text-xs text-slate-400">Total <?= $totalIndikator ?> indikator terpantau</div>
    </div>

    <!-- Card 2: Capaian Keseluruhan -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Capaian Keseluruhan</p>
        </div>
        <div class="mt-4 flex items-baseline justify-between">
            <div class="text-3xl font-display font-bold tracking-tight text-slate-900"><?= $indeksSekolah ?>%</div>
            <span class="text-xs font-semibold text-blue-600">Rata-rata</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">Termasuk indikator bernilai 0%</div>
    </div>

    <!-- Card 3: Capaian Tinggi -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Capaian Tinggi (&ge; 85%)</p>
        </div>
        <div class="mt-4 flex items-baseline justify-between">
            <div class="text-3xl font-display font-bold tracking-tight text-slate-900"><?= $pctHijau ?>%</div>
            <span class="text-xs font-semibold text-slate-600"><?= $countHijau ?> Indikator</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">Tercapai optimal</div>
    </div>

    <!-- Card 4: Capaian Rendah / Belum Dimulai -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Capaian Rendah (&lt; 50%)</p>
        </div>
        <div class="mt-4 flex items-baseline justify-between">
            <div class="text-3xl font-display font-bold tracking-tight text-red-600"><?= $pctMerah ?>%</div>
            <span class="text-xs font-semibold text-red-600"><?= $countMerah ?> Indikator</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">Termasuk belum dikerjakan</div>
    </div>

</div>

<!-- Grid Grafik Utama (Chart.js Terhubung ke Database) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

    <!-- Bar Chart: Nilai Kinerja per Bidang -->
    <div class="bg-white p-6 rounded-2xl shadow-sm ring-1 ring-slate-200 lg:col-span-2 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-lg font-semibold text-slate-900">Rata-rata Capaian per Bidang (Termasuk 0%)</h3>
            <span class="text-xs text-slate-400">Tahun Ajaran Aktif</span>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="barChartBidang"></canvas>
        </div>
    </div>

    <!-- Doughnut Chart: Proporsi Status Capaian -->
    <div class="bg-white p-6 rounded-2xl shadow-sm ring-1 ring-slate-200 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-lg font-semibold text-slate-900">Proporsi Capaian</h3>
            <span class="text-xs text-slate-400">Distribusi</span>
        </div>
        <div class="relative h-64 w-full flex items-center justify-center">
            <canvas id="doughnutCapaian"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs text-slate-600">
            <div class="bg-emerald-50 py-1.5 rounded-lg"><span class="font-bold text-emerald-700"><?= $pctHijau ?>%</span> Tinggi</div>
            <div class="bg-amber-50 py-1.5 rounded-lg"><span class="font-bold text-amber-700"><?= $pctKuning ?>%</span> Sedang</div>
            <div class="bg-red-50 py-1.5 rounded-lg"><span class="font-bold text-red-700"><?= $pctMerah ?>%</span> Rendah</div>
        </div>
    </div>

</div>

<!-- Tabel Top Prioritas Perbaikan -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 mb-8">
    <h3 class="font-display text-lg font-semibold text-slate-900 mb-4">Prioritas Perbaikan (Berdasarkan Refleksi & Kendala PIC)</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-4 py-3">No</th>
                    <th scope="col" class="px-4 py-3">Bidang</th>
                    <th scope="col" class="px-4 py-3">Indikator Kinerja</th>
                    <th scope="col" class="px-4 py-3">PIC / Guru</th>
                    <th scope="col" class="px-4 py-3">Kendala / Refleksi</th>
                    <th scope="col" class="px-4 py-3 text-center">Capaian</th>
                    <th scope="col" class="px-4 py-3 text-right">Rencana Tindak Lanjut (RTL)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (!empty($listPrioritas)): ?>
                    <?php $no = 1;
                    foreach ($listPrioritas as $p): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                            <td class="px-4 py-3 font-semibold text-slate-900"><?= htmlspecialchars($p['bidang']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($p['indikator']) ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($p['nama_pic']) ?></td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-xs truncate"><?= htmlspecialchars($p['refleksi_kendala'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-center font-bold text-red-600"><?= $p['capaian'] ?>%</td>
                            <td class="px-4 py-3 text-right text-xs italic text-slate-500 max-w-xs truncate">
                                <?= htmlspecialchars($p['rencana_tindak_lanjut'] ?? 'Menunggu arahan') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-slate-400">Belum ada data laporan dengan kendala atau capaian rendah yang memerlukan perbaikan khusus.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Skrip Grafik Chart.js -->
<script>
    const bidangLabels = <?= json_encode($bidangLabels) ?>;
    const bidangScores = <?= json_encode($bidangScores) ?>;

    // 1. Bar Chart: Rata-rata Capaian per Bidang
    const ctxBar = document.getElementById('barChartBidang').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: bidangLabels,
            datasets: [{
                label: 'Rata-rata Capaian (%)',
                data: bidangScores,
                backgroundColor: '#1d4ed8',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });

    // 2. Doughnut Chart: Proporsi Status Capaian
    const ctxDoughnut = document.getElementById('doughnutCapaian').getContext('2d');
    new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: ['Tinggi (>= 85%)', 'Sedang (50-84%)', 'Rendah / Belum (< 50%)'],
            datasets: [{
                data: [<?= $pctHijau ?>, <?= $pctKuning ?>, <?= $pctMerah ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
</script>

<?php
require_once '../../includes/kepsek_footer.php';
?>