<?php
require_once __DIR__ . '/../models/AbsensiModel.php';
require_once __DIR__ . '/../models/NilaiModel.php';
require_once __DIR__ . '/../models/SiswaModel.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WaliKelasController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    private function getGuruId()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return null;

        $stmt = $this->db->prepare("SELECT guru_id FROM users WHERE id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();

        if ($user && !empty($user['guru_id'])) {
            return $user['guru_id'];
        }

        $stmt2 = $this->db->prepare("SELECT id FROM guru WHERE username = :uname OR id = :uid LIMIT 1");
        $stmt2->execute(['uname' => $_SESSION['username'] ?? '', 'uid' => $userId]);
        $guru = $stmt2->fetch();

        return $guru['id'] ?? null;
    }

    private function getKelasBinaan($guruId)
    {
        $stmtWali = $this->db->prepare("SELECT kelas FROM wali_kelas WHERE guru_id = :guru_id LIMIT 1");
        $stmtWali->execute(['guru_id' => $guruId]);
        $dataWali = $stmtWali->fetch(PDO::FETCH_ASSOC);
        return $dataWali['kelas'] ?? '';
    }

    public function dashboard()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak. Anda bukan Wali Kelas.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $totalSiswaKelas = 0;
        $listMapelBinaan = [];
        $listMapelDiampu = [];

        if (!empty($kelasBinaan)) {
            $stmtSiswa = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas = :kelas");
            $stmtSiswa->execute(['kelas' => $kelasBinaan]);
            $totalSiswaKelas = $stmtSiswa->fetchColumn();

            $parts = explode(' ', $kelasBinaan);
            $tingkatJurusan = count($parts) >= 2 ? $parts[0] . ' ' . $parts[1] : $kelasBinaan;

            $stmtBinaan = $this->db->prepare("
                SELECT m.*, 
                g.nama_guru,
                (SELECT COUNT(*) FROM siswa s WHERE s.kelas = :kelas_binaan) as total_siswa,
                (SELECT COUNT(*) FROM nilai n JOIN siswa s ON n.siswa_id = s.id WHERE n.mapel_id = m.id AND s.kelas = :kelas_binaan AND n.nilai_akhir > 0) as sudah_diisi
                FROM mata_pelajaran m 
                LEFT JOIN guru g ON m.guru_id = g.id
                WHERE m.tingkat_jurusan LIKE :tingkat
                ORDER BY m.nama_mapel ASC
            ");
            $stmtBinaan->execute([
                'kelas_binaan' => $kelasBinaan,
                'tingkat' => "$tingkatJurusan%"
            ]);
            $listMapelBinaan = $stmtBinaan->fetchAll();
        }

        $stmtDiampu = $this->db->prepare("
            SELECT m.*, 
            g.nama_guru,
            (SELECT COUNT(*) FROM siswa s WHERE s.kelas LIKE CONCAT(m.tingkat_jurusan, '%')) as total_siswa,
            (SELECT COUNT(*) FROM nilai n JOIN siswa s ON n.siswa_id = s.id WHERE n.mapel_id = m.id AND s.kelas LIKE CONCAT(m.tingkat_jurusan, '%') AND n.nilai_akhir > 0) as sudah_diisi
            FROM mata_pelajaran m 
            LEFT JOIN guru g ON m.guru_id = g.id
            WHERE m.guru_id = :guru_id
            ORDER BY m.nama_mapel ASC
        ");
        $stmtDiampu->execute(['guru_id' => $guruId]);
        $listMapelDiampu = $stmtDiampu->fetchAll();

        $title = "Dashboard Wali Kelas - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/walikelas/dashboard.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function inputNilaiKelas()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak. Anda bukan Wali Kelas.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        if (empty($kelasBinaan)) {
            die("Anda belum memiliki kelas binaan.");
        }

        $stmtSekolah = $this->db->query("SELECT kkm_kktp FROM sekolah LIMIT 1");
        $sekolahInfo = $stmtSekolah->fetch();
        $kkm = $sekolahInfo['kkm_kktp'] ?? 75;

        $parts = explode(' ', $kelasBinaan);
        $tingkatJurusan = count($parts) >= 2 ? $parts[0] . ' ' . $parts[1] : $kelasBinaan;

        $stmtMapel = $this->db->prepare("SELECT id, kode_mapel, is_sub, nama_mapel FROM mata_pelajaran WHERE tingkat_jurusan LIKE :tingkat ORDER BY kelompok ASC, urutan ASC, nama_mapel ASC");
        $stmtMapel->execute(['tingkat' => "$tingkatJurusan%"]);
        $listMapel = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $whereSql = "WHERE s.kelas = :kelas";
        $params = ['kelas' => $kelasBinaan];

        if (!empty($search)) {
            $whereSql .= " AND s.nama_siswa LIKE :search";
            $params['search'] = "%$search%";
        }

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM siswa s $whereSql");
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = ceil($totalData / $limit);

        $stmtSiswa = $this->db->prepare("SELECT s.id as siswa_id, s.nis, s.nama_siswa FROM siswa s $whereSql ORDER BY s.nama_siswa ASC LIMIT $limit OFFSET $offset");
        $stmtSiswa->execute($params);
        $listSiswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        $stmtRank = $this->db->prepare("
            SELECT s.id as siswa_id, COALESCE(SUM(n.nilai_akhir), 0) as total_nilai
            FROM siswa s
            LEFT JOIN nilai n ON s.id = n.siswa_id
            WHERE s.kelas = :kelas
            GROUP BY s.id
            ORDER BY total_nilai DESC
        ");
        $stmtRank->execute(['kelas' => $kelasBinaan]);
        $rankingData = $stmtRank->fetchAll(PDO::FETCH_ASSOC);

        $ranks = [];
        $position = 1;
        foreach ($rankingData as $rd) {
            $ranks[$rd['siswa_id']] = $position++;
        }

        $listRekapNilai = [];
        foreach ($listSiswa as $siswa) {
            $sId = $siswa['siswa_id'];
            $stmtNilai = $this->db->prepare("SELECT mapel_id, nilai_akhir FROM nilai WHERE siswa_id = :sid");
            $stmtNilai->execute(['sid' => $sId]);
            $nilaiRows = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);

            $mapelNilaiMap = [];
            foreach ($nilaiRows as $nr) {
                $mapelNilaiMap[$nr['mapel_id']] = $nr['nilai_akhir'];
            }

            $listRekapNilai[] = [
                'siswa_id' => $sId,
                'nis' => $siswa['nis'],
                'nama_siswa' => $siswa['nama_siswa'],
                'nilai_mapel' => $mapelNilaiMap,
                'peringkat' => $ranks[$sId] ?? '-'
            ];
        }

        $title = "Rekapitulasi Nilai Kelas - Wali Kelas";
        ob_start();
        require_once __DIR__ . '/../views/walikelas/nilai.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function pratinjauRaport()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $siswaId = $_GET['siswa_id'] ?? null;
        if (!$siswaId) {
            $stmtFirst = $this->db->prepare("SELECT id FROM siswa WHERE kelas = :kelas LIMIT 1");
            $stmtFirst->execute(['kelas' => $kelasBinaan]);
            $fSiswa = $stmtFirst->fetch();
            $siswaId = $fSiswa['id'] ?? null;
        }

        if (!$siswaId) {
            die("Belum ada data siswa di kelas binaan ini.");
        }

        $stmtSiswa = $this->db->prepare("SELECT * FROM siswa WHERE id = :id LIMIT 1");
        $stmtSiswa->execute(['id' => $siswaId]);
        $siswa = $stmtSiswa->fetch();

        $stmtSekolah = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();

        $kelasArray = explode(' ', $siswa['kelas']);
        $tingkatJurusan = (count($kelasArray) >= 2) ? $kelasArray[0] . ' ' . $kelasArray[1] : $siswa['kelas'];

        // Mengurutkan berdasarkan Kelompok (Umum/Kejuruan), lalu Urutan No Cetak, lalu Mapel ID
        $stmtNilai = $this->db->prepare("
            SELECT m.id, m.is_sub, m.kelompok_mapel, m.nama_mapel, n.nilai_akhir
            FROM mata_pelajaran m
            LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = :sid
            WHERE m.tingkat_jurusan = :tingkat_jurusan OR m.tingkat_jurusan = '' OR m.tingkat_jurusan IS NULL
            ORDER BY m.kelompok ASC, m.urutan ASC, m.id ASC
        ");
        $stmtNilai->execute(['sid' => $siswaId, 'tingkat_jurusan' => $tingkatJurusan]);
        $listNilaiSiswa = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);

        $stmtAbsen = $this->db->prepare("SELECT * FROM absensi WHERE siswa_id = :sid LIMIT 1");
        $stmtAbsen->execute(['sid' => $siswaId]);
        $absensi = $stmtAbsen->fetch();

        $stmtWaliInfo = $this->db->prepare("
            SELECT g.nama_guru, g.nip, u.tanda_tangan 
            FROM wali_kelas wk 
            JOIN guru g ON wk.guru_id = g.id 
            LEFT JOIN users u ON g.id = u.guru_id 
            WHERE wk.kelas = :kelas LIMIT 1
        ");
        $stmtWaliInfo->execute(['kelas' => $siswa['kelas']]);
        $waliInfo = $stmtWaliInfo->fetch();

        $namaWaliKelas = $waliInfo['nama_guru'] ?? 'Belum Ditentukan';
        $nipWaliKelas = $waliInfo['nip'] ?? '-';
        $waliKelasTtd = $waliInfo['tanda_tangan'] ?? '';

        $title = "Pratinjau Raport - " . $siswa['nama_siswa'];

        ob_start();
        require_once __DIR__ . '/../views/walikelas/pratinjau_raport.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function absensiIndex()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $stmt = $this->db->prepare("
            SELECT s.*, a.sakit, a.izin, a.tanpa_keterangan 
            FROM siswa s 
            LEFT JOIN absensi a ON s.id = a.siswa_id 
            WHERE s.kelas = :kelas 
            ORDER BY s.nama_siswa ASC
        ");
        $stmt->execute(['kelas' => $kelasBinaan]);
        $listSiswa = $stmt->fetchAll();

        $title = "Input Absensi - e-Raport SMK Tiara Nusa";

        ob_start();
        require_once __DIR__ . '/../views/walikelas/absensi.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function absensiStore()
    {
        SessionHelper::checkLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siswaIds = $_POST['siswa_id'] ?? [];
            $sakits = $_POST['sakit'] ?? [];
            $izins = $_POST['izin'] ?? [];
            $tanpas = $_POST['tanpa_keterangan'] ?? [];

            $stmtCheck = $this->db->prepare("SELECT id FROM absensi WHERE siswa_id = :sid LIMIT 1");
            $stmtUpdate = $this->db->prepare("UPDATE absensi SET sakit = :sakit, izin = :izin, tanpa_keterangan = :tanpa WHERE siswa_id = :sid");
            $stmtInsert = $this->db->prepare("INSERT INTO absensi (siswa_id, sakit, izin, tanpa_keterangan, semester, tahun_pelajaran) VALUES (:sid, :sakit, :izin, :tanpa, 'Ganjil', '2025/2026')");

            for ($i = 0; $i < count($siswaIds); $i++) {
                $sid = $siswaIds[$i];
                $sakit = intval($sakits[$i] ?? 0);
                $izin = intval($izins[$i] ?? 0);
                $tanpa = intval($tanpas[$i] ?? 0);

                $stmtCheck->execute(['sid' => $sid]);
                if ($stmtCheck->fetch()) {
                    $stmtUpdate->execute([
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'tanpa' => $tanpa,
                        'sid' => $sid
                    ]);
                } else {
                    $stmtInsert->execute([
                        'sid' => $sid,
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'tanpa' => $tanpa
                    ]);
                }
            }

            header("Location: /walikelas/absensi?success=1");
            exit();
        }
    }

    public function downloadTemplateAbsensi()
    {
        SessionHelper::checkLogin();
        require_once __DIR__ . '/../vendor/autoload.php';

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $stmt = $this->db->prepare("SELECT id, nis, nama_siswa FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
        $stmt->execute(['kelas' => $kelasBinaan]);
        $siswaList = $stmt->fetchAll();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Absensi');

        $sheet->setCellValue('A1', 'TEMPLATE IMPOR DATA ABSENSI SISWA - SMK TIARA NUSA');
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A2', 'Petunjuk: Jangan ubah kolom A (ID_SISWA). Masukkan angka pada kolom Sakit, Izin, dan Tanpa Keterangan.');
        $sheet->mergeCells('A2:F2');

        $sheet->setCellValue('A4', 'ID_SISWA');
        $sheet->setCellValue('B4', 'NIS');
        $sheet->setCellValue('C4', 'NAMA SISWA');
        $sheet->setCellValue('D4', 'SAKIT');
        $sheet->setCellValue('E4', 'IZIN');
        $sheet->setCellValue('F4', 'TANPA_KETERANGAN');

        $row = 5;
        foreach ($siswaList as $s) {
            $sheet->setCellValue('A' . $row, $s['id']);
            $sheet->setCellValue('B' . $row, $s['nis']);
            $sheet->setCellValue('C' . $row, $s['nama_siswa']);
            $sheet->setCellValue('D' . $row, 0);
            $sheet->setCellValue('E' . $row, 0);
            $sheet->setCellValue('F' . $row, 0);
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Absensi_' . str_replace(' ', '_', $kelasBinaan) . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function importAbsensi()
    {
        SessionHelper::checkLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $file = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                $stmtCheck = $this->db->prepare("SELECT id FROM absensi WHERE siswa_id = :sid LIMIT 1");
                $stmtUpdate = $this->db->prepare("UPDATE absensi SET sakit = :sakit, izin = :izin, tanpa_keterangan = :tanpa WHERE siswa_id = :sid");
                $stmtInsert = $this->db->prepare("INSERT INTO absensi (siswa_id, sakit, izin, tanpa_keterangan, semester, tahun_pelajaran) VALUES (:sid, :sakit, :izin, :tanpa, 'Ganjil', '2025/2026')");

                for ($i = 4; $i < count($rows); $i++) {
                    $sid = trim($rows[$i][0] ?? '');
                    $sakit = intval($rows[$i][3] ?? 0);
                    $izin = intval($rows[$i][4] ?? 0);
                    $tanpa = intval($rows[$i][5] ?? 0);

                    if (!empty($sid) && is_numeric($sid)) {
                        $stmtCheck->execute(['sid' => $sid]);
                        if ($stmtCheck->fetch()) {
                            $stmtUpdate->execute([
                                'sakit' => $sakit,
                                'izin' => $izin,
                                'tanpa' => $tanpa,
                                'sid' => $sid
                            ]);
                        } else {
                            $stmtInsert->execute([
                                'sid' => $sid,
                                'sakit' => $sakit,
                                'izin' => $izin,
                                'tanpa' => $tanpa
                            ]);
                        }
                    }
                }
                header("Location: /walikelas/absensi?success=1");
                exit();
            } catch (\Exception $e) {
                header("Location: /walikelas/absensi?error=import");
                exit();
            }
        }
    }

    public function editNilaiSiswa()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $siswaId = $_GET['siswa_id'] ?? null;
        if (!$siswaId) {
            header("Location: /walikelas/nilai");
            exit();
        }

        $stmtSiswa = $this->db->prepare("SELECT * FROM siswa WHERE id = :id LIMIT 1");
        $stmtSiswa->execute(['id' => $siswaId]);
        $siswa = $stmtSiswa->fetch();

        if (!$siswa) {
            die("Data siswa tidak ditemukan.");
        }

        $kelasSiswa = $siswa['kelas'];

        $stmtSekolah = $this->db->query("SELECT kkm_kktp FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();
        $kkm = $sekolah['kkm_kktp'] ?? 75;

        $stmtNilai = $this->db->prepare("
        SELECT m.id AS mapel_id, m.is_sub, m.nama_mapel, m.kelompok_mapel, m.tingkat_jurusan AS kelas_mapel,
               COALESCE(g.nama_guru, 'Belum Ditentukan') AS nama_guru,
               n.nilai_akhir 
        FROM mata_pelajaran m 
        LEFT JOIN guru g ON m.guru_id = g.id
        LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = :sid
        WHERE m.tingkat_jurusan = :kelas OR :kelas LIKE CONCAT(m.tingkat_jurusan, '%')
        ORDER BY m.kelompok ASC, m.urutan ASC, m.nama_mapel ASC
    ");
        $stmtNilai->execute(['sid' => $siswaId, 'kelas' => $kelasSiswa]);
        $listNilaiMapel = $stmtNilai->fetchAll();

        if (empty($listNilaiMapel)) {
            $stmtFallback = $this->db->prepare("
            SELECT m.id AS mapel_id, m.is_sub, m.nama_mapel, m.kelompok_mapel, m.tingkat_jurusan AS kelas_mapel,
                   COALESCE(g.nama_guru, 'Belum Ditentukan') AS nama_guru,
                   n.nilai_akhir 
            FROM mata_pelajaran m 
            LEFT JOIN guru g ON m.guru_id = g.id
            LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = :sid
            ORDER BY m.kelompok ASC, m.urutan ASC, m.nama_mapel ASC
        ");
            $stmtFallback->execute(['sid' => $siswaId]);
            $listNilaiMapel = $stmtFallback->fetchAll();
        }

        $title = "Edit Nilai Peserta Didik";
        ob_start();
        require_once __DIR__ . '/../views/walikelas/edit_nilai_siswa.php';
        $content = ob_get_clean();

        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function updateNilaiSiswa()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siswaId = $_POST['siswa_id'] ?? null;
            $mapelIds = $_POST['mapel_id'] ?? [];
            $nilaiAkhirs = $_POST['nilai_akhir'] ?? [];

            if ($siswaId && !empty($mapelIds)) {
                foreach ($mapelIds as $index => $mapelId) {
                    $nilaiAkhir = $nilaiAkhirs[$index] ?? 0;

                    $stmtCheck = $this->db->prepare("SELECT id FROM nilai WHERE siswa_id = :sid AND mapel_id = :mid LIMIT 1");
                    $stmtCheck->execute(['sid' => $siswaId, 'mid' => $mapelId]);
                    $exists = $stmtCheck->fetch();

                    if ($exists) {
                        $stmtUpdate = $this->db->prepare("
                            UPDATE nilai 
                            SET nilai_akhir = :nilai 
                            WHERE siswa_id = :sid AND mapel_id = :mid
                        ");
                        $stmtUpdate->execute([
                            'nilai' => $nilaiAkhir,
                            'sid' => $siswaId,
                            'mid' => $mapelId
                        ]);
                    } else {
                        $stmtInsert = $this->db->prepare("
                            INSERT INTO nilai (siswa_id, mapel_id, nilai_akhir) 
                            VALUES (:sid, :mid, :nilai)
                        ");
                        $stmtInsert->execute([
                            'sid' => $siswaId,
                            'mid' => $mapelId,
                            'nilai' => $nilaiAkhir
                        ]);
                    }
                }
            }

            header("Location: /walikelas/nilai?success=updated");
            exit();
        }
    }

    public function cetakSemuaRaport()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $stmtSiswa = $this->db->prepare("SELECT * FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
        $stmtSiswa->execute(['kelas' => $kelasBinaan]);
        $listSemuaSiswa = $stmtSiswa->fetchAll();

        if (empty($listSemuaSiswa)) {
            die("Tidak ada data siswa di kelas ini.");
        }

        $stmtSekolah = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();

        $stmtWaliInfo = $this->db->prepare("
            SELECT g.nama_guru, g.nip, u.tanda_tangan 
            FROM wali_kelas wk 
            JOIN guru g ON wk.guru_id = g.id 
            LEFT JOIN users u ON g.id = u.guru_id 
            WHERE wk.kelas = :kelas LIMIT 1
        ");
        $stmtWaliInfo->execute(['kelas' => $kelasBinaan]);
        $waliInfo = $stmtWaliInfo->fetch();

        $namaWaliKelas = $waliInfo['nama_guru'] ?? 'Belum Ditentukan';
        $nipWaliKelas = $waliInfo['nip'] ?? '-';
        $waliKelasTtd = $waliInfo['tanda_tangan'] ?? '';

        $dataRaportMassal = [];
        foreach ($listSemuaSiswa as $s) {
            $kelasArray = explode(' ', $s['kelas']);
            $tingkatJurusan = (count($kelasArray) >= 2) ? $kelasArray[0] . ' ' . $kelasArray[1] : $s['kelas'];

            $stmtNilai = $this->db->prepare("
                SELECT m.id, m.is_sub, m.kelompok_mapel, m.nama_mapel, n.nilai_akhir
                FROM mata_pelajaran m
                LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = :sid
                WHERE m.tingkat_jurusan = :tingkat_jurusan OR m.tingkat_jurusan = '' OR m.tingkat_jurusan IS NULL
                ORDER BY m.kelompok ASC, m.urutan ASC, m.id ASC
            ");
            $stmtNilai->execute(['sid' => $s['id'], 'tingkat_jurusan' => $tingkatJurusan]);
            $nilaiSiswa = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);

            $stmtAbsen = $this->db->prepare("SELECT * FROM absensi WHERE siswa_id = :sid LIMIT 1");
            $stmtAbsen->execute(['sid' => $s['id']]);
            $absensi = $stmtAbsen->fetch();

            $dataRaportMassal[] = [
                'siswa' => $s,
                'nilai' => $nilaiSiswa,
                'absensi' => $absensi
            ];
        }

        require_once __DIR__ . '/../views/walikelas/cetak_semua_raport.php';
    }

    public function cetakFisikRaport()
    {
        SessionHelper::checkLogin();
        $siswaId = $_GET['siswa_id'] ?? null;
        if (!$siswaId) die("Siswa tidak dipilih.");

        $stmtSiswa = $this->db->prepare("SELECT * FROM siswa WHERE id = :id LIMIT 1");
        $stmtSiswa->execute(['id' => $siswaId]);
        $siswa = $stmtSiswa->fetch();

        $stmtSekolah = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();

        $kelasArray = explode(' ', $siswa['kelas']);
        $tingkatJurusan = (count($kelasArray) >= 2) ? $kelasArray[0] . ' ' . $kelasArray[1] : $siswa['kelas'];

        $stmtNilai = $this->db->prepare("
            SELECT m.id, m.is_sub, m.kelompok_mapel, m.nama_mapel, n.nilai_akhir
            FROM mata_pelajaran m
            LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = :sid
            WHERE m.tingkat_jurusan = :tingkat_jurusan OR m.tingkat_jurusan = '' OR m.tingkat_jurusan IS NULL
            ORDER BY m.kelompok ASC, m.urutan ASC, m.id ASC
        ");
        $stmtNilai->execute(['sid' => $siswaId, 'tingkat_jurusan' => $tingkatJurusan]);
        $listNilaiSiswa = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);

        $stmtAbsen = $this->db->prepare("SELECT * FROM absensi WHERE siswa_id = :sid LIMIT 1");
        $stmtAbsen->execute(['sid' => $siswaId]);
        $absensi = $stmtAbsen->fetch();

        $kelas = $siswa['kelas'] ?? '';
        $stmtWaliInfo = $this->db->prepare("
            SELECT g.nama_guru, u.tanda_tangan 
            FROM wali_kelas wk 
            JOIN guru g ON wk.guru_id = g.id 
            LEFT JOIN users u ON g.id = u.guru_id 
            WHERE wk.kelas = :kelas LIMIT 1
        ");
        $stmtWaliInfo->execute(['kelas' => $kelas]);
        $waliInfo = $stmtWaliInfo->fetch();
        $namaWaliKelas = $waliInfo['nama_guru'] ?? 'Wali Kelas';
        $waliKelasTtd = $waliInfo['tanda_tangan'] ?? '';

        require_once __DIR__ . '/../views/walikelas/cetak_raport_pdf.php';
    }

    public function inputAbsensi()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak. Anda bukan Wali Kelas.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $listSiswa = [];
        if (!empty($kelasBinaan)) {
            $stmt = $this->db->prepare("
                SELECT s.*, a.sakit, a.izin, a.tanpa_keterangan 
                FROM siswa s 
                LEFT JOIN absensi a ON s.id = a.siswa_id 
                WHERE s.kelas = :kelas 
                ORDER BY s.nama_siswa ASC
            ");
            $stmt->execute(['kelas' => $kelasBinaan]);
            $listSiswa = $stmt->fetchAll();
        }

        $title = "Input Absensi Siswa - Wali Kelas";
        ob_start();
        require_once __DIR__ . '/../views/walikelas/absensi.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function storeAbsensi()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siswaIds = $_POST['siswa_id'] ?? [];
            $sakits = $_POST['sakit'] ?? [];
            $izins = $_POST['izin'] ?? [];
            $tks = $_POST['tanpa_keterangan'] ?? [];

            $stmtSekolah = $this->db->query("SELECT tahun_pelajaran, semester FROM sekolah LIMIT 1");
            $sekolah = $stmtSekolah->fetch();
            $tp = $sekolah['tahun_pelajaran'] ?? '2025/2026';
            $sem = $sekolah['semester'] ?? 'Ganjil';

            for ($i = 0; $i < count($siswaIds); $i++) {
                $siswaId = $siswaIds[$i];
                $sakit = (int)($sakits[$i] ?? 0);
                $izin = (int)($izins[$i] ?? 0);
                $tk = (int)($tks[$i] ?? 0);

                $stmtCek = $this->db->prepare("SELECT id FROM absensi WHERE siswa_id = :sid AND semester = :sem AND tahun_pelajaran = :tp");
                $stmtCek->execute(['sid' => $siswaId, 'sem' => $sem, 'tp' => $tp]);
                $existing = $stmtCek->fetch();

                if ($existing) {
                    $stmtUp = $this->db->prepare("UPDATE absensi SET sakit = :sakit, izin = :izin, tanpa_keterangan = :tk WHERE id = :id");
                    $stmtUp->execute(['sakit' => $sakit, 'izin' => $izin, 'tk' => $tk, 'id' => $existing['id']]);
                } else {
                    $stmtIn = $this->db->prepare("INSERT INTO absensi (siswa_id, sakit, izin, tanpa_keterangan, semester, tahun_pelajaran) VALUES (:sid, :sakit, :izin, :tk, :sem, :tp)");
                    $stmtIn->execute(['sid' => $siswaId, 'sakit' => $sakit, 'izin' => $izin, 'tk' => $tk, 'sem' => $sem, 'tp' => $tp]);
                }
            }

            header("Location: /walikelas/absensi?success=1");
            exit();
        }
    }

    public function templateAbsensi()
    {
        SessionHelper::checkLogin();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Absensi');

        $sheet->setCellValue('A1', 'NIS');
        $sheet->setCellValue('B1', 'Nama Siswa');
        $sheet->setCellValue('C1', 'Sakit');
        $sheet->setCellValue('D1', 'Izin');
        $sheet->setCellValue('E1', 'Tanpa Keterangan');

        $sheet->setCellValue('A2', '1001');
        $sheet->setCellValue('B2', 'Contoh Nama Siswa');
        $sheet->setCellValue('C2', '0');
        $sheet->setCellValue('D2', '0');
        $sheet->setCellValue('E2', '0');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Absensi_Siswa.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function cetakRaport()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $listSiswa = [];
        if (!empty($kelasBinaan)) {
            $stmt = $this->db->prepare("SELECT * FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
            $stmt->execute(['kelas' => $kelasBinaan]);
            $listSiswa = $stmt->fetchAll();
        }

        $title = "Cetak Raport Siswa - Wali Kelas";
        ob_start();
        require_once __DIR__ . '/../views/walikelas/raport.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function raportIndex()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $stmt = $this->db->prepare("SELECT * FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
        $stmt->execute(['kelas' => $kelasBinaan]);
        $listSiswa = $stmt->fetchAll();

        $title = "Cetak Dokumen Raport - e-Raport SMK Tiara Nusa";

        ob_start();
        require_once __DIR__ . '/../views/walikelas/raport.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function cetakPdfRaport()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        $siswaId = $_GET['siswa_id'] ?? null;
        if (!$siswaId) {
            die("ID Siswa tidak ditemukan.");
        }

        $stmtSiswa = $this->db->prepare("SELECT * FROM siswa WHERE id = :id AND kelas = :kelas LIMIT 1");
        $stmtSiswa->execute(['id' => $siswaId, 'kelas' => $kelasBinaan]);
        $siswa = $stmtSiswa->fetch();

        if (!$siswa) {
            die("Data siswa tidak ditemukan atau bukan bagian dari kelas binaan Anda.");
        }

        $stmtSekolah = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();

        $kelasArray = explode(' ', $siswa['kelas']);
        $tingkatJurusan = (count($kelasArray) >= 2) ? $kelasArray[0] . ' ' . $kelasArray[1] : $siswa['kelas'];

        $stmtNilai = $this->db->prepare("
            SELECT m.id, m.is_sub, m.kelompok_mapel, m.nama_mapel, n.nilai_akhir
            FROM mata_pelajaran m
            LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = :sid
            WHERE m.tingkat_jurusan = :tingkat_jurusan OR m.tingkat_jurusan = '' OR m.tingkat_jurusan IS NULL
            ORDER BY m.kelompok ASC, m.urutan ASC, m.id ASC
        ");
        $stmtNilai->execute(['sid' => $siswaId, 'tingkat_jurusan' => $tingkatJurusan]);
        $listNilaiSiswa = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);

        $stmtAbsen = $this->db->prepare("SELECT * FROM absensi WHERE siswa_id = :sid LIMIT 1");
        $stmtAbsen->execute(['sid' => $siswaId]);
        $absensi = $stmtAbsen->fetch();

        $stmtWaliInfo = $this->db->prepare("
            SELECT g.nama_guru, g.nip, u.tanda_tangan 
            FROM wali_kelas wk 
            JOIN guru g ON wk.guru_id = g.id 
            LEFT JOIN users u ON g.id = u.guru_id 
            WHERE wk.kelas = :kelas LIMIT 1
        ");
        $stmtWaliInfo->execute(['kelas' => $siswa['kelas']]);
        $waliInfo = $stmtWaliInfo->fetch();

        $namaWaliKelas = $waliInfo['nama_guru'] ?? 'Belum Ditentukan';
        $nipWaliKelas = $waliInfo['nip'] ?? '-';
        $waliKelasTtd = $waliInfo['tanda_tangan'] ?? '';

        require_once __DIR__ . '/../views/walikelas/cetak_raport_pdf.php';
    }

    public function uploadTtd()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $stmt = $this->db->prepare("SELECT tanda_tangan FROM users WHERE guru_id = :guru_id LIMIT 1");
        $stmt->execute(['guru_id' => $guruId]);
        $userTtd = $stmt->fetch();

        $title = "Upload Tanda Tangan - Wali Kelas";
        ob_start();
        require_once __DIR__ . '/../views/walikelas/ttd.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function downloadRekapExcel()
    {
        SessionHelper::checkLogin();
        if (!isset($_SESSION['is_walikelas']) || !$_SESSION['is_walikelas']) {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $kelasBinaan = $this->getKelasBinaan($guruId);

        if (empty($kelasBinaan)) {
            die("Data penugasan wali kelas tidak ditemukan untuk akun ini. Pastikan Anda sudah ditugaskan sebagai wali kelas di menu Admin.");
        }

        $stmtGuru = $this->db->prepare("SELECT nama_guru FROM guru WHERE id = :gid LIMIT 1");
        $stmtGuru->execute(['gid' => $guruId]);
        $dataGuru = $stmtGuru->fetch();
        $namaWaliKelas = $dataGuru['nama_guru'] ?? ($_SESSION['nama_lengkap'] ?? 'Wali Kelas');

        $stmtSekolah = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();
        $semesterAktif = $sekolah['semester'] ?? 'Ganjil';
        $tahunPelajaran = $sekolah['tahun_pelajaran'] ?? '2026/2027';

        $faseKelas = (strpos($kelasBinaan, 'X ') === 0) ? 'E' : 'F';

        // Mengambil tingkat dan jurusan dari kelas binaan (misal: "X TJKT 1" -> "X TJKT")
        $parts = explode(' ', $kelasBinaan);
        $tingkatJurusan = count($parts) >= 2 ? $parts[0] . ' ' . $parts[1] : $kelasBinaan;

        // Hanya mengambil mapel yang sesuai dengan kelas masing-masing berdasarkan database
        $stmtMapel = $this->db->prepare("SELECT id, kode_mapel, kelompok_mapel, kelompok, urutan, nama_mapel FROM mata_pelajaran WHERE tingkat_jurusan LIKE :tingkat ORDER BY kelompok ASC, urutan ASC, nama_mapel ASC");
        $stmtMapel->execute(['tingkat' => "$tingkatJurusan%"]);
        $listMapel = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        if (empty($listMapel)) {
            $stmtMapelAll = $this->db->query("SELECT id, kode_mapel, kelompok_mapel, kelompok, urutan, nama_mapel FROM mata_pelajaran ORDER BY kelompok ASC, urutan ASC, nama_mapel ASC");
            $listMapel = $stmtMapelAll->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmtSiswa = $this->db->prepare("SELECT * FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
        $stmtSiswa->execute(['kelas' => $kelasBinaan]);
        $listSiswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        $rekapData = [];
        foreach ($listSiswa as $siswa) {
            $siswaId = $siswa['id'];
            $nilaiMapel = [];
            $totalNilai = 0;
            $jumlahMapel = count($listMapel);

            foreach ($listMapel as $m) {
                $stmtN = $this->db->prepare("SELECT nilai_akhir FROM nilai WHERE siswa_id = :sid AND mapel_id = :mid LIMIT 1");
                $stmtN->execute(['sid' => $siswaId, 'mid' => $m['id']]);
                $resN = $stmtN->fetch();
                $val = $resN ? round($resN['nilai_akhir']) : 0;
                $nilaiMapel[$m['id']] = $val;
                $totalNilai += $val;
            }

            $rataRata = $jumlahMapel > 0 ? ($totalNilai / $jumlahMapel) : 0;

            $stmtAbsen = $this->db->prepare("SELECT COALESCE(SUM(sakit),0) as sakit, COALESCE(SUM(izin),0) as izin, COALESCE(SUM(tanpa_keterangan),0) as alfa FROM absensi WHERE siswa_id = :sid LIMIT 1");
            try {
                $stmtAbsen->execute(['sid' => $siswaId]);
                $absen = $stmtAbsen->fetch();
                $sakit = $absen['sakit'] ?? 0;
                $izin = $absen['izin'] ?? 0;
                $alfa = $absen['alfa'] ?? 0;
            } catch (\Exception $e) {
                $sakit = 0;
                $izin = 0;
                $alfa = 0;
            }

            $rekapData[] = [
                'siswa_id' => $siswaId,
                'nis' => $siswa['nis'],
                'nisn' => $siswa['nisn'],
                'nama_siswa' => $siswa['nama_siswa'],
                'nilai_mapel' => $nilaiMapel,
                'rata_rata' => $rataRata,
                'sakit' => $sakit,
                'izin' => $izin,
                'alfa' => $alfa
            ];
        }

        usort($rekapData, function ($a, $b) {
            return $b['rata_rata'] <=> $a['rata_rata'];
        });

        foreach ($rekapData as $index => &$item) {
            $item['peringkat'] = $index + 1;
        }
        unset($item);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai Kelas');

        $lastColIndex = 4 + count($listMapel) + 4;
        $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

        // Header Dokumen
        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->setCellValue('A1', 'REKAPITULASI NILAI AKHIR PESERTA DIDIK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$lastColumnLetter}2");
        $sheet->setCellValue('A2', 'SMK TIARA NUSA');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A4', 'Kelas');
        $sheet->setCellValue('B4', ': ' . $kelasBinaan);
        $sheet->setCellValue('A5', 'Wali Kelas');
        $sheet->setCellValue('B5', ': ' . $namaWaliKelas);
        $sheet->setCellValue('A6', 'Fase / Semester');
        $sheet->setCellValue('B6', ': ' . $faseKelas . ' / ' . $semesterAktif);
        $sheet->setCellValue('A7', 'Tahun Ajaran');
        $sheet->setCellValue('B7', ': ' . $tahunPelajaran);
        $sheet->getStyle('A4:A7')->getFont()->setBold(true);

        $rowHeader = 9;
        $sheet->setCellValue("A{$rowHeader}", 'No');
        $sheet->setCellValue("B{$rowHeader}", 'NIS');
        $sheet->setCellValue("C{$rowHeader}", 'NISN');
        $sheet->setCellValue("D{$rowHeader}", 'Nama Siswa');

        $colIndex = 5;
        foreach ($listMapel as $m) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $namaHeader = !empty($m['kode_mapel']) ? $m['kode_mapel'] : $m['nama_mapel'];
            $sheet->setCellValue("{$colLetter}{$rowHeader}", $namaHeader);
            $sheet->getStyle("{$colLetter}{$rowHeader}")->getAlignment()->setWrapText(true);
            $colIndex++;
        }

        $colPeringkat = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $colSakit = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $colIzin = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $colAlfa = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);

        $sheet->setCellValue("{$colPeringkat}{$rowHeader}", 'Peringkat');
        $sheet->setCellValue("{$colSakit}{$rowHeader}", 'Sakit (S)');
        $sheet->setCellValue("{$colIzin}{$rowHeader}", 'Izin (I)');
        $sheet->setCellValue("{$colAlfa}{$rowHeader}", 'Alpa (A)');

        $headerRange = "A{$rowHeader}:{$colAlfa}{$rowHeader}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('E5E7EB');

        $currentRow = $rowHeader + 1;
        $no = 1;
        foreach ($rekapData as $data) {
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $data['nis'] ?? '-');
            $sheet->setCellValue("C{$currentRow}", $data['nisn'] ?? '-');
            $sheet->setCellValue("D{$currentRow}", $data['nama_siswa']);

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $cIdx = 5;
            foreach ($listMapel as $m) {
                $cLet = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx);
                $valNilai = $data['nilai_mapel'][$m['id']] > 0 ? $data['nilai_mapel'][$m['id']] : '-';
                $sheet->setCellValue("{$cLet}{$currentRow}", $valNilai);
                $sheet->getStyle("{$cLet}{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $cIdx++;
            }

            $sheet->setCellValue("{$colPeringkat}{$currentRow}", $data['peringkat']);
            $sheet->setCellValue("{$colSakit}{$currentRow}", $data['sakit']);
            $sheet->setCellValue("{$colIzin}{$currentRow}", $data['izin']);
            $sheet->setCellValue("{$colAlfa}{$currentRow}", $data['alfa']);

            $sheet->getStyle("{$colPeringkat}{$currentRow}:{$colAlfa}{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $currentRow++;
        }

        $endDataRow = $currentRow - 1;
        $tableRange = "A{$rowHeader}:{$colAlfa}{$endDataRow}";
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'D1D5DB'],
                ],
            ],
        ];
        $sheet->getStyle($tableRange)->applyFromArray($borderStyle);

        foreach (range('A', $colAlfa) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $filename = "Rekap_Nilai_Kelas_" . str_replace([' ', '/'], '_', $kelasBinaan) . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function storeTtd()
    {
        SessionHelper::checkLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['tanda_tangan'])) {
            $guruId = $this->getGuruId();
            if (!$guruId) {
                header("Location: /walikelas/ttd?error=1");
                exit();
            }

            if ($_FILES['tanda_tangan']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/ttd_walikelas/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['tanda_tangan']['name']);
                $targetFilePath = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

                if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
                    if (move_uploaded_file($_FILES['tanda_tangan']['tmp_name'], $targetFilePath)) {
                        $ttdPath = '/assets/uploads/ttd_walikelas/' . $fileName;

                        $stmt = $this->db->prepare("UPDATE users SET tanda_tangan = :ttd WHERE guru_id = :guru_id");
                        $stmt->execute(['ttd' => $ttdPath, 'guru_id' => $guruId]);

                        header("Location: /walikelas/ttd?success=1");
                        exit();
                    }
                }
            }
            header("Location: /walikelas/ttd?error=1");
            exit();
        }
    }
}
