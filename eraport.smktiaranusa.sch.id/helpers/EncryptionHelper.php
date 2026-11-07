<?php
class EncryptionHelper {
    private static function getMethod() {
        return 'AES-256-CBC';
    }

    private static function getKey() {
        // Mengambil APP_KEY dari environment atau menggunakan key cadangan
        $key = $_ENV['APP_KEY'] ?? 'R4h4s1aEnkr1ps1SMKTiaraNusa2026_!';
        return hash('sha256', $key);
    }

    public static function encrypt($data) {
        $method = self::getMethod();
        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data) {
        $method = self::getMethod();
        $key = self::getKey();
        $data = base64_decode($data);
        
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
}