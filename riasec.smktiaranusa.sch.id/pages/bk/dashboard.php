<?php
// pages/bk/dashboard.php

require_once __DIR__ . '/../../config/session.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$page_title = 'Ringkasan Dashboard';

// --- Mengambil Data Statistik Otomatis dari Database ---

// 1. Total Siswa dan Status Asesmen (Menggantikan Data Jenis Kelamin)
// Ambil total seluruh siswa dari master_siswa
$stmtTotalSiswa = $pdo->query("SELECT COUNT(*) FROM master_siswa");
$total_siswa = $stmtTotalSiswa->fetchColumn();

// Ambil total siswa yang SUDAH mengisi asesmen (gunakan DISTINCT agar tidak double)
$stmtTotalSudah = $pdo->query("SELECT COUNT(DISTINCT siswa_id) FROM hasil_asesmen");
$total_sudah = $stmtTotalSudah->fetchColumn();

// Hitung yang BELUM asesmen
$total_belum = $total_siswa - $total_sudah;

// 2. Data Kelas untuk Grafik Bar (Persentase Belum Asesmen)
// Mengambil total siswa dan total yang sudah asesmen per kelas
$stmtClass = $pdo->query("
    SELECT 
        ms.kelas, 
        COUNT(DISTINCT ms.id) as total_siswa, 
        COUNT(DISTINCT ha.siswa_id) as sudah_asesmen 
    FROM master_siswa ms 
    LEFT JOIN hasil_asesmen ha ON ms.id = ha.siswa_id 
    GROUP BY ms.kelas 
    ORDER BY ms.kelas ASC
");
$class_data = $stmtClass->fetchAll(PDO::FETCH_ASSOC);

$class_labels = [];
$class_persen_belum = [];
$class_tooltip_data = [];

foreach($class_data as $row) {
    $kelas = $row['kelas'] ?? 'Tanpa Kelas';
    $total = (int)$row['total_siswa'];
    $sudah = (int)$row['sudah_asesmen'];
    $belum = $total - $sudah;
    
    // Hitung persentase yang belum
    $persen = $total > 0 ? round(($belum / $total) * 100, 1) : 0;
    
    $class_labels[] = $kelas;
    $class_persen_belum[] = $persen;
    // Siapkan teks untuk tooltip (Berapa yang belum dari berapa)
    $class_tooltip_data[] = "Belum: $belum dari $total siswa";
}

$class_labels_json = json_encode($class_labels);
$class_persen_json = json_encode($class_persen_belum);
$class_tooltip_json = json_encode($class_tooltip_data);

// 3. 5 Siswa Terakhir yang Mengisi
$stmtRecent = $pdo->query("
    SELECT ha.siswa_id as id, ms.nama, ms.kelas, ha.tanggal_pengisian 
    FROM hasil_asesmen ha 
    JOIN master_siswa ms ON ha.siswa_id = ms.id 
    ORDER BY ha.tanggal_pengisian DESC 
    LIMIT 5
");
$recent_siswa = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

// Panggil Komponen Atas
require_once __DIR__ . '/../../includes/bk_header.php';
?>
<!-- Tambahkan library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
require_once __DIR__ . '/../../includes/bk_sidebar.php';
require_once __DIR__ . '/../../includes/bk_navbar.php';
?>

<div class="space-y-6">

    <!-- Card Statistik Utama -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Siswa -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 border-l-4 border-l-blue-600">
            <div class="p-3 bg-blue-50 rounded-lg text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Siswa</p>
                <p class="text-3xl font-bold text-gray-900"><?= $total_siswa ?></p>
            </div>
        </div>

        <!-- Sudah Asesmen -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 border-l-4 border-l-teal-500">
            <div class="p-3 bg-teal-50 rounded-lg text-teal-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Sudah Asesmen</p>
                <p class="text-3xl font-bold text-gray-900"><?= $total_sudah ?></p>
            </div>
        </div>

        <!-- Belum Asesmen -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 border-l-4 border-l-pink-500">
            <div class="p-3 bg-pink-50 rounded-lg text-pink-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Belum Asesmen</p>
                <p class="text-3xl font-bold text-gray-900"><?= $total_belum ?></p>
            </div>
        </div>
    </div>

    <!-- Area Grafik -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Grafik Bar: Persentase Kelas -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Persentase Belum Asesmen per Kelas</h3>
            <div class="relative h-64">
                <canvas id="classChart"></canvas>
            </div>
        </div>

        <!-- Grafik Donut: Status Asesmen -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Status Pengisian Keseluruhan</h3>
            <div class="relative h-64 flex justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabel 5 Siswa Terakhir -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">5 Responden Terakhir</h3>
            <a href="asesmen.php" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Lihat Semua Data &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($recent_siswa) > 0): ?>
                        <?php foreach ($recent_siswa as $siswa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900"><?= htmlspecialchars($siswa['nama']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($siswa['kelas']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($siswa['tanggal_pengisian'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <a href="detail_siswa.php?id=<?= $siswa['id'] ?>" class="text-blue-600 hover:text-blue-900 font-semibold text-sm">Lihat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada data terbaru.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Script Inisialisasi Chart.js -->
<script>
    // Data Tooltip Custom untuk Chart Kelas
    const classTooltips = <?= $class_tooltip_json ?>;

    // 1. Chart Bar Kelas (% Belum Asesmen)
    const ctxClass = document.getElementById('classChart').getContext('2d');
    new Chart(ctxClass, {
        type: 'bar',
        data: {
            labels: <?= $class_labels_json ?: '[]' ?>,
            datasets: [{
                label: '% Belum Asesmen',
                data: <?= $class_persen_json ?: '[]' ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.8)', // Tailwind red-500
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100, // Karena persentase, maksimal 100
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let index = context.dataIndex;
                            let percentage = context.raw;
                            // Output misal: "35.5% (Belum: 12 dari 32 siswa)"
                            return percentage + '% (' + classTooltips[index] + ')';
                        }
                    }
                }
            }
        }
    });

    // 2. Chart Donut Status Pengisian (Mengganti Jenis Kelamin)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Sudah Asesmen', 'Belum Asesmen'],
            datasets: [{
                data: [<?= $total_sudah ?>, <?= $total_belum ?>],
                backgroundColor: [
                    'rgba(20, 184, 166, 0.8)', // teal-500
                    'rgba(236, 72, 153, 0.8)' // pink-500
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>