<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Inisialisasi Dotenv untuk membaca file .env (jika belum di-load oleh file yang memanggilnya)
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    try {
        $dotenv->safeLoad();
    } catch (Exception $e) {
        // Abaikan jika error, mungkin env diset langsung dari server (DirectAdmin)
    }
}

function uploadToGoogleDrive($fileTmpPath, $fileName)
{
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/../config/credentials.json');
    $client->addScope(Google_Service_Drive::DRIVE);

    $tokenPath = __DIR__ . '/../config/token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            throw new Exception("Autentikasi Google Drive kedaluwarsa. Silakan jalankan ulang oauth_callback.php.");
        }
    }

    $service = new Google_Service_Drive($client);

    // Ambil Folder ID dari .env
    $folderId = $_ENV['GDRIVE_FOLDER_ID'] ?? getenv('GDRIVE_FOLDER_ID');
    
    // Validasi jika Folder ID kosong
    if (empty($folderId)) {
        throw new Exception("GDRIVE_FOLDER_ID belum dikonfigurasi di file .env!");
    }

    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $fileName,
        'parents' => [$folderId]
    ]);

    $content = file_get_contents($fileTmpPath);
    $mimeType = mime_content_type($fileTmpPath);

    $file = $service->files->create($fileMetadata, [
        'data' => $content,
        'mimeType' => $mimeType,
        'uploadType' => 'multipart',
        'fields' => 'id, webViewLink'
    ]);

    $fileId = $file->id;

    // --- AGAR FILE OTOMATIS BISA DIBACA PUBLIK ---
    $permission = new Google_Service_Drive_Permission([
        'type' => 'anyone',
        'role' => 'reader'
    ]);
    $service->permissions->create($fileId, $permission);
    // ---------------------------------------------

    return $file->webViewLink;
}

function hapusFileGoogleDrive($fileId)
{
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/../config/credentials.json');
    $client->addScope(Google_Service_Drive::DRIVE);

    $tokenPath = __DIR__ . '/../config/token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            return false;
        }
    }

    $service = new Google_Service_Drive($client);
    try {
        $service->files->delete($fileId);
        return true;
    } catch (Exception $e) {
        return false;
    }
}