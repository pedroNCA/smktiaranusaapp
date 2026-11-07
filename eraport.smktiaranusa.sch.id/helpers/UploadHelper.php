<?php
class UploadHelper {
    public static function uploadFile($file, $destinationFolder, $allowedExtensions = ['jpg', 'jpeg', 'png', 'xlsx']) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => false, 'message' => 'Tidak ada file yang diunggah atau terjadi kesalahan sistem.'];
        }

        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validasi Ekstensi
        if (!in_array($fileExt, $allowedExtensions)) {
            return ['status' => false, 'message' => 'Format file tidak diizinkan. Ekstensi valid: ' . implode(', ', $allowedExtensions)];
        }

        // Validasi Ukuran Maksimal (Default 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            return ['status' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5 MB.'];
        }

        // Buat nama unik untuk mencegah duplikasi
        $newFileName = uniqid('file_', true) . '.' . $fileExt;
        
        // Pastikan direktori tujuan ada
        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }

        $destinationPath = $destinationFolder . '/' . $newFileName;

        if (move_uploaded_file($fileTmpName, $destinationPath)) {
            return ['status' => true, 'filename' => $newFileName, 'path' => $destinationPath];
        }

        return ['status' => false, 'message' => 'Gagal memindahkan file yang diunggah ke server.'];
    }
}