<?php
// Pastikan koneksi database sudah ada
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM tb_pengaturan LIMIT 1");
    $app_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika data kosong di database, beri nilai default agar sistem tidak error
    if (!$app_data) {
        $app_data = [
            'nama_sekolah' => 'Humanis Insight',
            'tahun_ajaran' => '-',
            'kepala_sekolah' => '-',
            'nrks' => '-',
            'alamat' => '-',
            'ketua_yayasan' => '-',
            'kompetensi_keahlian_1' => '-',
            'kompetensi_keahlian_2' => '-',
            'logo' => 'default_logo.png',
            'favicon' => 'default_favicon.png',
            'tipe_sekolah' => '-',
            'no_telepon' => '-',
            'email_sekolah' => '-',
            'website' => '-',
            'slogan' => '-',
            'npsn' => '-',
            'target_mutu_global' => 85,
            'semester_aktif' => '-',
            'smtp_host' => '-',
            'smtp_user' => '-',
            'smtp_pass' => '-',
            'smtp_port' => 587
        ];
    }
} catch (Exception $e) {
    // Jika terjadi error koneksi, beri nilai default
    $app_data = [];
}
