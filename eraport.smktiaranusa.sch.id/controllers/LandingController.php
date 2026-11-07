<?php
/**
 * LandingController
 * Menangani logika tampilan halaman depan (landing page) publik e-Raport SMK Tiara Nusa.
 */

class LandingController {
    
    public function index() {
        // Logika tambahan jika diperlukan sebelum menampilkan landing page,
        // misalnya mengambil informasi umum sekolah dari database (opsional).
        
        $title = "e-Raport - SMK Tiara Nusa";
        
        // Panggil file view landing page
        $viewPath = __DIR__ . '/../views/landing/index.php';
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            http_response_code(404);
            echo "Halaman Landing tidak ditemukan.";
        }
    }
}