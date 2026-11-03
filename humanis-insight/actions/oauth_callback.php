<?php
// Tampilkan error jika ada kendala di server lokal
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../config/credentials.json');
$client->addScope(Google_Service_Drive::DRIVE);

// =========================================================================
// DETEKSI OTOMATIS: LOKAL vs ONLINE
// =========================================================================
$host = $_SERVER['HTTP_HOST'] ?? '';

if ($host === 'localhost' || $host === '127.0.0.1') {
    // Jika diakses dari komputer lokal
    $redirect_uri = 'http://localhost/humanis-insight/actions/oauth_callback.php';
    $mode = "Lokal (Development)";
} else {
    // Jika diakses dari hosting / online
    $redirect_uri = 'https://humanis.smktiaranusa.sch.id/actions/oauth_callback.php';
    $mode = "Online (Production)";
}

// Set URL pengalihan sesuai dengan hasil deteksi di atas
$client->setRedirectUri($redirect_uri);
// =========================================================================

$client->setAccessType('offline');
$client->setApprovalPrompt('force');

// Jika belum ada kode autentikasi dari Google, arahkan user untuk login
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
} else {
    // Tukar kode dengan access token
    $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();

    // Simpan token ke file agar bisa digunakan secara otomatis saat upload
    file_put_contents(__DIR__ . '/../config/token.json', json_encode($token));

    // Tampilan sukses
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: green;'>Autentikasi Berhasil!</h2>";
    echo "<p>Token akses Google Drive telah berhasil disimpan ke folder config.</p>";
    echo "<p style='color: #64748b; font-size: 14px;'>Sistem mendeteksi Anda berjalan pada mode: <strong>{$mode}</strong></p>";
    echo "<br><br><a href='../pages/pic/pelaporan.php' style='padding: 10px 20px; background-color: #0f172a; color: white; text-decoration: none; border-radius: 8px;'>Kembali ke Halaman Pelaporan</a>";
    echo "</div>";
    exit;
}
?>