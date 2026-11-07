<?php
/**
 * KONFIGURASI APLIKASI E-POINT
 * Sesuaikan kredensial database di bawah ini dengan server Anda.
 */
// Polyfill untuk kompatibilitas versi PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp((string)$haystack, (string)$needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return (string)$needle === '' || substr((string)$haystack, -strlen((string)$needle)) === (string)$needle;
    }
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.cookie_samesite', 'Lax');
    @session_start();
}

// Cek ketersediaan ekstensi database mysqli
if (!extension_loaded('mysqli')) {
    die('<div style="font-family:\'Segoe UI\',Tahoma,sans-serif;max-width:560px;margin:60px auto;padding:24px;border-radius:12px;background:#ffffff;border:1px solid #fecaca;box-shadow:0 10px 25px rgba(0,0,0,0.06);">' .
        '<div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' .
        '<span style="background:#fee2e2;color:#dc2626;padding:10px;border-radius:50%;font-size:1.4rem;display:inline-flex;">⚠️</span>' .
        '<h4 style="margin:0;font-weight:700;color:#991b1b;">Ekstensi PHP MySQLi Diperlukan</h4>' .
        '</div>' .
        '<p style="font-size:0.92rem;color:#475569;margin-bottom:14px;line-height:1.5;">Ekstensi PHP <code>mysqli</code> belum aktif di server hosting / komputer Anda.</p>' .
        '<div style="background:#f8fafc;padding:14px;border-radius:8px;font-size:0.85rem;color:#334155;border:1px solid #e2e8f0;line-height:1.6;">' .
        '<strong>Cara Mengaktifkan:</strong>' .
        '<ul style="margin:6px 0 0 18px;padding:0;">' .
        '<li>Di Laragon: Klik kanan icon tray &rarr; <strong>PHP &rarr; Extensions</strong>, centang <code>mysqli</code> dan <code>zip</code>.</li>' .
        '<li>Di DirectAdmin / cPanel: Masuk ke <strong>Select PHP Version &rarr; Extensions</strong>, centang <code>mysqli</code> dan <code>zip</code>.</li>' .
        '<li>Di XAMPP: Buka <code>php.ini</code>, pastikan baris <code>extension=mysqli</code> tidak diawali tanda titik koma (;).</li>' .
        '</ul></div></div>');
}

@mysqli_report(MYSQLI_REPORT_OFF);

$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = in_array($client_ip, ['127.0.0.1', '::1'])
            || in_array($server_name, ['localhost', '127.0.0.1'])
            || str_ends_with($server_name, '.test')
            || str_starts_with($http_host, 'localhost')
            || str_starts_with($http_host, '127.0.0.1')
            || str_ends_with(explode(':', $http_host)[0], '.test')
            || filter_var($client_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

if ($is_local) {
    error_reporting(E_ALL & ~E_DEPRECATED);
    @ini_set('display_errors', 1);
} else {
    // Di server hosting produksi: sembunyikan pesan error teknis dari publik demi keamanan
    error_reporting(E_ALL & ~E_DEPRECATED);
    @ini_set('display_errors', 0);
    @ini_set('log_errors', 1);
}

defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_USER') || define('DB_USER', 'irotzkba_epoint');
defined('DB_PASS') || define('DB_PASS', 'Ma6FDJr6Lnk6CTy5XyVN');
defined('DB_NAME') || define('DB_NAME', 'irotzkba_epoint');

// Skor awal setiap siswa sebelum dikurangi/ditambah poin
defined('SKOR_AWAL') || define('SKOR_AWAL', 100);

// Ambang batas predikat berdasarkan skor akhir
defined('BATAS_SANGAT_BAIK') || define('BATAS_SANGAT_BAIK', 90);
defined('BATAS_BAIK') || define('BATAS_BAIK', 75);
defined('BATAS_CUKUP') || define('BATAS_CUKUP', 50);
// di bawah BATAS_CUKUP => "Perlu Pembinaan"

// Durasi Inactivity Timeout Sesi (dalam detik, default 15 menit = 900 detik. Ubah misal ke 1800 untuk 30 menit)
defined('SESSION_TIMEOUT_SECONDS') || define('SESSION_TIMEOUT_SECONDS', 900);

$koneksi = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    $err_detail = $is_local ? ('<div style="margin-top:10px;font-size:0.8rem;color:#b91c1c;"><strong>Detail Teknis:</strong> ' . htmlspecialchars(mysqli_connect_error()) . '</div>') : '';
    die('<div style="font-family:\'Segoe UI\',Tahoma,sans-serif;max-width:560px;margin:60px auto;padding:24px;border-radius:12px;background:#ffffff;border:1px solid #fed7aa;box-shadow:0 10px 25px rgba(0,0,0,0.06);">' .
        '<div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' .
        '<span style="background:#ffedd5;color:#ea580c;padding:10px;border-radius:50%;font-size:1.4rem;display:inline-flex;">⚠️</span>' .
        '<h4 style="margin:0;font-weight:700;color:#9a3412;">Konfigurasi Database Diperlukan</h4>' .
        '</div>' .
        '<p style="font-size:0.92rem;color:#475569;margin-bottom:14px;line-height:1.5;">Sistem E-Point belum dapat terhubung ke basis data MySQL server Anda.</p>' .
        '<div style="background:#f8fafc;padding:14px;border-radius:8px;font-size:0.85rem;color:#334155;border:1px solid #e2e8f0;line-height:1.6;">' .
        '<strong>Panduan untuk Pengelola Server / Hosting:</strong>' .
        '<ol style="margin:6px 0 0 18px;padding:0;">' .
        '<li>Buka berkas <code>config.php</code> di File Manager (DirectAdmin / cPanel).</li>' .
        '<li>Sesuaikan <code>DB_USER</code>, <code>DB_PASS</code>, dan <code>DB_NAME</code> dengan database hosting Anda.</li>' .
        '<li>Pastikan struktur tabel dari <code>database.sql</code> sudah diimpor via phpMyAdmin.</li>' .
        '</ol></div>' .
        $err_detail .
        '</div>');
}
mysqli_set_charset($koneksi, 'utf8mb4');

