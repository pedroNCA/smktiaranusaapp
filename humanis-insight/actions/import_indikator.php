<?php
// actions/import_indikator.php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangkap parameter redirect dari form (untuk membedakan Kepsek/Operator)
    // Jika tidak ada, default-nya akan kembali ke halaman operator
    $redirect_url = isset($_POST['redirect']) ? trim($_POST['redirect']) : '../pages/operator/master_data.php';
    
    // Pastikan file terupload dengan benar
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($fileTmpPath);
            // Ambil data dari sheet pertama (Template Indikator)
            $sheet = $spreadsheet->getSheet(0);
            $sheetData = $sheet->toArray(null, true, true, true);

            $berhasil = 0;
            $gagal = 0;

            // Loop mulai dari baris 2 (baris 1 adalah header)
            for ($i = 2; $i <= count($sheetData); $i++) {
                $row = $sheetData[$i] ?? null;

                // Lewati jika baris kosong
                if (!$row || empty($row['A'])) continue;

                $bidang       = trim($row['A'] ?? '');
                $indikator    = trim($row['B'] ?? '');
                $bobot        = floatval($row['C'] ?? 0);
                $target       = floatval($row['D'] ?? 0);
                $jenis_target = trim($row['E'] ?? '=');
                $satuan       = trim($row['F'] ?? '%');
                $prioritas    = trim($row['G'] ?? 'P1');
                $frekuensi    = trim($row['H'] ?? 'Semester');
                $pic          = trim($row['I'] ?? '');

                // Lewati baris jika masih mengandung teks header
                if (str_contains(strtolower($bidang), 'bidang')) {
                    continue;
                }

                // Insert ke database
                $stmt = $pdo->prepare("INSERT INTO tb_indikator 
                    (bidang, indikator, bobot, target, jenis_target, satuan, prioritas, frekuensi, pic) 
                    VALUES (:bidang, :indikator, :bobot, :target, :jenis_target, :satuan, :prioritas, :frekuensi, :pic)");

                $result = $stmt->execute([
                    ':bidang'       => $bidang,
                    ':indikator'    => $indikator,
                    ':bobot'        => $bobot,
                    ':target'       => $target,
                    ':jenis_target' => $jenis_target,
                    ':satuan'       => $satuan,
                    ':prioritas'    => $prioritas,
                    ':frekuensi'    => $frekuensi,
                    ':pic'          => $pic
                ]);

                if ($result) {
                    $berhasil++;
                } else {
                    $gagal++;
                }
            }

            // Redirect dinamis menggunakan variabel $redirect_url
            header("Location: " . $redirect_url . "?status=sukses&berhasil=$berhasil");
            exit;
        } catch (Exception $e) {
            header("Location: " . $redirect_url . "?status=error&pesan=" . urlencode("Error: " . $e->getMessage()));
            exit;
        }
    } else {
        header("Location: " . $redirect_url . "?status=error&pesan=" . urlencode("Gagal mengunggah file."));
        exit;
    }
} else {
    // Jika ada akses langsung tanpa POST, arahkan ke index utama atau default
    header("Location: ../index.php");
    exit;
}