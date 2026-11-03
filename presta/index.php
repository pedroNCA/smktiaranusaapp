<?php
// ==========================================
// FRONT CONTROLLER (Router Utama PRESTA)
// ==========================================
session_start();

// 1. Panggil koneksi database (Gunakan __DIR__ agar akurat)
require_once __DIR__ . '/config/database.php';

// 2. Ambil parameter route dari URL
$route = $_GET['route'] ?? 'landing';

// 3. Sanitasi KETAT route untuk mencegah Path Traversal / LFI
// Izinkan huruf, angka, garis bawah, strip, dan garis miring (untuk folder bersarang seperti admin/export/excel)
$route = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $route);
$route = trim($route, '/'); // Hapus kelebihan slash di awal/akhir

if (empty($route)) {
    $route = 'landing';
}

// 4. Tangani route khusus API atau Controller (bypass folder views)
switch ($route) {
    case 'api/laporan':
        require_once __DIR__ . '/controllers/LaporanController.php';
        exit;

    case 'guru/ajax_laporan':
        require_once __DIR__ . '/views/guru/ajax/ajax_laporan.php';
        exit;

    case 'admin/ajax_laporan':
        require_once __DIR__ . '/views/admin/ajax/ajax_laporan.php';
        exit;

    case 'api/piket/simpan_finalisasi':
        require_once __DIR__ . '/views/api/piket/simpan_finalisasi.php';
        exit;

    case 'api/piket/finalisasi':
        require_once __DIR__ . '/views/api/piket/finalisasi.php';
        exit;

    case 'piket/presensi':
        require_once __DIR__ . '/views/piket/presensi.php';
        exit;

    case 'piket/presensi_detail':
        require_once __DIR__ . '/views/piket/presensi_detail.php';
        exit;

    case 'api/piket/simpan_dan_verifikasi':
        require_once __DIR__ . '/views/api/piket/simpan_dan_verifikasi.php';
        exit;

    case 'admin/export_laporan_hari_ini':
        require_once __DIR__ . '/views/admin/includes/export_laporan_hari_ini.php';
        exit;

    case 'admin/export_laporan':
        require_once __DIR__ . '/views/admin/includes/export_laporan.php';
        exit;
}

// 5. Validasi Path Fisik Absolut (Keamanan Ekstra)
$views_dir = realpath(__DIR__ . '/views');
$target_file = __DIR__ . '/views/' . $route . '.php';

// Fungsi realpath() akan me-return false jika file tidak ada di server
$real_path = realpath($target_file);

// 6. Cek: File ada AND Path file tersebut benar-benar di dalam folder views/
if ($real_path && str_starts_with($real_path, $views_dir) && file_exists($real_path)) {
    require_once $real_path;
} else {
    // Jika file tidak ditemukan atau mencoba akses di luar folder views, tampilkan 404
    http_response_code(404);
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Halaman Tidak Ditemukan</title>
        <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    </head>

    <body class="bg-gray-50 text-gray-700 min-h-screen flex flex-col items-center justify-center p-4">
        <div class="text-center">
            <h1 class="text-6xl font-extrabold text-blue-600 mb-4">404</h1>
            <p class="text-xl font-bold text-gray-900 mb-2">Halaman tidak ditemukan.</p>
            <p class="text-sm text-gray-500 mb-6">File untuk route <code class="bg-gray-200 px-2 py-1 rounded text-red-500"><?= htmlspecialchars($route) ?></code> tidak valid atau tidak ada.</p>
            <a href="index.php" class="inline-block bg-blue-600 text-white font-semibold px-6 py-2.5 rounded-xl shadow-lg hover:bg-blue-700 transition-colors">
                Kembali ke Beranda
            </a>
        </div>
    </body>

    </html>
<?php
}
?>