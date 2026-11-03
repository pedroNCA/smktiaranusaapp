<?php

/**
 * Fungsi utama untuk menyimpan notifikasi in-app
 * (Fitur kirim email telah dinonaktifkan)
 */
function kirimNotifikasi($pdo, $nama_penerima, $email_penerima, $pesan, $link = '#')
{
    // SIMPAN KE DATABASE (Notifikasi In-App)
    try {
        $stmt = $pdo->prepare("INSERT INTO tb_notifikasi (nama_penerima, pesan, link, dibaca, tanggal) 
                               VALUES (:nama, :pesan, :link, 0, NOW())");
        $stmt->execute([
            ':nama'  => $nama_penerima,
            ':pesan' => $pesan,
            ':link'  => $link
        ]);
    } catch (Exception $e) {
        // Catat error di log server jika gagal insert ke database
        error_log("Gagal menyimpan notifikasi in-app: " . $e->getMessage());
    }
}