// Self-healing migration: Pastikan kolom password_plain selalu tersedia di tabel users
try {
    $check_users_tbl = @mysqli_query($koneksi, "SHOW TABLES LIKE 'users'");
    if ($check_users_tbl && mysqli_num_rows($check_users_tbl) > 0) {
        $check_col = @mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
        if ($check_col && mysqli_num_rows($check_col) === 0) {
            @mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) DEFAULT NULL AFTER password");
        }
        // Pastikan akun pengguna selalu memiliki nilai password_plain jika masih kosong
        @mysqli_query($koneksi, "UPDATE users SET password_plain = CONCAT(username, '123') WHERE password_plain IS NULL OR password_plain = ''");
        @mysqli_query($koneksi, "UPDATE users SET password_plain = 'admin123' WHERE username = 'admin' AND (password_plain IS NULL OR password_plain = '')");
        @mysqli_query($koneksi, "UPDATE users SET password_plain = 'kepsek123' WHERE username = 'kepsek' AND (password_plain IS NULL OR password_plain = '')");
        @mysqli_query($koneksi, "UPDATE users SET password_plain = 'gurubk123' WHERE username = 'gurubk' AND (password_plain IS NULL OR password_plain = '')");
        @mysqli_query($koneksi, "UPDATE users SET password_plain = 'walikelas123' WHERE username = 'walikelas' AND (password_plain IS NULL OR password_plain = '')");
    }
} catch (Throwable $e) {
    // Lewati secara aman jika tabel belum tersedia
}

defined('BASE_URL') || define('BASE_URL', '');

/**
 * Helper: bersihkan input
 */
if (!function_exists('clean')) {
    function clean($koneksi, $data) {
        return mysqli_real_escape_string($koneksi, trim($data ?? ''));
    }
}

/**
 * Helper: hitung predikat berdasarkan skor akhir
 */
if (!function_exists('hitungPredikat')) {
    function hitungPredikat($skor) {
        if ($skor >= BATAS_SANGAT_BAIK) return ['Sangat Baik', 'success'];
        if ($skor >= BATAS_BAIK) return ['Baik', 'primary'];
        if ($skor >= BATAS_CUKUP) return ['Cukup', 'warning'];
        return ['Perlu Pembinaan', 'danger'];
    }
}

/**
 * Helper: redirect dengan pesan flash
 */
if (!function_exists('redirect')) {
    function redirect($url, $pesan = null, $tipe = 'success') {
        if ($pesan) {
            $_SESSION['flash_pesan'] = $pesan;
            $_SESSION['flash_tipe'] = $tipe;
        }
        if (!headers_sent()) {
            header("Location: $url");
            exit;
        } else {
            echo "<script>window.location.href=" . json_encode($url) . ";</script>";
            exit;
        }
    }
}

