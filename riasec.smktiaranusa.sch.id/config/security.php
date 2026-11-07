<?php
// config/security.php

// Pastikan env_parser sudah dipanggil. Jika belum, panggil di sini.
if (!isset($_ENV['SECRET_KEY'])) {
    require_once __DIR__ . '/env_parser.php';
}

// Algoritma enkripsi yang digunakan
define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Fungsi untuk mengenkripsi data sensitif (misal: NIS)
 * 
 * @param string $data Data asli yang akan dienkripsi
 * @return string Data terenkripsi dalam format base64
 */
function encryptData($data) {
    if (empty($data)) return $data;
    
    // Hash SECRET_KEY agar selalu memiliki panjang 32 karakter (kebutuhan AES-256)
    $key = hash('sha256', $_ENV['SECRET_KEY']);
    
    // Buat IV (Initialization Vector) acak untuk lapisan keamanan tambahan
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    // Proses enkripsi
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, $key, 0, $iv);
    
    // Gabungkan IV dan data terenkripsi, lalu encode dengan base64 agar aman disimpan di database
    return base64_encode($iv . $encrypted);
}

/**
 * Fungsi untuk mendekripsi data sensitif dari database
 * 
 * @param string $encryptedData Data terenkripsi dari database
 * @return string Data asli
 */
function decryptData($encryptedData) {
    if (empty($encryptedData)) return $encryptedData;
    
    $key = hash('sha256', $_ENV['SECRET_KEY']);
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    
    // Decode base64
    $decoded = base64_decode($encryptedData);
    
    // Pisahkan IV (di awal) dan teks terenkripsi (sisanya)
    $iv = substr($decoded, 0, $ivLength);
    $encrypted = substr($decoded, $ivLength);
    
    // Proses dekripsi
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, $key, 0, $iv);
}

/**
 * Fungsi untuk membersihkan inputan form dari user (Mencegah XSS)
 * 
 * @param string|array $data Input dari $_POST atau $_GET
 * @return string|array Data yang sudah dibersihkan
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value); // Bersihkan array secara rekursif
        }
        return $data;
    }
    // Hapus spasi berlebih, hilangkan tag HTML/PHP, dan ubah karakter khusus menjadi entitas HTML
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>