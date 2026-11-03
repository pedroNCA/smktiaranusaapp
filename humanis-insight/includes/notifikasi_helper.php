<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Fungsi untuk mengirim Notifikasi In-App dan Email
 * 
 * @param PDO $pdo Koneksi database
 * @param string $target_role Role target (Kepala Sekolah, Wakil Kepala, Yayasan)
 * @param string|null $target_bidang Bidang target (khusus untuk Wakil Kepala, misal 'Kurikulum'). Isi null jika tidak perlu.
 * @param string $pesan_in_app Teks notifikasi di dalam aplikasi
 * @param string $link_in_app URL tujuan saat notifikasi diklik
 * @param string $subjek_email Judul Email
 * @param string $body_email Isi Email (Bisa format HTML)
 */
function kirimNotifDanEmail($pdo, $target_role, $target_bidang, $pesan_in_app, $link_in_app, $subjek_email, $body_email)
{
    try {
        // 1. Cari User Tujuan
        $sqlCari = "SELECT nama_lengkap, email FROM tb_users WHERE role = :role";
        $paramsCari = [':role' => $target_role];

        if ($target_bidang !== null) {
            $sqlCari .= " AND bidang = :bidang";
            $paramsCari[':bidang'] = $target_bidang;
        }

        $stmtCari = $pdo->prepare($sqlCari);
        $stmtCari->execute($paramsCari);
        $users = $stmtCari->fetchAll(PDO::FETCH_ASSOC);

        if (empty($users)) return false; // Tidak ada user yang cocok

        // 2. Ambil Pengaturan SMTP dari Database
        $stmtSet = $pdo->query("SELECT * FROM tb_pengaturan LIMIT 1");
        $pengaturan = $stmtSet->fetch(PDO::FETCH_ASSOC);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $pengaturan['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $pengaturan['smtp_user'] ?? '';
        $mail->Password   = !empty($pengaturan['smtp_pass']) ? base64_decode($pengaturan['smtp_pass']) : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $pengaturan['smtp_port'] ?? 587;

        $mail->setFrom($pengaturan['smtp_user'] ?? 'no-reply@sekolah.com', $pengaturan['nama_sekolah'] ?? 'Humanis Insight');
        $mail->isHTML(true);
        $mail->Subject = $subjek_email;
        $mail->Body    = $body_email;

        // 3. Looping ke semua user yang ditemukan (Kirim Notif & Email)
        $stmtInsertNotif = $pdo->prepare("INSERT INTO tb_notifikasi (nama_penerima, pesan, link, tanggal, dibaca) VALUES (:nama, :pesan, :link, NOW(), 0)");

        foreach ($users as $user) {
            // Insert In-App Notif
            $stmtInsertNotif->execute([
                ':nama' => $user['nama_lengkap'],
                ':pesan' => $pesan_in_app,
                ':link' => $link_in_app
            ]);

            // Tambahkan penerima Email
            if (!empty($user['email'])) {
                $mail->addAddress($user['email'], $user['nama_lengkap']);
            }
        }

        // 4. Eksekusi Pengiriman Email (Hanya jika ada email tujuan)
        if (count($mail->getAllRecipientAddresses()) > 0) {
            $mail->send();
        }

        return true;
    } catch (Exception $e) {
        // Tangkap error diam-diam agar proses utama tidak terganggu
        error_log("Error Kirim Notif/Email: " . $e->getMessage());
        return false;
    }
}
