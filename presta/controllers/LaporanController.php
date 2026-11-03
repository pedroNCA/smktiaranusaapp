<?php
// Penyesuaian path agar selaras dengan posisi file di folder /controllers/
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Auth.php';

// Panggil file helper dengan path penelusuran folder yang akurat
if (file_exists(__DIR__ . '/../app/Helpers/laporan_helper.php')) {
    require_once __DIR__ . '/../app/Helpers/laporan_helper.php';
} elseif (file_exists(__DIR__ . '/../includes/laporan_helper.php')) {
    require_once __DIR__ . '/../includes/laporan_helper.php';
}

header('Content-Type: application/json; charset=utf-8');

// Tolak request selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak valid'
    ]);
    exit;
}

// Tangkap penanda modul dari frontend (htmx atau fetch API)
// Nilainya bisa: 'laporan_admin', 'laporan_guru', atau 'rekap_harian_guru'
$sumber_modul = $_POST['sumber_modul'] ?? '';

switch ($sumber_modul) {

    // ==========================================
    // 1. LAPORAN UNTUK ADMIN
    // ==========================================
    case 'laporan_admin':
        Auth::admin(); // Pastikan admin login (Sesuaikan dengan helper Auth Anda)

        $tahun   = (int)($_POST['tahun'] ?? 0);
        $kelas   = (int)($_POST['kelas'] ?? 0);
        $jenis   = $_POST['jenis'] ?? 'bulanan';
        $periode = $_POST['periode'] ?? date('n');

        if ($kelas == 0) {
            if ($tahun == 0) {
                echo json_encode(['success' => false, 'message' => 'Tahun ajaran belum dipilih.']);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'Kelas belum dipilih.']);
            exit;
        }

        $info = getInfoLaporan($conn, $tahun, $kelas);
        if (empty($info)) {
            echo json_encode(['success' => false, 'message' => 'Data kelas atau tahun ajaran tidak ditemukan.']);
            exit;
        }

        $summary = getSummaryLaporan($conn, $tahun, $kelas, $jenis, $periode);
        $rows    = getPreviewLaporan($conn, $tahun, $kelas, $jenis, $periode);
        $preview = buildPreviewTable($rows);

        echo json_encode([
            "success"      => true,
            "hadir"        => $summary['hadir'],
            "izin"         => $summary['izin'],
            "sakit"        => $summary['sakit'],
            "alfa"         => $summary['alfa'],
            "terlambat"    => $summary['terlambat'],
            "hari"         => $summary['hari'],
            "persen"       => $summary['persen'],
            "preview"      => $preview,
            "info_kelas"   => $info['nama_kelas'] ?? '-',
            "info_tahun"   => $info['nama_tahun'] ?? '-',
            "info_periode" => getNamaPeriode($jenis, $periode),
            "jumlah_siswa" => count($rows),
        ]);
        break;


    // ==========================================
    // 2. LAPORAN UNTUK GURU / WALI KELAS
    // ==========================================
    case 'laporan_guru':
        Auth::guru();

        $userId  = $_SESSION['user_id'];
        $tahun   = (int)($_POST['tahun'] ?? 0);
        $kelas   = (int)($_POST['kelas'] ?? 0);
        $jenis   = $_POST['jenis'] ?? 'bulanan';
        $periode = $_POST['periode'] ?? date('n');

        if (!$tahun || !$kelas) {
            echo json_encode(['success' => false, 'message' => 'Data belum lengkap.']);
            exit;
        }

        // Validasi akses kelas wali
        $stmt = $conn->prepare("SELECT id FROM kelas WHERE id = ? AND wali_kelas_id = ? LIMIT 1");
        $stmt->bind_param("ii", $kelas, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses pada kelas tersebut.']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Ambil Data
        $info    = getInfoLaporan($conn, $tahun, $kelas);
        $summary = getSummaryLaporan($conn, $tahun, $kelas, $jenis, $periode);
        $rows    = getPreviewLaporan($conn, $tahun, $kelas, $jenis, $periode);

        // Buat HTML Preview
        $preview = '
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th><th>NIS</th><th>Nama Siswa</th>
                    <th>H</th><th>I</th><th>S</th><th>A</th><th>T</th><th>%</th>
                </tr>
            </thead>
            <tbody>' . buildPreviewTable($rows) . '</tbody>
        </table>';

        // Hitung total siswa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ?");
        $stmt->bind_param("i", $kelas);
        $stmt->execute();
        $totalSiswa = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();

        // Buat Summary HTML
        $summaryHtml = '
            <div class="report-item"><span>Periode</span><strong>' . getNamaPeriode($jenis, $periode) . '</strong></div>
            <div class="report-item"><span>Kelas</span><strong>' . htmlspecialchars($info['nama_kelas']) . '</strong></div>
            <div class="report-item"><span>Tahun Ajaran</span><strong>' . htmlspecialchars($info['nama_tahun']) . '</strong></div>
            <div class="report-item"><span>Total Siswa</span><strong>' . $totalSiswa . '</strong></div>
            <div class="report-item"><span>Status</span><span class="status-badge status-success">Siap Export</span></div>
        ';

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_siswa'  => $totalSiswa,
                'hadir'        => $summary['hadir'],
                'izin'         => $summary['izin'],
                'sakit'        => $summary['sakit'],
                'alfa'         => $summary['alfa'],
                'terlambat'    => $summary['terlambat'],
                'persentase'   => $summary['persen'],
                'hari_efektif' => $summary['hari']
            ],
            'chart' => [
                'hadir'     => $summary['hadir'],
                'izin'      => $summary['izin'],
                'sakit'     => $summary['sakit'],
                'alfa'      => $summary['alfa'],
                'terlambat' => $summary['terlambat']
            ],
            'summary' => $summaryHtml,
            'preview' => $preview
        ]);
        break;


    // ==========================================
    // 3. REKAP HARIAN GURU (UI / PREVIEW WALI)
    // ==========================================
    case 'rekap_harian_guru':
        try {
            Auth::guru();
            $userId = $_SESSION['user_id'];
            $tanggalHariIni = date('Y-m-d');

            // Ambil data kelas wali
            $stmt = $conn->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
            if (!$stmt) throw new Exception("Query error: " . $conn->error);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $kelasData = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$kelasData) {
                throw new Exception('Anda tidak terdaftar sebagai wali kelas.');
            }
            $kelasId = $kelasData['id'];

            // Query data siswa & absensi harian
            $query = $conn->prepare("
                SELECT 
                    s.nis, s.nama_siswa as nama_lengkap,
                    COALESCE(a.status, 'Belum ada keterangan') as status,
                    a.jam_datang as waktu_absen, a.keterangan
                FROM siswa s
                LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal = ?
                WHERE s.kelas_id = ?
                ORDER BY s.nama_siswa ASC
            ");
            if (!$query) throw new Exception("Query absensi error: " . $conn->error);

            $query->bind_param("si", $tanggalHariIni, $kelasId);
            $query->execute();
            $listSiswa = $query->get_result()->fetch_all(MYSQLI_ASSOC);
            $query->close();

            $totalSiswa = count($listSiswa);
            $hadir = $izin = $sakit = $alfa = $terlambat = 0;
            $tidakMasukList = [];

            foreach ($listSiswa as $s) {
                $st = trim($s['status']);
                $stLower = strtolower($st);

                if (str_contains($stLower, 'hadir')) {
                    $hadir++;
                } else {
                    if (str_contains($stLower, 'izin')) $izin++;
                    elseif (str_contains($stLower, 'sakit')) $sakit++;
                    elseif (str_contains($stLower, 'alfa')) $alfa++;
                    elseif (str_contains($stLower, 'terlambat')) $terlambat++;
                    else $st = 'Belum Diisi';

                    $tidakMasukList[] = [
                        'nama' => $s['nama_lengkap'],
                        'nis' => $s['nis'],
                        'status' => $st,
                        'keterangan' => $s['keterangan']
                    ];
                }
            }

            // Render HTML Preview Tabel Utama
            ob_start();
?>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">NIS</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Jam Datang</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm font-medium text-gray-700">
                    <?php if ($totalSiswa === 0): ?>
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-400">Tidak ada data siswa di kelas ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($listSiswa as $s):
                            $st = $s['status'];
                            $stLower = strtolower($st);
                            $badgeColor = "bg-gray-100 text-gray-700";

                            if (str_contains($stLower, 'hadir')) $badgeColor = "bg-emerald-50 text-emerald-700";
                            elseif (str_contains($stLower, 'izin')) $badgeColor = "bg-amber-50 text-amber-700";
                            elseif (str_contains($stLower, 'sakit')) $badgeColor = "bg-blue-50 text-blue-700";
                            elseif (str_contains($stLower, 'alfa')) $badgeColor = "bg-red-50 text-red-700";
                            elseif (str_contains($stLower, 'terlambat')) $badgeColor = "bg-orange-50 text-orange-700";
                            else $badgeColor = "bg-slate-100 text-slate-500";
                        ?>
                            <tr>
                                <td class="py-3.5 px-4"><?= $no++ ?></td>
                                <td class="py-3.5 px-4 font-mono text-xs"><?= htmlspecialchars($s['nis']) ?></td>
                                <td class="py-3.5 px-4 font-bold text-gray-900"><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold <?= $badgeColor ?>"><?= htmlspecialchars($st) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-gray-500"><?= $s['waktu_absen'] ?? '-' ?></td>
                                <td class="py-3.5 px-4 text-xs text-gray-500"><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
<?php
            $htmlPreview = ob_get_clean();
            $persentase = ($totalSiswa > 0) ? round(($hadir / $totalSiswa) * 100, 2) : 0;

            echo json_encode([
                'success' => true,
                'preview' => $htmlPreview,
                'stats' => [
                    'total_siswa' => $totalSiswa,
                    'hadir'       => $hadir,
                    'izin'        => $izin,
                    'sakit'       => $sakit,
                    'alfa'        => $alfa,
                    'terlambat'   => $terlambat,
                    'persentase'  => $persentase
                ],
                'tidak_masuk_list' => $tidakMasukList
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;


    // ==========================================
    // JIKA MODUL TIDAK DIKENALI
    // ==========================================
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Sumber modul laporan tidak dikenali.'
        ]);
        break;
}
?>