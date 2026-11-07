<?php
require_once __DIR__ . '/../models/GuruModel.php';
require_once __DIR__ . '/../models/SiswaModel.php';
require_once __DIR__ . '/../models/WaliKelasModel.php';
require_once __DIR__ . '/../models/MapelModel.php';
require_once __DIR__ . '/../models/SekolahModel.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function dashboard()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        // Ambil data ringkasan untuk statistik dashboard admin
        $totalGuru = $this->db->query("SELECT COUNT(*) FROM guru")->fetchColumn();
        $totalSiswa = $this->db->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
        $totalKelas = $this->db->query("SELECT COUNT(DISTINCT kelas) FROM siswa WHERE kelas IS NOT NULL AND kelas != ''")->fetchColumn();
        $totalMapel = $this->db->query("SELECT COUNT(*) FROM mata_pelajaran")->fetchColumn();

        $title = "Dashboard Admin - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/admin/dashboard.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function dataGuru()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) die("Akses Ditolak.");

        $guruModel = new GuruModel($this->db);
        $gurus = $guruModel->getAllGuru();
        $title = "Data Guru - e-Raport";

        ob_start();
        require_once __DIR__ . '/../views/admin/guru/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function downloadRekapSemuaKelas()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses Ditolak.");
        }

        $db = $this->db;

        $stmtSekolah = $db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch() ?: [];
        $tahunPelajaran = $sekolah['tahun_pelajaran'] ?? '2026/2027';
        $semester = $sekolah['semester'] ?? 'Ganjil';
        $namaAsesmen = $sekolah['nama_asesmen'] ?? 'Asesmen Sumatif Tengah Semester';

        $stmtKelas = $db->query("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
        $daftarKelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);

        if (empty($daftarKelas)) {
            die("Tidak ada data kelas atau siswa yang ditemukan untuk direkap.");
        }

        require_once __DIR__ . '/../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($daftarKelas as $index => $namaKelas) {
            $sheet = $spreadsheet->createSheet($index);
            $sheetName = substr(str_replace(['/', '\\', '?', '*', ':', '[', ']'], '_', $namaKelas), 0, 31);
            $sheet->setTitle($sheetName);
            $sheet->setShowGridlines(true);

            $stmtWali = $db->prepare("SELECT g.nama_guru FROM wali_kelas wk JOIN guru g ON wk.guru_id = g.id WHERE wk.kelas = :kelas LIMIT 1");
            $stmtWali->execute(['kelas' => $namaKelas]);
            $namaWaliKelas = $stmtWali->fetchColumn() ?: '-';

            // Pisahkan format kelas (misal: "X TJKT 1" menjadi "X TJKT")
            $kelasArray = explode(' ', $namaKelas);
            $tingkatJurusan = count($kelasArray) >= 2 ? $kelasArray[0] . ' ' . $kelasArray[1] : $namaKelas;

            // Panggil mapel khusus jurusan tersebut dan urutkan berdasarkan Kelompok lalu Urutan
            $stmtMapel = $db->prepare("
                SELECT id, kode_mapel, nama_mapel, kelompok, is_sub, urutan FROM mata_pelajaran 
                WHERE tingkat_jurusan = :tj OR tingkat_jurusan = '' OR tingkat_jurusan IS NULL 
                ORDER BY kelompok ASC, urutan ASC, nama_mapel ASC
            ");
            $stmtMapel->execute(['tj' => $tingkatJurusan]);
            $daftarMapel = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

            $stmtSiswa = $db->prepare("SELECT * FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
            $stmtSiswa->execute(['kelas' => $namaKelas]);
            $daftarSiswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

            $totalKolomIndex = 4 + count($daftarMapel) + 5;
            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalKolomIndex);

            // --- STYLING JUDUL ATAS ---
            $sheet->mergeCells("A1:{$lastColLetter}1");
            $sheet->mergeCells("A2:{$lastColLetter}2");
            $sheet->setCellValue('A1', 'REKAP NILAI & ABSENSI PESERTA DIDIK');
            $sheet->setCellValue('A2', 'SMK TIARA NUSA');
            $sheet->getStyle('A1:A2')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFont()->setSize(13);
            $sheet->getStyle('A2')->getFont()->setSize(12);
            $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A4', 'Kelas');
            $sheet->setCellValue('B4', ': ' . $namaKelas);
            $sheet->setCellValue('A5', 'Wali Kelas');
            $sheet->setCellValue('B5', ': ' . $namaWaliKelas);
            $sheet->setCellValue('A6', 'Thn Pelajaran');
            $sheet->setCellValue('B6', ': ' . $tahunPelajaran);
            $sheet->setCellValue('A7', 'Semester');
            $sheet->setCellValue('B7', ': ' . $semester);
            $sheet->getStyle('A4:A7')->getFont()->setBold(true);

            // --- HEADER TABEL 2 TINGKAT ---
            $rowGrpHeader = 9; // Baris untuk Kelompok A / B
            $rowMapelHeader = 10; // Baris untuk Nama Mapel

            $sheet->mergeCells("A{$rowGrpHeader}:A{$rowMapelHeader}");
            $sheet->setCellValue("A{$rowGrpHeader}", 'NO');
            $sheet->mergeCells("B{$rowGrpHeader}:B{$rowMapelHeader}");
            $sheet->setCellValue("B{$rowGrpHeader}", 'NIS');
            $sheet->mergeCells("C{$rowGrpHeader}:C{$rowMapelHeader}");
            $sheet->setCellValue("C{$rowGrpHeader}", 'NISN');
            $sheet->mergeCells("D{$rowGrpHeader}:D{$rowMapelHeader}");
            $sheet->setCellValue("D{$rowGrpHeader}", 'NAMA SISWA');

            $colIndex = 5;
            $mapelCols = [];

            // Variabel Tracking Numbering & Pengelompokan
            $noA = 1;
            $noB = 1;
            $charSub = 'a';
            $inSubGroup = false;
            $startColUmum = '';
            $endColUmum = '';
            $startColKej = '';
            $endColKej = '';

            foreach ($daftarMapel as $mapel) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $kel = $mapel['kelompok'] ?? 'A';
                $isSub = $mapel['is_sub'] ?? 0;

                $prefix = '';
                if ($kel === 'A') {
                    $prefix = $noA . '. ';
                    $noA++;
                    if ($startColUmum === '') $startColUmum = $colLetter;
                    $endColUmum = $colLetter;
                } else {
                    if ($isSub == 1) {
                        if (!$inSubGroup) {
                            $inSubGroup = true;
                            $noB++;
                            $charSub = 'a';
                        }
                        $prefix = '   ' . $charSub . '. ';
                        $charSub++;
                    } else {
                        if ($inSubGroup) $inSubGroup = false;
                        $prefix = $noB . '. ';
                        $noB++;
                    }
                    if ($startColKej === '') $startColKej = $colLetter;
                    $endColKej = $colLetter;
                }

                // Menggunakan kode_mapel jika ada, jika tidak fallback ke nama_mapel
                $namaHeaderMapel = !empty($mapel['kode_mapel']) ? $mapel['kode_mapel'] : $mapel['nama_mapel'];

                $sheet->setCellValue("{$colLetter}{$rowMapelHeader}", $prefix . strtoupper($namaHeaderMapel));
                $mapelCols[$mapel['id']] = $colLetter;
                $colIndex++;
            }

            // Set Header Kelompok (Baris 9)
            if ($startColUmum !== '') {
                $sheet->mergeCells("{$startColUmum}{$rowGrpHeader}:{$endColUmum}{$rowGrpHeader}");
                $sheet->setCellValue("{$startColUmum}{$rowGrpHeader}", "A. Kelompok Mata Pelajaran Umum");
            }
            if ($startColKej !== '') {
                $sheet->mergeCells("{$startColKej}{$rowGrpHeader}:{$endColKej}{$rowGrpHeader}");
                $sheet->setCellValue("{$startColKej}{$rowGrpHeader}", "B. Kelompok Mata Pelajaran Kejuruan");
            }

            // Kolom Akhir (Rata, Rank, Absen)
            $colRata = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $colRank = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $colSakit = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $colIjin = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $colAlpha = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);

            $sheet->mergeCells("{$colRata}{$rowGrpHeader}:{$colRata}{$rowMapelHeader}");
            $sheet->setCellValue("{$colRata}{$rowGrpHeader}", 'RATA');
            $sheet->mergeCells("{$colRank}{$rowGrpHeader}:{$colRank}{$rowMapelHeader}");
            $sheet->setCellValue("{$colRank}{$rowGrpHeader}", 'RANK');
            $sheet->mergeCells("{$colSakit}{$rowGrpHeader}:{$colSakit}{$rowMapelHeader}");
            $sheet->setCellValue("{$colSakit}{$rowGrpHeader}", 'SAKIT');
            $sheet->mergeCells("{$colIjin}{$rowGrpHeader}:{$colIjin}{$rowMapelHeader}");
            $sheet->setCellValue("{$colIjin}{$rowGrpHeader}", 'IJIN');
            $sheet->mergeCells("{$colAlpha}{$rowGrpHeader}:{$colAlpha}{$rowMapelHeader}");
            $sheet->setCellValue("{$colAlpha}{$rowGrpHeader}", 'ALPA');

            $headerRange = "A{$rowGrpHeader}:{$lastColLetter}{$rowMapelHeader}";
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF10B981');
            $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // --- ISI DATA SISWA ---
            $siswaDataComputed = [];
            foreach ($daftarSiswa as $siswa) {
                $siswaId = $siswa['id'];
                $totalNilai = 0;
                $nilaiMapelSiswa = [];
                foreach ($daftarMapel as $mapel) {
                    $stmtNilai = $db->prepare("SELECT nilai_akhir FROM nilai WHERE siswa_id = :sid AND mapel_id = :mid LIMIT 1");
                    $stmtNilai->execute(['sid' => $siswaId, 'mid' => $mapel['id']]);
                    $n = $stmtNilai->fetchColumn();
                    $val = ($n !== false && $n !== null) ? round(floatval($n)) : 0;
                    $nilaiMapelSiswa[$mapel['id']] = $val;
                    $totalNilai += $val;
                }
                $rataRata = count($daftarMapel) > 0 ? round($totalNilai / count($daftarMapel)) : 0;

                $stmtAbsen = $db->prepare("SELECT sakit, izin, tanpa_keterangan FROM absensi WHERE siswa_id = :sid LIMIT 1");
                $stmtAbsen->execute(['sid' => $siswaId]);
                $siswaDataComputed[] = [
                    'siswa' => $siswa,
                    'nilai' => $nilaiMapelSiswa,
                    'rata' => $rataRata,
                    'absen' => $stmtAbsen->fetch(PDO::FETCH_ASSOC) ?: ['sakit' => 0, 'izin' => 0, 'tanpa_keterangan' => 0]
                ];
            }

            usort($siswaDataComputed, function ($a, $b) {
                return $b['rata'] <=> $a['rata'];
            });

            $currentRow = $rowMapelHeader + 1;
            $no = 1;
            foreach ($siswaDataComputed as $rankIndex => $data) {
                $sheet->setCellValue("A{$currentRow}", $no);
                $sheet->setCellValueExplicit("B{$currentRow}", $data['siswa']['nis'] ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$currentRow}", $data['siswa']['nisn'] ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("D{$currentRow}", $data['siswa']['nama_siswa']);

                foreach ($daftarMapel as $mapel) {
                    $cLet = $mapelCols[$mapel['id']];
                    $sheet->setCellValue("{$cLet}{$currentRow}", $data['nilai'][$mapel['id']]);
                }

                $sheet->setCellValue("{$colRata}{$currentRow}", $data['rata']);
                $sheet->setCellValue("{$colRank}{$currentRow}", $rankIndex + 1);
                $sheet->setCellValue("{$colSakit}{$currentRow}", $data['absen']['sakit']);
                $sheet->setCellValue("{$colIjin}{$currentRow}", $data['absen']['izin']);
                $sheet->setCellValue("{$colAlpha}{$currentRow}", $data['absen']['tanpa_keterangan']);

                $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$colRank}{$currentRow}:{$colAlpha}{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $no++;
                $currentRow++;
            }

            if (($currentRow - 1) >= $rowMapelHeader + 1) {
                $sheet->getStyle("A{$rowGrpHeader}:{$lastColLetter}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
            foreach (range('A', $lastColLetter) as $columnID) $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Rekap_Nilai_Semua_Kelas_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function waliKelasIndex()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        $search = trim($_GET['search'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Ambil daftar guru untuk dropdown form tambah/edit
        $stmtGuru = $this->db->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC");
        $listGuruOption = $stmtGuru->fetchAll();

        // Ambil daftar kelas unik dari tabel siswa untuk dropdown form tambah/edit & filter
        $stmtKelas = $this->db->query("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
        $listKelasOption = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);

        // Query & Filter
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (g.nama_guru LIKE :search OR wk.kelas LIKE :search)";
            $params['search'] = "%$search%";
        }

        // Hitung total data
        $countQuery = "SELECT COUNT(*) FROM wali_kelas wk JOIN guru g ON wk.guru_id = g.id $where";
        $stmtCount = $this->db->prepare($countQuery);
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = ceil($totalData / $limit);

        // Ambil data dengan JOIN ke tabel guru
        $query = "SELECT wk.*, g.nama_guru FROM wali_kelas wk JOIN guru g ON wk.guru_id = g.id $where ORDER BY wk.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list_walikelas = $stmt->fetchAll();

        $title = "Data Wali Kelas - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/admin/walikelas/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function storeWaliKelas()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $guru_id = trim($_POST['guru_id'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');

            try {
                // 1. Cek apakah kelas sudah memiliki wali kelas lain
                $stmtCheckKelas = $this->db->prepare("SELECT COUNT(*) FROM wali_kelas WHERE kelas = :kelas");
                $stmtCheckKelas->execute(['kelas' => $kelas]);
                if ($stmtCheckKelas->fetchColumn() > 0) {
                    header("Location: /admin/walikelas?error=kelas_exist");
                    exit();
                }

                // 2. Cek apakah guru yang bersangkutan sudah menjadi wali kelas di kelas lain
                $stmtCheckGuru = $this->db->prepare("SELECT COUNT(*) FROM wali_kelas WHERE guru_id = :guru_id");
                $stmtCheckGuru->execute(['guru_id' => $guru_id]);
                if ($stmtCheckGuru->fetchColumn() > 0) {
                    header("Location: /admin/walikelas?error=guru_exist");
                    exit();
                }

                $stmt = $this->db->prepare("INSERT INTO wali_kelas (guru_id, kelas) VALUES (:guru_id, :kelas)");
                $stmt->execute(['guru_id' => $guru_id, 'kelas' => $kelas]);

                header("Location: /admin/walikelas?success=1");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/walikelas?error=system");
                exit();
            }
        }
    }

    public function updateWaliKelas()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $guru_id = trim($_POST['guru_id'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');

            try {
                // 1. Cek apakah kelas sudah digunakan oleh data lain (kecuali data ini sendiri)
                $stmtCheckKelas = $this->db->prepare("SELECT COUNT(*) FROM wali_kelas WHERE kelas = :kelas AND id != :id");
                $stmtCheckKelas->execute(['kelas' => $kelas, 'id' => $id]);
                if ($stmtCheckKelas->fetchColumn() > 0) {
                    header("Location: /admin/walikelas?error=kelas_exist");
                    exit();
                }

                // 2. Cek apakah guru sudah menjadi wali kelas di kelas lain (kecuali data ini sendiri)
                $stmtCheckGuru = $this->db->prepare("SELECT COUNT(*) FROM wali_kelas WHERE guru_id = :guru_id AND id != :id");
                $stmtCheckGuru->execute(['guru_id' => $guru_id, 'id' => $id]);
                if ($stmtCheckGuru->fetchColumn() > 0) {
                    header("Location: /admin/walikelas?error=guru_exist");
                    exit();
                }

                $stmt = $this->db->prepare("UPDATE wali_kelas SET guru_id = :guru_id, kelas = :kelas WHERE id = :id");
                $stmt->execute(['id' => $id, 'guru_id' => $guru_id, 'kelas' => $kelas]);

                header("Location: /admin/walikelas?success=update");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/walikelas?error=system");
                exit();
            }
        }
    }

    public function templateWaliKelas()
    {
        SessionHelper::checkLogin();
        require_once __DIR__ . '/../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Wali Kelas');

        $sheet->setCellValue('A1', 'Username Guru');
        $sheet->setCellValue('B1', 'Kelas');

        $sheet->setCellValue('A2', 'budi_guru');
        $sheet->setCellValue('B2', 'X TKJ 1');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Wali_Kelas_SMKTN.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function importWaliKelas()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $file = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                for ($i = 1; $i < count($rows); $i++) {
                    $username = trim($rows[$i][0] ?? '');
                    $kelas = trim($rows[$i][1] ?? '');

                    if (empty($username) || empty($kelas)) {
                        continue;
                    }

                    $stmtG = $this->db->prepare("SELECT id FROM guru WHERE username = :uname LIMIT 1");
                    $stmtG->execute(['uname' => $username]);
                    $guru = $stmtG->fetch();

                    if ($guru) {
                        $guru_id = $guru['id'];
                        $stmt = $this->db->prepare("INSERT INTO wali_kelas (guru_id, kelas) VALUES (:gid, :kelas) ON DUPLICATE KEY UPDATE guru_id=:gid");
                        $stmt->execute(['gid' => $guru_id, 'kelas' => $kelas]);
                    }
                }
                header("Location: /admin/walikelas?success=1");
                exit();
            } catch (\Exception $e) {
                header("Location: /admin/walikelas?error=import");
                exit();
            }
        }
    }

    public function deleteWaliKelas()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $stmt = $this->db->prepare("DELETE FROM wali_kelas WHERE id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: /admin/walikelas?success=delete");
            exit();
        }
    }

    public function deleteSelectedWaliKelas()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_ids'])) {
            $ids = $_POST['selected_ids'];
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("DELETE FROM wali_kelas WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            header("Location: /admin/walikelas?success=delete");
            exit();
        }
        header("Location: /admin/walikelas");
        exit();
    }

    public function mapelIndex()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        $search = trim($_GET['search'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Data Master untuk Form Dropdown
        $stmtGuru = $this->db->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC");
        $listGuruOption = $stmtGuru->fetchAll();

        $stmtTingkat = $this->db->query("SELECT DISTINCT SUBSTRING_INDEX(kelas, ' ', 2) as tingkat_jurusan FROM siswa WHERE kelas IS NOT NULL ORDER BY tingkat_jurusan ASC");
        $listTingkatOption = $stmtTingkat->fetchAll(PDO::FETCH_COLUMN);

        // Filter Pencarian (ditambah pencarian berdasarkan kode_mapel)
        $where = "WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $where .= " AND (m.nama_mapel LIKE :search OR m.kode_mapel LIKE :search OR m.tingkat_jurusan LIKE :search OR g.nama_guru LIKE :search)";
            $params['search'] = "%$search%";
        }

        // Pagination
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM mata_pelajaran m LEFT JOIN guru g ON m.guru_id = g.id $where");
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = ceil($totalData / $limit);

        // Ambil Data: DIURUTKAN BERDASARKAN TINGKAT JURUSAN -> KELOMPOK (A/B) -> URUTAN -> NAMA
        $query = "SELECT m.*, g.nama_guru 
                  FROM mata_pelajaran m 
                  LEFT JOIN guru g ON m.guru_id = g.id 
                  $where 
                  ORDER BY m.tingkat_jurusan ASC, m.kelompok ASC, m.urutan ASC, m.nama_mapel ASC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list_mapel = $stmt->fetchAll();

        $title = "Data Mata Pelajaran - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/admin/mapel/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function storeMapel()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kode_mapel = trim($_POST['kode_mapel'] ?? '');
            $kelompok = trim($_POST['kelompok'] ?? 'A');
            $urutan = (int)($_POST['urutan'] ?? 99);
            $is_sub = (int)($_POST['is_sub'] ?? 0);
            $kelompok_mapel = trim($_POST['kelompok_mapel'] ?? '');
            $tingkat_jurusan = trim($_POST['tingkat_jurusan'] ?? '');
            $nama_mapel = trim($_POST['nama_mapel'] ?? '');
            $guru_id = trim($_POST['guru_id'] ?? '');

            try {
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE nama_mapel = :mapel AND tingkat_jurusan = :tingkat");
                $stmtCheck->execute(['mapel' => $nama_mapel, 'tingkat' => $tingkat_jurusan]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: /admin/mapel?error=mapel_exist");
                    exit();
                }

                $stmt = $this->db->prepare("INSERT INTO mata_pelajaran (kode_mapel, kelompok_mapel, kelompok, is_sub, urutan, tingkat_jurusan, nama_mapel, guru_id) 
                                            VALUES (:kode_mapel, :kel_mapel, :kelompok, :is_sub, :urutan, :tingkat, :nama, :guru_id)");
                $stmt->execute([
                    'kode_mapel' => $kode_mapel,
                    'kel_mapel' => $kelompok_mapel,
                    'kelompok' => $kelompok,
                    'is_sub' => $is_sub,
                    'urutan' => $urutan,
                    'tingkat' => $tingkat_jurusan,
                    'nama' => $nama_mapel,
                    'guru_id' => empty($guru_id) ? null : $guru_id
                ]);

                header("Location: /admin/mapel?success=1");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/mapel?error=system");
                exit();
            }
        }
    }

    public function updateMapel()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $kode_mapel = trim($_POST['kode_mapel'] ?? '');
            $kelompok = trim($_POST['kelompok'] ?? 'A');
            $urutan = (int)($_POST['urutan'] ?? 99);
            $is_sub = (int)($_POST['is_sub'] ?? 0);
            $kelompok_mapel = trim($_POST['kelompok_mapel'] ?? '');
            $tingkat_jurusan = trim($_POST['tingkat_jurusan'] ?? '');
            $nama_mapel = trim($_POST['nama_mapel'] ?? '');
            $guru_id = trim($_POST['guru_id'] ?? '');

            try {
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE nama_mapel = :mapel AND tingkat_jurusan = :tingkat AND id != :id");
                $stmtCheck->execute(['mapel' => $nama_mapel, 'tingkat' => $tingkat_jurusan, 'id' => $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: /admin/mapel?error=mapel_exist");
                    exit();
                }

                $stmt = $this->db->prepare("UPDATE mata_pelajaran SET kode_mapel = :kode_mapel, kelompok_mapel = :kel_mapel, kelompok = :kelompok, is_sub = :is_sub, urutan = :urutan, tingkat_jurusan = :tingkat, nama_mapel = :nama, guru_id = :guru_id WHERE id = :id");
                $stmt->execute([
                    'id' => $id,
                    'kode_mapel' => $kode_mapel,
                    'kel_mapel' => $kelompok_mapel,
                    'kelompok' => $kelompok,
                    'is_sub' => $is_sub,
                    'urutan' => $urutan,
                    'tingkat' => $tingkat_jurusan,
                    'nama' => $nama_mapel,
                    'guru_id' => empty($guru_id) ? null : $guru_id
                ]);

                header("Location: /admin/mapel?success=update");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/mapel?error=system");
                exit();
            }
        }
    }

    public function deleteMapel()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $stmt = $this->db->prepare("DELETE FROM mata_pelajaran WHERE id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: /admin/mapel?success=delete");
            exit();
        }
    }

    public function deleteSelectedMapel()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_ids'])) {
            $ids = $_POST['selected_ids'];
            // Buat placeholder (?, ?, ?) sesuai jumlah ID yang dipilih
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $this->db->prepare("DELETE FROM mata_pelajaran WHERE id IN ($placeholders)");
            $stmt->execute($ids);

            header("Location: /admin/mapel?success=delete");
            exit();
        }

        // Jika tidak ada yang dicentang tapi tombol tertekan
        header("Location: /admin/mapel");
        exit();
    }

    public function templateMapel()
    {
        SessionHelper::checkLogin();
        require_once __DIR__ . '/../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // =========================================================================
        // SHEET 1: DATA MAPEL (Untuk Diisi)
        // =========================================================================
        $sheetData = $spreadsheet->getActiveSheet();
        $sheetData->setTitle('Data Mapel');

        // 1. Judul Utama
        $sheetData->setCellValue('A1', 'TEMPLATE IMPORT DATA MATA PELAJARAN');
        $sheetData->mergeCells('A1:H1');
        $styleJudul = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheetData->getStyle('A1:H1')->applyFromArray($styleJudul);
        $sheetData->getRowDimension(1)->setRowHeight(30);

        // 2. Header Kolom (Baris 3) - Kolom D ditambah untuk Kode Mapel
        $sheetData->setCellValue('A3', 'Tipe Kelompok');
        $sheetData->setCellValue('B3', 'Urutan Cetak');
        $sheetData->setCellValue('C3', 'Is Sub Mapel');
        $sheetData->setCellValue('D3', 'Kode Mapel');
        $sheetData->setCellValue('E3', 'Ket. Kelompok');
        $sheetData->setCellValue('F3', 'Tingkat & Jurusan');
        $sheetData->setCellValue('G3', 'Nama Mata Pelajaran');
        $sheetData->setCellValue('H3', 'Username Guru');

        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF10B981']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF047857']],
            ],
        ];
        $sheetData->getStyle('A3:H3')->applyFromArray($styleHeader);
        $sheetData->getRowDimension(3)->setRowHeight(25);

        // 3. Contoh Data (Baris 4)
        $sheetData->setCellValue('A4', 'A');
        $sheetData->setCellValue('B4', '1');
        $sheetData->setCellValue('C4', '0');
        $sheetData->setCellValue('D4', 'PAI-X');
        $sheetData->setCellValue('E4', 'Muatan Nasional');
        $sheetData->setCellValue('F4', 'X TJKT');
        $sheetData->setCellValue('G4', 'Pendidikan Agama Islam');
        $sheetData->setCellValue('H4', 'budi_guru');

        // Border untuk contoh data
        $styleData = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
            ],
        ];
        $sheetData->getStyle('A4:H4')->applyFromArray($styleData);
        $sheetData->getStyle('A4:C4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // 4. Validasi Dropdown Tingkat Jurusan (Sekarang Kolom F)
        $stmtTingkat = $this->db->query("SELECT DISTINCT SUBSTRING_INDEX(kelas, ' ', 2) as tingkat_jurusan FROM siswa WHERE kelas IS NOT NULL ORDER BY tingkat_jurusan ASC");
        $listTingkat = $stmtTingkat->fetchAll(PDO::FETCH_COLUMN);
        $tingkatStr = implode(',', $listTingkat);

        if (!empty($tingkatStr)) {
            $validationTingkat = $sheetData->getCell('F4')->getDataValidation();
            $validationTingkat->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validationTingkat->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validationTingkat->setAllowBlank(false);
            $validationTingkat->setShowDropDown(true);
            $validationTingkat->setFormula1('"' . $tingkatStr . '"');
            $validationTingkat->setSqref('F4:F1000');
        }

        // 5. Validasi Dropdown Username Guru (Sekarang Kolom H)
        $stmtGuru = $this->db->query("SELECT username FROM guru ORDER BY username ASC");
        $listUsernames = $stmtGuru->fetchAll(PDO::FETCH_COLUMN);
        $userStr = implode(',', $listUsernames);

        if (!empty($userStr) && strlen($userStr) < 255) {
            $validationGuru = $sheetData->getCell('H4')->getDataValidation();
            $validationGuru->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validationGuru->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validationGuru->setAllowBlank(true);
            $validationGuru->setShowDropDown(true);
            $validationGuru->setFormula1('"' . $userStr . '"');
            $validationGuru->setSqref('H4:H1000');
        }

        // Auto Lebar Kolom
        $sheetData->getColumnDimension('A')->setWidth(18);
        $sheetData->getColumnDimension('B')->setWidth(15);
        $sheetData->getColumnDimension('C')->setWidth(15);
        $sheetData->getColumnDimension('D')->setWidth(20);
        $sheetData->getColumnDimension('E')->setWidth(25);
        $sheetData->getColumnDimension('F')->setWidth(20);
        $sheetData->getColumnDimension('G')->setWidth(40);
        $sheetData->getColumnDimension('H')->setWidth(25);


        // =========================================================================
        // SHEET 2: PETUNJUK PENGISIAN
        // =========================================================================
        $sheetPetunjuk = $spreadsheet->createSheet(1);
        $sheetPetunjuk->setTitle('Petunjuk Pengisian');

        $sheetPetunjuk->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE MATA PELAJARAN');
        $sheetPetunjuk->mergeCells('A1:C1');
        $sheetPetunjuk->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $petunjukData = [
            ['Kolom', 'Penjelasan', 'Contoh Nilai'],
            ['Tipe Kelompok (A)', 'Isi dengan huruf "A" untuk Mapel Umum (Nasional/Kewilayahan), atau "B" untuk Mapel Kejuruan.', 'A / B'],
            ['Urutan Cetak (B)', 'Angka urutan posisi mapel saat dicetak di raport (1, 2, 3, dst).', '1'],
            ['Is Sub Mapel (C)', 'Isi angka "1" jika mapel ini adalah turunan (menggunakan abjad a, b, c). Isi "0" jika mapel utama.', '0 / 1'],
            ['Kode Mapel (D)', 'Kode singkatan mata pelajaran (opsional).', 'PAI-X / MTK-X'],
            ['Ket. Kelompok (E)', 'Opsional. Keterangan kelompok seperti "Muatan Nasional", "Muatan Peminatan", dll.', 'Muatan Nasional'],
            ['Tingkat & Jurusan (F)', 'Pilih tingkat dan jurusan dari dropdown. Harus sama persis dengan format data.', 'X TJKT'],
            ['Nama Mata Pelajaran (G)', 'Tulis nama mata pelajaran secara lengkap.', 'Pendidikan Pancasila'],
            ['Username Guru (H)', 'Pilih username guru yang mengampu mapel ini dari dropdown. Boleh dikosongkan.', 'budi_guru']
        ];

        $rowPetunjuk = 3;
        foreach ($petunjukData as $data) {
            $sheetPetunjuk->setCellValue('A' . $rowPetunjuk, $data[0]);
            $sheetPetunjuk->setCellValue('B' . $rowPetunjuk, $data[1]);
            $sheetPetunjuk->setCellValue('C' . $rowPetunjuk, $data[2]);
            $rowPetunjuk++;
        }

        // Style Petunjuk
        $sheetPetunjuk->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF475569']],
        ]);
        $sheetPetunjuk->getStyle('A3:C11')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheetPetunjuk->getStyle('A3:A11')->getFont()->setBold(true);
        $sheetPetunjuk->getStyle('C4:C11')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheetPetunjuk->getColumnDimension('A')->setWidth(25);
        $sheetPetunjuk->getColumnDimension('B')->setWidth(85);
        $sheetPetunjuk->getColumnDimension('C')->setWidth(20);

        // Aktifkan kembali Sheet 1 (Data Mapel) saat file dibuka
        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Mapel_SMKTN.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function importMapel()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $file = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $sheet = $spreadsheet->getSheet(0);
                $rows = $sheet->toArray();

                for ($i = 3; $i < count($rows); $i++) {
                    $kelompok = trim($rows[$i][0] ?? 'A');
                    $urutan = (int)($rows[$i][1] ?? 99);
                    $is_sub = (int)($rows[$i][2] ?? 0);
                    $kode_mapel = trim($rows[$i][3] ?? '');
                    $kelompok_mapel = trim($rows[$i][4] ?? '');
                    $tingkat = trim($rows[$i][5] ?? '');
                    $nama_mapel = trim($rows[$i][6] ?? '');
                    $username = trim($rows[$i][7] ?? '');

                    if (empty($nama_mapel) || empty($tingkat)) {
                        continue;
                    }

                    $guru_id = null;
                    if (!empty($username)) {
                        $stmtG = $this->db->prepare("SELECT id FROM guru WHERE username = :uname LIMIT 1");
                        $stmtG->execute(['uname' => $username]);
                        $guru = $stmtG->fetch();
                        if ($guru) {
                            $guru_id = $guru['id'];
                        }
                    }

                    $stmtCheck = $this->db->prepare("SELECT id FROM mata_pelajaran WHERE nama_mapel = :nama AND tingkat_jurusan = :tingkat LIMIT 1");
                    $stmtCheck->execute(['nama' => $nama_mapel, 'tingkat' => $tingkat]);
                    $existing = $stmtCheck->fetch();

                    if ($existing) {
                        $stmt = $this->db->prepare("UPDATE mata_pelajaran SET kode_mapel = :kode, kelompok_mapel = :kel_mapel, kelompok = :kelompok, is_sub = :is_sub, urutan = :urutan, guru_id = :gid WHERE id = :id");
                        $stmt->execute([
                            'id' => $existing['id'],
                            'kode' => $kode_mapel,
                            'kel_mapel' => $kelompok_mapel,
                            'kelompok' => $kelompok,
                            'is_sub' => $is_sub,
                            'urutan' => $urutan,
                            'gid' => $guru_id
                        ]);
                    } else {
                        $stmt = $this->db->prepare("INSERT INTO mata_pelajaran (kode_mapel, kelompok_mapel, kelompok, is_sub, urutan, tingkat_jurusan, nama_mapel, guru_id) 
                                                    VALUES (:kode, :kel_mapel, :kelompok, :is_sub, :urutan, :tingkat, :nama, :gid)");
                        $stmt->execute([
                            'kode' => $kode_mapel,
                            'kel_mapel' => $kelompok_mapel,
                            'kelompok' => $kelompok,
                            'is_sub' => $is_sub,
                            'urutan' => $urutan,
                            'tingkat' => $tingkat,
                            'nama' => $nama_mapel,
                            'gid' => $guru_id
                        ]);
                    }
                }
                header("Location: /admin/mapel?success=1");
                exit();
            } catch (\Exception $e) {
                header("Location: /admin/mapel?error=import");
                exit();
            }
        }
    }

    public function pengaturanSekolah()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses Ditolak.");
        }

        // Ambil data sekolah (mengasumsikan hanya ada 1 baris data pengaturan)
        $stmt = $this->db->query("SELECT * FROM sekolah LIMIT 1");
        $sekolah = $stmt->fetch() ?: [];

        $title = "Pengaturan Sekolah - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/admin/sekolah/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function storeSekolah()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses Ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama_sekolah = trim($_POST['nama_sekolah'] ?? '');
            $nama_asesmen = trim($_POST['nama_asesmen'] ?? '');
            $tahun_pelajaran = trim($_POST['tahun_pelajaran'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            $kkm_kktp = trim($_POST['kkm_kktp'] ?? '75');
            $nama_kepala_sekolah = trim($_POST['nama_kepala_sekolah'] ?? '');
            $nrks = trim($_POST['nrks'] ?? '');
            $alamat_sekolah = trim($_POST['alamat_sekolah'] ?? '');

            try {
                // 1. Ambil data sekolah saat ini terlebih dahulu untuk mengetahui path file lama
                $stmtCheck = $this->db->query("SELECT * FROM sekolah LIMIT 1");
                $sekolahLama = $stmtCheck->fetch();
                $exists = $sekolahLama ? true : false;

                // 2. Proses Upload File Tanda Tangan
                $ttdPath = null;
                if (isset($_FILES['tanda_tangan']) && $_FILES['tanda_tangan']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../assets/uploads/ttd_kepsek/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = time() . '_' . basename($_FILES['tanda_tangan']['name']);
                    $targetFilePath = $uploadDir . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

                    // Validasi Ekstensi Gambar
                    if (in_array($fileType, ['jpg', 'png', 'jpeg'])) {
                        if (move_uploaded_file($_FILES['tanda_tangan']['tmp_name'], $targetFilePath)) {
                            $ttdPath = '/assets/uploads/ttd_kepsek/' . $fileName;

                            // FITUR BARU: Hapus file lama jika ada dan berhasil upload yang baru
                            if ($exists && !empty($sekolahLama['tanda_tangan_kepsek'])) {
                                $oldFileFullPath = __DIR__ . '/..' . $sekolahLama['tanda_tangan_kepsek'];
                                if (file_exists($oldFileFullPath)) {
                                    unlink($oldFileFullPath); // Menghapus file fisik dari server
                                }
                            }
                        }
                    } else {
                        header("Location: /admin/sekolah?error=format");
                        exit();
                    }
                }

                // 3. Simpan ke Database
                if ($exists) {
                    // Update Data
                    $sql = "UPDATE sekolah SET nama_sekolah = :ns, nama_asesmen = :na, tahun_pelajaran = :tp, semester = :sm, kkm_kktp = :kkm, nama_kepala_sekolah = :nk, nrks = :nr, alamat_sekolah = :al";
                    $params = [
                        'ns' => $nama_sekolah,
                        'na' => $nama_asesmen,
                        'tp' => $tahun_pelajaran,
                        'sm' => $semester,
                        'kkm' => $kkm_kktp,
                        'nk' => $nama_kepala_sekolah,
                        'nr' => $nrks,
                        'al' => $alamat_sekolah
                    ];

                    if ($ttdPath) {
                        $sql .= ", tanda_tangan_kepsek = :ttd";
                        $params['ttd'] = $ttdPath;
                    }

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                } else {
                    // Insert Data Baru
                    $sql = "INSERT INTO sekolah (nama_sekolah, nama_asesmen, tahun_pelajaran, semester, kkm_kktp, nama_kepala_sekolah, nrks, alamat_sekolah, tanda_tangan_kepsek) VALUES (:ns, :na, :tp, :sm, :kkm, :nk, :nr, :al, :ttd)";
                    $params = [
                        'ns' => $nama_sekolah,
                        'na' => $nama_asesmen,
                        'tp' => $tahun_pelajaran,
                        'sm' => $semester,
                        'kkm' => $kkm_kktp,
                        'nk' => $nama_kepala_sekolah,
                        'nr' => $nrks,
                        'al' => $alamat_sekolah,
                        'ttd' => $ttdPath
                    ];

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                }

                header("Location: /admin/sekolah?success=1");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/sekolah?error=system");
                exit();
            }
        }
    }

    public function templateGuru()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Data Guru');

        // Menulis Baris Header Kolom
        $sheet->setCellValue('A1', 'NIP');
        $sheet->setCellValue('B1', 'Nama Guru');
        $sheet->setCellValue('C1', 'Username');
        $sheet->setCellValue('D1', 'Password');

        // Menulis Contoh Data / Panduan Pengisian di Baris ke-2
        $sheet->setCellValue('A2', '198501012010011001');
        $sheet->setCellValue('B2', 'Contoh: Budi Santoso, S.Pd.');
        $sheet->setCellValue('C2', 'budi_guru');
        $sheet->setCellValue('D2', 'rahasia123');

        // Mengatur agar header tebal (bold)
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        // Auto-size kolom agar rapi
        foreach (range('A', 'D') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Header HTTP untuk proses download file Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="template_import_guru_smk_tiara_nusa.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function storeGuru()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nip = !empty(trim($_POST['nip'] ?? '')) ? trim($_POST['nip']) : null;
            $nama_guru = trim($_POST['nama_guru'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);

            try {
                // Cek apakah username sudah ada di tabel users / guru
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmtCheck->execute(['username' => $username]);
                if ($stmtCheck->fetchColumn() > 0) {
                    // Alihkan dengan error khusus username duplikat
                    header("Location: /admin/guru?error=username_exist");
                    exit();
                }

                // 1. Simpan ke tabel guru (NIP dibiarkan boleh sama)
                $stmtGuru = $this->db->prepare("INSERT INTO guru (nip, nama_guru, username, password) VALUES (:nip, :nama_guru, :username, :password)");
                $stmtGuru->execute([
                    'nip' => $nip,
                    'nama_guru' => $nama_guru,
                    'username' => $username,
                    'password' => $password
                ]);
                $guruId = $this->db->lastInsertId();

                // 2. Simpan ke tabel users
                $stmtUser = $this->db->prepare("INSERT INTO users (guru_id, username, password, nama, role, is_walikelas) VALUES (:guru_id, :username, :password, :nama, 'guru', 0)");
                $stmtUser->execute([
                    'guru_id' => $guruId,
                    'username' => $username,
                    'password' => $password,
                    'nama' => $nama_guru
                ]);

                header("Location: /admin/guru?success=1");
                exit();
            } catch (PDOException $e) {
                echo "Terjadi kesalahan database: " . $e->getMessage();
                exit();
            }
        }
    }

    public function updateGuru()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $nip = !empty(trim($_POST['nip'] ?? '')) ? trim($_POST['nip']) : null;
            $nama_guru = trim($_POST['nama_guru'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            try {
                // Cek duplikasi username jika diubah ke username milik orang lain
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND guru_id != :id");
                $stmtCheck->execute(['username' => $username, 'id' => $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: /admin/guru?error=username_exist");
                    exit();
                }

                // Update tabel guru
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("UPDATE guru SET nip = :nip, nama_guru = :nama_guru, username = :username, password = :password WHERE id = :id");
                    $stmt->execute(['nip' => $nip, 'nama_guru' => $nama_guru, 'username' => $username, 'password' => $hashedPassword, 'id' => $id]);

                    $stmtUser = $this->db->prepare("UPDATE users SET username = :username, password = :password, nama = :nama WHERE guru_id = :id");
                    $stmtUser->execute(['username' => $username, 'password' => $hashedPassword, 'nama' => $nama_guru, 'id' => $id]);
                } else {
                    $stmt = $this->db->prepare("UPDATE guru SET nip = :nip, nama_guru = :nama_guru, username = :username WHERE id = :id");
                    $stmt->execute(['nip' => $nip, 'nama_guru' => $nama_guru, 'username' => $username, 'id' => $id]);

                    $stmtUser = $this->db->prepare("UPDATE users SET username = :username, nama = :nama WHERE guru_id = :id");
                    $stmtUser->execute(['username' => $username, 'nama' => $nama_guru, 'id' => $id]);
                }

                header("Location: /admin/guru?success=1");
                exit();
            } catch (PDOException $e) {
                echo "Terjadi kesalahan database: " . $e->getMessage();
                exit();
            }
        }
    }

    public function deleteGuru()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';

            if (!empty($id)) {
                // 1. Hapus akun user yang berelasi dengan guru_id ini terlebih dahulu
                $stmtUser = $this->db->prepare("DELETE FROM users WHERE guru_id = :id");
                $stmtUser->execute(['id' => $id]);

                // 2. Hapus data guru dari tabel guru
                $stmt = $this->db->prepare("DELETE FROM guru WHERE id = :id");
                $stmt->execute(['id' => $id]);
            }

            header("Location: /admin/guru?success=1");
            exit();
        }
    }

    public function importGuru()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            require_once __DIR__ . '/../vendor/autoload.php';

            $file = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                // Mulai iterasi dari baris ke-2 (mengabaikan header baris pertama)
                for ($i = 1; $i < count($rows); $i++) {
                    $nip = !empty(trim($rows[$i][0] ?? '')) ? trim($rows[$i][0]) : null;
                    $nama_guru = trim($rows[$i][1] ?? '');
                    $username = trim($rows[$i][2] ?? '');
                    $passwordRaw = trim($rows[$i][3] ?? '123456');

                    // Validasi data kosong
                    if (empty($nama_guru) || empty($username)) {
                        continue;
                    }

                    // Cek apakah username sudah terdaftar, jika sudah maka lewati
                    $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                    $stmtCheck->execute(['username' => $username]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        continue;
                    }

                    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

                    // Simpan ke tabel guru
                    $stmtGuru = $this->db->prepare("INSERT INTO guru (nip, nama_guru, username, password) VALUES (:nip, :nama_guru, :username, :password)");
                    $stmtGuru->execute([
                        'nip' => $nip,
                        'nama_guru' => $nama_guru,
                        'username' => $username,
                        'password' => $password
                    ]);
                    $guruId = $this->db->lastInsertId();

                    // Simpan ke tabel users
                    $stmtUser = $this->db->prepare("INSERT INTO users (guru_id, username, password, nama, role, is_walikelas) VALUES (:guru_id, :username, :password, :nama, 'guru', 0)");
                    $stmtUser->execute([
                        'guru_id' => $guruId,
                        'username' => $username,
                        'password' => $password,
                        'nama' => $nama_guru
                    ]);
                }

                header("Location: /admin/guru?success=1");
                exit();
            } catch (\Exception $e) {
                echo "Gagal memproses berkas Excel: " . $e->getMessage();
                exit();
            }
        }
    }

    public function siswaIndex()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        $search = trim($_GET['search'] ?? '');
        $kelasFilter = trim($_GET['kelas'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Ambil daftar kelas unik untuk dropdown filter
        $stmtKelas = $this->db->query("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
        $listKelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);

        // Query Dasar & Kondisi Filter/Search
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (nama_siswa LIKE :search OR nis LIKE :search OR nisn LIKE :search)";
            $params['search'] = "%$search%";
        }

        if (!empty($kelasFilter)) {
            $where .= " AND kelas = :kelas";
            $params['kelas'] = $kelasFilter;
        }

        // Hitung total data untuk pagination
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM siswa $where");
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = ceil($totalData / $limit);

        // Ambil data siswa dengan limit dan offset
        $query = "SELECT * FROM siswa $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $siswas = $stmt->fetchAll();

        $title = "Data Siswa - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/admin/siswa/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function storeSiswa()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nis = trim($_POST['nis'] ?? '');
            $nisn = trim($_POST['nisn'] ?? '');
            $nama_siswa = trim($_POST['nama_siswa'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');
            $fase = trim($_POST['fase'] ?? '');

            // 1. Cek Spesifik Duplikasi NIS
            $stmtCheckNis = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE nis = :nis");
            $stmtCheckNis->execute(['nis' => $nis]);
            if ($stmtCheckNis->fetchColumn() > 0) {
                header("Location: /admin/siswa?error=nis_exist");
                exit();
            }

            // 2. Cek Spesifik Duplikasi NISN
            $stmtCheckNisn = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = :nisn");
            $stmtCheckNisn->execute(['nisn' => $nisn]);
            if ($stmtCheckNisn->fetchColumn() > 0) {
                header("Location: /admin/siswa?error=nisn_exist");
                exit();
            }

            try {
                $stmt = $this->db->prepare("INSERT INTO siswa (nis, nisn, nama_siswa, kelas, fase) VALUES (:nis, :nisn, :nama_siswa, :kelas, :fase)");
                $stmt->execute([
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'nama_siswa' => $nama_siswa,
                    'kelas' => $kelas,
                    'fase' => $fase
                ]);
                header("Location: /admin/siswa?success=1");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/siswa?error=system");
                exit();
            }
        }
    }

    public function updateSiswa()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $nis = trim($_POST['nis'] ?? '');
            $nisn = trim($_POST['nisn'] ?? '');
            $nama_siswa = trim($_POST['nama_siswa'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');
            $fase = trim($_POST['fase'] ?? '');

            // 1. Cek Duplikasi NIS (Kecuali ID siswa ini sendiri)
            $stmtCheckNis = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE nis = :nis AND id != :id");
            $stmtCheckNis->execute(['nis' => $nis, 'id' => $id]);
            if ($stmtCheckNis->fetchColumn() > 0) {
                header("Location: /admin/siswa?error=nis_exist");
                exit();
            }

            // 2. Cek Duplikasi NISN (Kecuali ID siswa ini sendiri)
            $stmtCheckNisn = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = :nisn AND id != :id");
            $stmtCheckNisn->execute(['nisn' => $nisn, 'id' => $id]);
            if ($stmtCheckNisn->fetchColumn() > 0) {
                header("Location: /admin/siswa?error=nisn_exist");
                exit();
            }

            try {
                $stmt = $this->db->prepare("UPDATE siswa SET nis = :nis, nisn = :nisn, nama_siswa = :nama_siswa, kelas = :kelas, fase = :fase WHERE id = :id");
                $stmt->execute([
                    'id' => $id,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'nama_siswa' => $nama_siswa,
                    'kelas' => $kelas,
                    'fase' => $fase
                ]);
                header("Location: /admin/siswa?success=update");
                exit();
            } catch (PDOException $e) {
                header("Location: /admin/siswa?error=system");
                exit();
            }
        }
    }

    public function importSiswa()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $file = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                for ($i = 1; $i < count($rows); $i++) {
                    $nis = trim($rows[$i][0] ?? '');
                    $nisn = trim($rows[$i][1] ?? '');
                    $nama_siswa = trim($rows[$i][2] ?? '');
                    $kelas = trim($rows[$i][3] ?? '');
                    $fase = trim($rows[$i][4] ?? '');

                    if (empty($nis) || empty($nama_siswa)) {
                        continue;
                    }

                    // Perhatikan: Menghapus semester dari query ON DUPLICATE KEY
                    $stmt = $this->db->prepare("INSERT INTO siswa (nis, nisn, nama_siswa, kelas, fase) VALUES (:nis, :nisn, :nama_siswa, :kelas, :fase) ON DUPLICATE KEY UPDATE nama_siswa=:nama_siswa, kelas=:kelas, fase=:fase");
                    $stmt->execute([
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'nama_siswa' => $nama_siswa,
                        'kelas' => $kelas,
                        'fase' => $fase
                    ]);
                }
                header("Location: /admin/siswa?success=1");
                exit();
            } catch (\Exception $e) {
                // Bisa menggunakan session error / log untuk debug pesan detail
                header("Location: /admin/siswa?error=import");
                exit();
            }
        }
    }

    public function deleteSiswa()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $stmt = $this->db->prepare("DELETE FROM siswa WHERE id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: /admin/siswa?success=delete");
            exit();
        }
    }

    public function deleteSelectedSiswa()
    {
        SessionHelper::checkLogin();
        if (!SessionHelper::hasRole('admin')) {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_ids'])) {
            $ids = $_POST['selected_ids'];
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("DELETE FROM siswa WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            header("Location: /admin/siswa?success=delete");
            exit();
        }
        header("Location: /admin/siswa");
        exit();
    }

    public function templateSiswa()
    {
        SessionHelper::checkLogin();
        require_once __DIR__ . '/../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Siswa');

        // Header Kolom
        $sheet->setCellValue('A1', 'NIS');
        $sheet->setCellValue('B1', 'NISN');
        $sheet->setCellValue('C1', 'Nama Lengkap Siswa');
        $sheet->setCellValue('D1', 'Kelas');
        $sheet->setCellValue('E1', 'Fase');

        // Contoh Baris Data
        $sheet->setCellValue('A2', '1001');
        $sheet->setCellValue('B2', '0081234567');
        $sheet->setCellValue('C2', 'Ahmad Fauzi');
        $sheet->setCellValue('D2', 'X TKJ 1');
        $sheet->setCellValue('E2', 'E');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Data_Siswa_SMKTN.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }
}