/**
 * Helper: render lencana jurusan yang elegan dan rapi
 */
if (!function_exists('badgeJurusan')) {
    function badgeJurusan($jurusan) {
        if ($jurusan === 'MPLB') {
            return '<span class="badge rounded-pill fw-semibold shadow-xs" style="background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; font-size:0.75rem; padding:0.35rem 0.65rem; display:inline-flex; align-items:center; gap:4px;"><i class="bi bi-briefcase-fill" style="font-size:0.7rem;"></i>MPLB</span>';
        } elseif ($jurusan === 'TJKT') {
            return '<span class="badge rounded-pill fw-semibold shadow-xs" style="background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; font-size:0.75rem; padding:0.35rem 0.65rem; display:inline-flex; align-items:center; gap:4px;"><i class="bi bi-hdd-network-fill" style="font-size:0.7rem;"></i>TJKT</span>';
        }
        return '<span class="badge bg-secondary">' . htmlspecialchars($jurusan ?? '-') . '</span>';
    }
}

/**
 * Helper: render daftar pilihan siswa dengan pengelompokan kelas yang cerdas
 * - Jika Wali Kelas: Kelas binaan berada di bagian paling atas
 * - Jika Admin/Guru BK: Dikelompokkan per kelas
 */
if (!function_exists('renderSelectSiswaOptions')) {
    function renderSelectSiswaOptions($koneksi, $selected_id = 0) {
        $user_role = $_SESSION['role'] ?? '';
        $user_kelas_id = (int)($_SESSION['kelas_id'] ?? 0);
        $user_nama_kelas = $_SESSION['nama_kelas'] ?? '';

        $query = mysqli_query($koneksi, "
            SELECT s.id, s.nama, s.nis, s.nisn, s.kelas_id, k.nama_kelas, k.jurusan 
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.status = 'aktif' 
            ORDER BY k.jurusan, k.nama_kelas, s.nama
        ");

        $siswa_saya = [];
        $siswa_lain = [];
        $siswa_per_kelas = [];

        while ($s = mysqli_fetch_assoc($query)) {
            if ($user_kelas_id > 0) {
                if ((int)$s['kelas_id'] === $user_kelas_id) {
                    $siswa_saya[] = $s;
                } else {
                    $siswa_lain[] = $s;
                }
            } else {
                $siswa_per_kelas[$s['nama_kelas']][] = $s;
            }
        }

        $html = '';
        if ($user_kelas_id > 0) {
            if (!empty($siswa_saya)) {
                $html .= '<optgroup label="⭐ Siswa Kelas Saya (' . htmlspecialchars($user_nama_kelas) . ')">';
                foreach ($siswa_saya as $s) {
                    $sel = ((int)$s['id'] === (int)$selected_id) ? 'selected' : '';
                    $identitas = $s['nisn'] ?: $s['nis'];
                    $html .= '<option value="' . $s['id'] . '" ' . $sel . '>' . htmlspecialchars("{$s['nama']} ({$identitas}) - {$s['nama_kelas']}") . '</option>';
                }
                $html .= '</optgroup>';
            }
            if (!empty($siswa_lain)) {
                $html .= '<optgroup label="Siswa Seluruh Kelas Lainnya">';
                foreach ($siswa_lain as $s) {
                    $sel = ((int)$s['id'] === (int)$selected_id) ? 'selected' : '';
                    $identitas = $s['nisn'] ?: $s['nis'];
                    $html .= '<option value="' . $s['id'] . '" ' . $sel . '>' . htmlspecialchars("{$s['nama']} ({$identitas}) - {$s['nama_kelas']} [{$s['jurusan']}]") . '</option>';
                }
                $html .= '</optgroup>';
            }
        } else {
            foreach ($siswa_per_kelas as $kelas_nama => $items) {
                $html .= '<optgroup label="Kelas ' . htmlspecialchars($kelas_nama) . '">';
                foreach ($items as $s) {
                    $sel = ((int)$s['id'] === (int)$selected_id) ? 'selected' : '';
                    $identitas = $s['nisn'] ?: $s['nis'];
                    $html .= '<option value="' . $s['id'] . '" ' . $sel . '>' . htmlspecialchars("{$s['nama']} ({$identitas}) - {$kelas_nama}") . '</option>';
                }
                $html .= '</optgroup>';
            }
        }

        return $html;
    }
}

