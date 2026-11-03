<?php
// Penyesuaian path: Karena file ini sekarang berada di folder /controllers/ (1 level di bawah root),
// pemanggilan folder config dan app cukup menggunakan ../
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Auth.php';
require_once __DIR__ . '/../app/Services/AttendanceService.php';

header('Content-Type: application/json');

// Tolak request selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak valid'
    ]);
    exit;
}

// Tangkap penanda dari frontend (htmx)
// Nilainya bisa: 'guru', 'sekretaris', atau 'piket'
$sumber_modul = $_POST['sumber_modul'] ?? '';

switch ($sumber_modul) {

    // ==========================================
    // LOGIKA UNTUK GURU & PIKET
    // ==========================================
    case 'guru':
    case 'piket':
        // Lakukan validasi otorisasi sesuai modul
        if ($sumber_modul === 'guru') {
            Auth::guru();
        } else {
            // Asumsi ada fungsi Auth::piket() jika sumber_modul = piket
            // Auth::piket(); 
        }

        $absensiId = isset($_POST['absensi_id']) ? (int)$_POST['absensi_id'] : 0;
        $status = trim($_POST['status'] ?? '');
        $jam = $_POST['jam'] ?? null;

        $statusValid = [
            'Hadir',
            'Sakit',
            'Izin',
            'Alfa',
            'Terlambat',
            'Belum ada keterangan'
        ];

        if ($absensiId <= 0 || !in_array($status, $statusValid)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        if ($jam === '') {
            $jam = null;
        }

        $sql = "UPDATE absensi SET status=?, jam_datang=? WHERE id=?";
        $stmt = $conn->prepare($sql);

        // PERBAIKAN BUG: Menghapus bind_param("s", $status) yang ganda/redundant sebelumnya.
        // Langsung bind ketiga parameter sekaligus.
        $stmt->bind_param("ssi", $status, $jam, $absensiId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan']);
        }
        $stmt->close();
        break;

    // ==========================================
    // LOGIKA UNTUK SEKRETARIS
    // ==========================================
    case 'sekretaris':
        Auth::sekretaris();

        try {
            AttendanceService::autoSave(
                $conn,
                (int)$_POST['siswa_id'],
                date('Y-m-d'),
                $_POST['status'],
                $_POST['jam_datang'] ?: null
            );

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    // ==========================================
    // JIKA MODUL TIDAK DIKENALI
    // ==========================================
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Sumber modul tidak dikenali.'
        ]);
        break;
}
