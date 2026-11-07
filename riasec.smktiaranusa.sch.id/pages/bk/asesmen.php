<?php
// pages/bk/asesmen.php

require_once __DIR__ . '/../../config/session.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$page_title = 'Data Hasil Asesmen';

// --- LOGIKA FILTER, SEARCH, & PAGINATION ---
$search = sanitizeInput($_GET['search'] ?? '');
$filter_kelas = sanitizeInput($_GET['kelas'] ?? '');
$tgl_awal = sanitizeInput($_GET['tgl_awal'] ?? '');
$tgl_akhir = sanitizeInput($_GET['tgl_akhir'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Susun Query secara dinamis
$whereClause = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClause[] = "ms.nama LIKE ?";
    $params[] = "%$search%";
}
if (!empty($filter_kelas)) {
    $whereClause[] = "ms.kelas = ?";
    $params[] = $filter_kelas;
}
// Tambahan Logika Filter Tanggal
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $whereClause[] = "ha.tanggal_pengisian BETWEEN ? AND ?";
    // Tambahkan jam agar mencakup layar 1 hari penuh
    $params[] = $tgl_awal . " 00:00:00";
    $params[] = $tgl_akhir . " 23:59:59";
}

$whereSQL = implode(" AND ", $whereClause);

try {
    // 1. Ambil daftar kelas unik dari siswa yang sudah mengerjakan asesmen untuk Dropdown Filter
    $stmtKelas = $pdo->query("SELECT DISTINCT ms.kelas FROM hasil_asesmen ha JOIN master_siswa ms ON ha.siswa_id = ms.id ORDER BY ms.kelas ASC");
    $list_kelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);

    // 2. Hitung Total Data (untuk pagination)
    $stmtCount = $pdo->prepare("SELECT COUNT(ha.id) FROM hasil_asesmen ha JOIN master_siswa ms ON ha.siswa_id = ms.id WHERE $whereSQL");
    $stmtCount->execute($params);
    $total_data = $stmtCount->fetchColumn();
    $total_pages = ceil($total_data / $limit);

    // 3. Ambil Data Siswa (JOIN hasil_asesmen dengan master_siswa) sesuai halaman
    $stmtData = $pdo->prepare("
        SELECT 
            ha.id as hasil_id, 
            ms.id as siswa_id, 
            ms.nama, 
            ms.nis, 
            ms.kelas, 
            ms.jenis_kelamin, 
            ha.tanggal_pengisian 
        FROM hasil_asesmen ha 
        JOIN master_siswa ms ON ha.siswa_id = ms.id 
        WHERE $whereSQL 
        ORDER BY ha.tanggal_pengisian DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmtData->execute($params);
    $siswa_list = $stmtData->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Terjadi kesalahan pada database: " . $e->getMessage());
}

require_once __DIR__ . '/../../includes/bk_header.php';
require_once __DIR__ . '/../../includes/bk_sidebar.php';
require_once __DIR__ . '/../../includes/bk_navbar.php';
?>

<!-- Notifikasi Hapus -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
        <strong class="font-bold">Berhasil!</strong>
        <span class="block sm:inline">Data siswa beserta hasil asesmennya telah dihapus permanen.</span>
    </div>
<?php endif; ?>

<!-- Baris Kendali: Search, Filter, Limit -->
<div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
    <form method="GET" action="asesmen.php" class="flex flex-col md:flex-row gap-4 justify-between items-end md:items-center">

        <div class="flex flex-wrap gap-4 w-full md:w-auto">
            <!-- Tampilkan -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tampilkan</label>
                <select name="limit" onchange="this.form.submit()" class="border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2">
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 baris</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 baris</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 baris</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 baris</option>
                </select>
            </div>

            <!-- Filter Kelas -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter Kelas</label>
                <select name="kelas" onchange="this.form.submit()" class="border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2 min-w-[150px]">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($list_kelas as $kls): ?>
                        <option value="<?= htmlspecialchars($kls) ?>" <?= $filter_kelas === $kls ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kls) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Rentang Tanggal -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Mulai Tanggal</label>
                <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>" class="border border-gray-300 text-gray-900 text-sm rounded-lg p-2 block w-full focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>" class="border border-gray-300 text-gray-900 text-sm rounded-lg p-2 block w-full focus:ring-primary focus:border-primary">
            </div>
        </div>

        <!-- Pencarian -->
        <div class="w-full md:w-72">
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cari Nama Siswa</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                    </svg>
                </div>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Ketik nama...">
                <!-- Tombol Submit Tersembunyi (terpicu saat enter) -->
                <button type="submit" class="hidden"></button>
            </div>
        </div>

    </form>
</div>

<!-- Tabel Data -->
<div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">NIS</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kelas</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($total_data > 0): ?>
                    <?php $no = $offset + 1;
                    foreach ($siswa_list as $siswa): ?>
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium"><?= $no++ ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($siswa['nama']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono"><?= htmlspecialchars(decryptData($siswa['nis'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-semibold"><?= htmlspecialchars($siswa['kelas']) ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y', strtotime($siswa['tanggal_pengisian'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="detail_siswa.php?id=<?= $siswa['siswa_id'] ?>" class="text-xs font-semibold text-primary hover:text-white bg-blue-50 hover:bg-primary px-3 py-1.5 rounded transition-all border border-blue-100">Detail</a>
                                    <!-- TOMBOL HAPUS -->
                                    <a href="../../proses/hapus_siswa.php?id=<?= $siswa['siswa_id'] ?>" onclick="return confirm('Yakin ingin menghapus data <?= htmlspecialchars($siswa['nama']) ?>? Data tidak dapat dipulihkan.');" class="text-xs font-semibold text-red-600 hover:text-white bg-red-50 hover:bg-red-600 px-3 py-1.5 rounded transition-all border border-red-100">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <p class="text-base font-semibold">Data tidak ditemukan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total: <?= $total_data ?> data)</span>
        <div class="flex gap-1">
            <?php
            // Bawa parameter GET saat ini ke link pagination
            $queryParams = $_GET;

            // Tombol Prev
            if ($page > 1):
                $queryParams['page'] = $page - 1;
                $prevUrl = '?' . http_build_query($queryParams);
            ?>
                <a href="<?= $prevUrl ?>" class="px-3 py-1.5 border border-gray-300 rounded text-sm font-medium hover:bg-gray-50">&laquo; Prev</a>
            <?php endif; ?>

            <?php
            // Tombol Next
            if ($page < $total_pages):
                $queryParams['page'] = $page + 1;
                $nextUrl = '?' . http_build_query($queryParams);
            ?>
                <a href="<?= $nextUrl ?>" class="px-3 py-1.5 border border-gray-300 rounded text-sm font-medium hover:bg-gray-50">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>