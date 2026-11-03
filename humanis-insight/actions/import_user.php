<?php
// actions/import_user.php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($fileTmpPath);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $berhasil = 0;
            $gagal = 0;

            // Ambil daftar valid bidang dari database
            $stmtBidang = $pdo->query("SELECT Bidang FROM tb_bidang");
            $listBidangValid = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);
            $listBidangValid[] = 'Semua';
            $listBidangValid[] = 'Sistem';

            // Role yang diizinkan sistem secara ketat sesuai ENUM database
            $roleValid = ['Kepala Sekolah', 'Wakil Kepala', 'Guru/PIC', 'Operator', 'Yayasan'];

            // Mulai perulangan dari baris 2 (karena baris 1 adalah header tabel)
            for ($i = 2; $i <= count($sheetData); $i++) {
                $row = $sheetData[$i] ?? null;
                if (!$row) continue;

                $nama_lengkap = trim($row['A'] ?? '');
                $username     = trim($row['B'] ?? '');
                $email        = trim($row['C'] ?? '');
                $password     = trim($row['D'] ?? '');
                $role         = trim($row['E'] ?? '');
                $bidang       = trim($row['F'] ?? 'Semua');

                // Lewati baris jika kolom utama kosong atau masih baris header/contoh teks judul
                if (
                    empty($username) || empty($nama_lengkap) || empty($password) ||
                    str_contains(strtolower($username), 'username') ||
                    str_contains(strtolower($nama_lengkap), 'nama lengkap')
                ) {
                    continue;
                }

                // Validasi Role: Jika tidak sesuai pilihan valid, tandai gagal dan lewati baris ini
                if (!in_array($role, $roleValid)) {
                    $gagal++;
                    continue;
                }

                // Validasi Bidang: Jika tidak ada di master, set default ke 'Semua'
                if (!in_array($bidang, $listBidangValid)) {
                    $bidang = 'Semua';
                }

                // Cek duplikasi Username atau Email di database
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_users WHERE username = :username OR email = :email");
                $stmtCheck->execute([
                    ':username' => $username,
                    ':email'    => empty($email) ? '-' : $email
                ]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $gagal++;
                    continue;
                }

                // Enkripsi password menggunakan BCRYPT yang aman
                $password_hashed = password_hash($password, PASSWORD_BCRYPT);

                // Insert data ke database
                $stmt = $pdo->prepare("INSERT INTO tb_users (username, email, password, nama_lengkap, role, bidang) VALUES (:username, :email, :password, :nama, :role, :bidang)");
                $stmt->execute([
                    ':username' => $username,
                    ':email'    => empty($email) ? null : $email,
                    ':password' => $password_hashed,
                    ':nama'     => $nama_lengkap,
                    ':role'     => $role,
                    ':bidang'   => $bidang
                ]);
                $berhasil++;
            }

            header("Location: ../pages/operator/kelola_user.php?status=sukses&berhasil=$berhasil&gagal=$gagal");
            exit;
        } catch (Exception $e) {
            header("Location: ../pages/operator/kelola_user.php?status=error&pesan=" . urlencode($e->getMessage()));
            exit;
        }
    } else {
        header("Location: ../pages/operator/kelola_user.php?status=error&pesan=" . urlencode("File gagal diunggah."));
        exit;
    }
} else {
    header("Location: ../pages/operator/kelola_user.php");
    exit;
}
