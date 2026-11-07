<?php
// Tampilkan error saat tahap pengembangan (local)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Config & Helpers
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/SessionHelper.php';

// Load Controllers
require_once __DIR__ . '/controllers/LandingController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/GuruController.php';
require_once __DIR__ . '/controllers/WaliKelasController.php';
require_once __DIR__ . '/controllers/AuthController.php';

SessionHelper::init();

$route = $_GET['route'] ?? '';
$route = rtrim($route, '/');

// Mapping Rute Aplikasi
switch ($route) {

    // ==========================================
    // 1. PUBLIC & AUTH ROUTES
    // ==========================================
    case '':
    case 'landing':
        $landingController = new LandingController();
        $landingController->index();
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $database = new Database();
            $db = $database->connect();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!empty($username) && !empty($password)) {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['role'] === 'guru') {
                        $stmtCheckGuru = $db->prepare("SELECT id FROM guru WHERE username = :username OR id = :guru_id LIMIT 1");
                        $stmtCheckGuru->execute([
                            'username' => $username,
                            'guru_id' => $user['guru_id'] ?? 0
                        ]);

                        if (!$stmtCheckGuru->fetch()) {
                            $error_message = "Akun tidak valid atau belum terdaftar dalam Data Guru!";
                            require_once __DIR__ . '/views/auth/login.php';
                            exit();
                        }
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['is_walikelas'] = $user['is_walikelas'];
                    $_SESSION['last_activity'] = time();

                    header("Location: /dashboard");
                    exit();
                } else {
                    $error_message = "Username atau password salah!";
                }
            } else {
                $error_message = "Mohon isi semua kolom.";
            }
        }
        require_once __DIR__ . '/views/auth/login.php';
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header("Location: /login");
        exit();

    case 'auth/update-password':
        // Pastikan Anda memanggil Controller yang menangani autentikasi/user
        $authController = new AuthController(); // Sesuaikan dengan controller auth Anda
        $authController->updatePassword();
        break;

    // ==========================================
    // 2. DASHBOARD UTAMA
    // ==========================================
    case 'dashboard':
        SessionHelper::checkLogin();

        if ($_SESSION['role'] === 'admin') {
            $adminController = new AdminController();
            $adminController->dashboard();
        } elseif ($_SESSION['role'] === 'guru') {
            $database = new Database();
            $db = $database->connect();

            // Ambil guru_id
            $stmtUser = $db->prepare("SELECT guru_id FROM users WHERE id = :user_id");
            $stmtUser->execute(['user_id' => $_SESSION['user_id']]);
            $guruId = $stmtUser->fetchColumn();

            // Cek apakah wali kelas
            $stmtCekWali = $db->prepare("SELECT kelas FROM wali_kelas WHERE guru_id = :guru_id LIMIT 1");
            $stmtCekWali->execute(['guru_id' => $guruId]);
            $dataWali = $stmtCekWali->fetch(PDO::FETCH_ASSOC);

            if ($dataWali) {
                $_SESSION['is_walikelas'] = true;
                require_once __DIR__ . '/controllers/WaliKelasController.php';
                $waliController = new WaliKelasController();
                $waliController->dashboard();
                exit();
            } else {
                $_SESSION['is_walikelas'] = false;
                $guruController = new GuruController();
                $guruController->dashboard();
                exit();
            }
        }


        // ==========================================
        // 3. ADMINISTRATOR ROUTES
        // ==========================================
    case 'admin/guru':
        $adminController = new AdminController();
        $adminController->dataGuru();
        break;

    case 'admin/guru/template':
        $adminController = new AdminController();
        $adminController->templateGuru();
        break;

    case 'admin/guru/store':
        $adminController = new AdminController();
        $adminController->storeGuru();
        break;

    case 'admin/guru/update':
        $adminController = new AdminController();
        $adminController->updateGuru();
        break;

    case 'admin/guru/delete':
        $adminController = new AdminController();
        $adminController->deleteGuru();
        break;

    case 'admin/guru/import':
        $adminController = new AdminController();
        $adminController->importGuru();
        break;

    case 'admin/siswa':
        $adminController = new AdminController();
        $adminController->siswaIndex();
        break;

    case 'admin/siswa/store':
        $adminController = new AdminController();
        $adminController->storeSiswa();
        break;

    case 'admin/siswa/update':
        $adminController = new AdminController();
        $adminController->updateSiswa();
        break;

    case 'admin/siswa/delete':
        $adminController = new AdminController();
        $adminController->deleteSiswa();
        break;

    case 'admin/siswa/delete-selected':
        $adminController = new AdminController();
        $adminController->deleteSelectedSiswa();
        break;

    case 'admin/siswa/template':
        $adminController = new AdminController();
        $adminController->templateSiswa();
        break;

    case 'admin/siswa/import':
        $adminController = new AdminController();
        $adminController->importSiswa();
        break;

    case 'admin/walikelas':
        $adminController = new AdminController();
        $adminController->waliKelasIndex();
        break;

    case 'admin/walikelas/store':
        $adminController = new AdminController();
        $adminController->storeWaliKelas();
        break;

    case 'admin/walikelas/update':
        $adminController = new AdminController();
        $adminController->updateWaliKelas();
        break;

    case 'admin/walikelas/delete':
        $adminController = new AdminController();
        $adminController->deleteWaliKelas();
        break;

    case 'admin/walikelas/delete-selected':
        $adminController = new AdminController();
        $adminController->deleteSelectedWaliKelas();
        break;

    case 'admin/walikelas/template':
        $adminController = new AdminController();
        $adminController->templateWaliKelas();
        break;

    case 'admin/walikelas/import':
        $adminController = new AdminController();
        $adminController->importWaliKelas();
        break;

    case 'admin/mapel':
        $adminController = new AdminController();
        $adminController->mapelIndex();
        break;

    case 'admin/mapel/store':
        $adminController = new AdminController();
        $adminController->storeMapel();
        break;

    case 'admin/mapel/update':
        $adminController = new AdminController();
        $adminController->updateMapel();
        break;

    case 'admin/mapel/delete':
        $adminController = new AdminController();
        $adminController->deleteMapel();
        break;

    case 'admin/mapel/delete-selected':
        $adminController = new AdminController();
        $adminController->deleteSelectedMapel();
        break;

    case 'admin/mapel/template':
        $adminController = new AdminController();
        $adminController->templateMapel();
        break;

    case 'admin/mapel/import':
        $adminController = new AdminController();
        $adminController->importMapel();
        break;

    case 'admin/sekolah':
        $adminController = new AdminController();
        $adminController->pengaturanSekolah();
        break;

    case 'admin/sekolah/simpan':
        $adminController = new AdminController();
        $adminController->storeSekolah();
        break;

    case 'admin/rekap/download-semua':
        $adminController = new AdminController();
        $adminController->downloadRekapSemuaKelas();
        break;

    // ==========================================
    // 4. WALI KELAS ROUTES
    // ==========================================
    case 'walikelas/nilai':
        $waliController = new WaliKelasController();
        $waliController->inputNilaiKelas();
        break;

    case 'walikelas/absensi':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->absensiIndex();
        break;

    case 'walikelas/absensi/store':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->absensiStore();
        break;

    case 'walikelas/absensi/template':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->downloadTemplateAbsensi();
        break;

    case 'walikelas/absensi/import':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->importAbsensi();
        break;

    case 'walikelas/raport':
        $waliController = new WaliKelasController();
        $waliController->cetakRaport();
        break;

    case 'walikelas/raport':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->raportIndex();
        break;

    case 'walikelas/raport/cetak-pdf':
    case 'walikelas/raport/cetak-fisik':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->cetakPdfRaport();
        break;

    case 'walikelas/raport/cetak-semua':
        require_once __DIR__ . '/controllers/WaliKelasController.php';
        $controller = new WaliKelasController();
        $controller->cetakSemuaRaport();
        break;

    case 'walikelas/ttd':
        $waliController = new WaliKelasController();
        $waliController->uploadTtd();
        break;

    case 'walikelas/ttd/store':
        $waliController = new WaliKelasController();
        $waliController->storeTtd();
        break;

    case 'walikelas/raport/pratinjau':
        $waliController = new WaliKelasController();
        $waliController->pratinjauRaport();
        break;

    case 'walikelas/nilai/edit':
        $waliController = new WaliKelasController();
        $waliController->editNilaiSiswa();
        break;

    case 'walikelas/nilai/update':
        $waliController = new WaliKelasController();
        $waliController->updateNilaiSiswa();
        break;

    case 'walikelas/nilai/download-excel':
        $waliKelasController = new WaliKelasController();
        $waliKelasController->downloadRekapExcel();
        break;

    // ==========================================
    // 5. GURU MAPEL ROUTES
    // ==========================================
    case 'guru/nilai':
        $guruController = new GuruController();
        $guruController->nilaiIndex();
        break;

    case 'guru/nilai/store':
        $guruController = new GuruController();
        $guruController->storeNilai();
        break;

    case 'guru/nilai/template':
        $guruController = new GuruController();
        $guruController->templateNilai();
        break;

    case 'guru/nilai/import':
        $guruController = new GuruController();
        $guruController->importNilai();
        break;

    case 'guru/dashboard':
    case 'guru':
        $guruController = new GuruController();
        $guruController->dashboard();
        break;


    // ==========================================
    // DEFAULT: 404 NOT FOUND
    // ==========================================
    default:
        http_response_code(404);
        echo "<div style='text-align:center; margin-top:100px; font-family:sans-serif;'>
                <h2 style='font-size: 2rem; color: #374151;'>404 - Halaman Tidak Ditemukan</h2>
                <p style='color: #6B7280; margin-bottom: 20px;'>Maaf, URL rute yang Anda tuju tidak tersedia di sistem ini.</p>
                <a href='/' style='padding: 10px 20px; background-color: #059669; color: white; text-decoration: none; border-radius: 8px;'>Kembali ke Beranda</a>
              </div>";
        break;
}
