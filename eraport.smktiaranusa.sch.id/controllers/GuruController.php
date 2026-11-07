<?php
require_once __DIR__ . '/../models/NilaiModel.php';
require_once __DIR__ . '/../models/MapelModel.php';

class GuruController
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

    public function dashboard()
    {
        SessionHelper::checkLogin();
        if ($_SESSION['role'] !== 'guru') {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();

        $stmtCekWali = $this->db->prepare("SELECT kelas FROM wali_kelas WHERE guru_id = :guru_id LIMIT 1");
        $stmtCekWali->execute(['guru_id' => $guruId]);
        $dataWali = $stmtCekWali->fetch(PDO::FETCH_ASSOC);

        if ($dataWali) {
            $_SESSION['is_walikelas'] = true;
            $kelasBinaan = $dataWali['kelas'];

            $stmtSiswa = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas = :kelas");
            $stmtSiswa->execute(['kelas' => $kelasBinaan]);
            $totalSiswaKelas = $stmtSiswa->fetchColumn();

            $title = "Dashboard Wali Kelas - e-Raport SMK Tiara Nusa";
            ob_start();
            require_once __DIR__ . '/../views/walikelas/dashboard.php';
            $content = ob_get_clean();
            require_once __DIR__ . '/../views/layouts/main.php';
        } else {
            $_SESSION['is_walikelas'] = false;

            $stmtMapel = $this->db->prepare("
                SELECT m.*, 
                (SELECT COUNT(*) FROM siswa s WHERE s.kelas LIKE CONCAT(m.tingkat_jurusan, '%')) as total_siswa,
                (SELECT COUNT(*) FROM nilai n WHERE n.mapel_id = m.id AND n.nilai_akhir > 0) as sudah_diisi
                FROM mata_pelajaran m 
                WHERE m.guru_id = :guru_id
                ORDER BY m.nama_mapel ASC
            ");
            $stmtMapel->execute(['guru_id' => $guruId]);
            $listMapelDiampu = $stmtMapel->fetchAll();

            $title = "Dashboard Guru - e-Raport SMK Tiara Nusa";
            ob_start();
            require_once __DIR__ . '/../views/guru/dashboard.php';
            $content = ob_get_clean();
            require_once __DIR__ . '/../views/layouts/main.php';
        }
    }

    public function nilaiIndex()
    {
        SessionHelper::checkLogin();
        if ($_SESSION['role'] !== 'guru') {
            die("Akses ditolak.");
        }

        $guruId = $this->getGuruId();
        $selectedMapelId = $_GET['mapel_id'] ?? '';
        $selectedKelas = $_GET['kelas'] ?? '';

        $stmtSekolah = $this->db->query("SELECT kkm_kktp FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();
        $kkm_kktp = $sekolah['kkm_kktp'] ?? 75;

        $stmtMapel = $this->db->prepare("SELECT * FROM mata_pelajaran WHERE guru_id = :guru_id ORDER BY nama_mapel ASC");
        $stmtMapel->execute(['guru_id' => $guruId]);
        $listMapelDiampu = $stmtMapel->fetchAll();

        $listKelas = [];
        $listSiswa = [];

        if (!empty($selectedMapelId)) {
            $stmtM = $this->db->prepare("SELECT * FROM mata_pelajaran WHERE id = :id AND guru_id = :guru_id LIMIT 1");
            $stmtM->execute(['id' => $selectedMapelId, 'guru_id' => $guruId]);
            $mapelInfo = $stmtM->fetch();

            if ($mapelInfo) {
                $tingkatJurusan = $mapelInfo['tingkat_jurusan'];

                $stmtK = $this->db->prepare("SELECT DISTINCT kelas FROM siswa WHERE kelas LIKE :tingkat ORDER BY kelas ASC");
                $stmtK->execute(['tingkat' => "$tingkatJurusan%"]);
                $listKelas = $stmtK->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($selectedKelas) && in_array($selectedKelas, $listKelas)) {
                    $querySiswa = "SELECT s.*, 
                                          n.id as nilai_id, 
                                          n.nilai_akhir
                                   FROM siswa s 
                                   LEFT JOIN nilai n ON s.id = n.siswa_id AND n.mapel_id = :mapel_id
                                   WHERE s.kelas = :kelas 
                                   ORDER BY s.nama_siswa ASC";
                    $stmtS = $this->db->prepare($querySiswa);
                    $stmtS->execute([
                        'mapel_id' => $selectedMapelId,
                        'kelas' => $selectedKelas
                    ]);
                    $listSiswa = $stmtS->fetchAll();
                }
            }
        }

        $title = "Input Nilai Mata Pelajaran - e-Raport SMK Tiara Nusa";
        ob_start();
        require_once __DIR__ . '/../views/guru/nilai/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function storeNilai()
    {
        SessionHelper::checkLogin();
        if ($_SESSION['role'] !== 'guru') {
            die("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mapel_id = $_POST['mapel_id'] ?? '';
            $kelas = $_POST['kelas'] ?? '';
            $siswa_ids = $_POST['siswa_id'] ?? [];
            $nilai_akhir_arr = $_POST['nilai_akhir'] ?? [];

            try {
                foreach ($siswa_ids as $siswa_id) {
                    $nilaiInput = $nilai_akhir_arr[$siswa_id] ?? '';
                    
                    // Jika field kosong (tidak diisi), abaikan/jangan disave agar tetap kosong
                    if ($nilaiInput === '') {
                        continue;
                    }

                    $nak = (int)$nilaiInput;
                    $nak = max(0, min(100, $nak)); // Batasi 0 - 100

                    $stmtCek = $this->db->prepare("SELECT id FROM nilai WHERE siswa_id = :sid AND mapel_id = :mid LIMIT 1");
                    $stmtCek->execute(['sid' => $siswa_id, 'mid' => $mapel_id]);
                    $exist = $stmtCek->fetch();

                    if ($exist) {
                        $stmtUp = $this->db->prepare("UPDATE nilai SET nilai_akhir = :nak WHERE id = :id");
                        $stmtUp->execute(['nak' => $nak, 'id' => $exist['id']]);
                    } else {
                        $stmtIn = $this->db->prepare("INSERT INTO nilai (siswa_id, mapel_id, nilai_akhir) VALUES (:sid, :mid, :nak)");
                        $stmtIn->execute(['sid' => $siswa_id, 'mid' => $mapel_id, 'nak' => $nak]);
                    }
                }

                header("Location: /guru/nilai?mapel_id=" . urlencode($mapel_id) . "&kelas=" . urlencode($kelas) . "&success=1");
                exit();
            } catch (\Exception $e) {
                header("Location: /guru/nilai?mapel_id=" . urlencode($mapel_id) . "&kelas=" . urlencode($kelas) . "&error=1");
                exit();
            }
        }
    }

public function templateNilai()
    {
        SessionHelper::checkLogin();
        $guruId = $this->getGuruId();

        require_once __DIR__ . '/../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $stmtGuru = $this->db->prepare("SELECT nama_guru FROM guru WHERE id = :id LIMIT 1");
        $stmtGuru->execute(['id' => $guruId]);
        $guruInfo = $stmtGuru->fetch();
        $namaGuru = $guruInfo['nama_guru'] ?? 'Guru Pengampu';

        $stmtSekolah = $this->db->query("SELECT kkm_kktp FROM sekolah LIMIT 1");
        $sekolah = $stmtSekolah->fetch();
        $kkm = $sekolah['kkm_kktp'] ?? 75;

        // Ditambahkan pengambilan kode_mapel
        $stmtMapel = $this->db->prepare("SELECT id, kode_mapel, nama_mapel, tingkat_jurusan FROM mata_pelajaran WHERE guru_id = :guru_id ORDER BY nama_mapel ASC");
        $stmtMapel->execute(['guru_id' => $guruId]);
        $listMapel = $stmtMapel->fetchAll();

        if (empty($listMapel)) {
            die("Anda belum memiliki mata pelajaran yang diampu.");
        }

        $sheetIndex = 0;
        foreach ($listMapel as $mapel) {
            $tingkatJurusan = $mapel['tingkat_jurusan'];
            
            // Ambil daftar kelas yang sesuai dengan tingkat/jurusan mapel ini
            $stmtKelas = $this->db->prepare("SELECT DISTINCT kelas FROM siswa WHERE kelas LIKE :tingkat ORDER BY kelas ASC");
            $stmtKelas->execute(['tingkat' => "$tingkatJurusan%"]);
            $daftarKelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);
            
            // Skip mapel jika belum ada satupun kelas di tingkat tersebut
            if (empty($daftarKelas)) {
                continue; 
            }
            
            // Generate Sheet untuk setiap Kelas dari Mapel ini
            foreach ($daftarKelas as $kelas) {
                $sheet = $spreadsheet->createSheet($sheetIndex);
                
                // Gunakan kode_mapel jika ada, jika tidak fallback ke nama_mapel
                $kodeAtauNama = !empty($mapel['kode_mapel']) ? $mapel['kode_mapel'] : $mapel['nama_mapel'];
                
                // Judul Sheet: (Contoh: "ASJ - XI TJKT 1")
                $sheetTitle = substr(preg_replace('/[\\\\\/\?\*\[\]\:]/', '', $kodeAtauNama . ' - ' . $kelas), 0, 31);
                $sheet->setTitle($sheetTitle);

                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword('SmkTiaraNusa2026');

                $sheet->setCellValue('B1', 'FORMULIR PENILAIAN PESERTA DIDIK');
                $sheet->mergeCells('B1:G1');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);

                $sheet->setCellValue('B3', 'Nama Guru');
                $sheet->setCellValue('D3', ': ' . $namaGuru);
                $sheet->mergeCells('B3:C3');
                $sheet->mergeCells('D3:G3');

                $sheet->setCellValue('B4', 'Mata Pelajaran');
                $sheet->setCellValue('D4', ': ' . $kodeAtauNama);
                $sheet->mergeCells('B4:C4');
                $sheet->mergeCells('D4:G4');

                $sheet->setCellValue('B5', 'Kelas'); // Mengubah tingkat_jurusan menjadi nama spesifik Kelas
                $sheet->setCellValue('D5', ': ' . $kelas);
                $sheet->mergeCells('B5:C5');
                $sheet->mergeCells('D5:G5');

                $sheet->setCellValue('B6', 'KKM / KKTP');
                $sheet->setCellValue('D6', ': ' . $kkm);
                $sheet->mergeCells('B6:C6');
                $sheet->mergeCells('D6:G6');

                $sheet->getStyle('B3:B6')->getFont()->setBold(true);

                $sheet->setCellValue('B8', 'No');
                $sheet->setCellValue('C8', 'NISN');
                $sheet->setCellValue('D8', 'NIS');
                $sheet->setCellValue('E8', 'Nama Siswa');
                $sheet->setCellValue('F8', 'Kelas');
                $sheet->setCellValue('G8', 'Nilai Akhir (0-100)');
                $sheet->setCellValue('H8', 'ID'); 

                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ];
                $sheet->getStyle('B8:G8')->applyFromArray($headerStyle);
                $sheet->getRowDimension(8)->setRowHeight(25);

                // Ambil daftar siswa spesifik HANYA untuk kelas ini
                $stmtS = $this->db->prepare("SELECT id, nisn, nis, nama_siswa, kelas FROM siswa WHERE kelas = :kelas ORDER BY nama_siswa ASC");
                $stmtS->execute(['kelas' => $kelas]);
                $students = $stmtS->fetchAll();

                $row = 9;
                $no = 1;
                foreach ($students as $s) {
                    $stmtNilaiCek = $this->db->prepare("SELECT nilai_akhir FROM nilai WHERE siswa_id = :sid AND mapel_id = :mid LIMIT 1");
                    $stmtNilaiCek->execute(['sid' => $s['id'], 'mid' => $mapel['id']]);
                    $cekNilai = $stmtNilaiCek->fetch();
                    $nilaiExist = $cekNilai ? $cekNilai['nilai_akhir'] : '';

                    $sheet->setCellValue("B$row", $no++);
                    $sheet->setCellValue("C$row", $s['nisn']);
                    $sheet->setCellValue("D$row", $s['nis']);
                    $sheet->setCellValue("E$row", $s['nama_siswa']);
                    $sheet->setCellValue("F$row", $s['kelas']);
                    $sheet->setCellValue("G$row", $nilaiExist);
                    $sheet->setCellValue("H$row", $s['id']);

                    $sheet->getStyle("B$row:F$row")->getFont()->getColor()->setRGB('4B5563');

                    $sheet->getStyle("G$row")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
                    $sheet->getStyle("G$row")->getFont()->setBold(true)->getColor()->setRGB('111827');
                    $sheet->getStyle("G$row")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $row++;
                }

                $lastRow = max(9, $row - 1);

                $tableBorder = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB']
                        ]
                    ],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
                ];
                $sheet->getStyle("B9:G$lastRow")->applyFromArray($tableBorder);

                $sheet->getStyle("B9:B$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C9:D$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F9:F$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $sheet->getColumnDimension('B')->setWidth(8);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(32);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setVisible(false);
                
                $sheetIndex++;
            }
        }
        
        if ($sheetIndex === 0) {
            die("Belum ada data siswa/kelas yang terhubung dengan mata pelajaran Anda.");
        }

        $spreadsheet->setActiveSheetIndex(0);

        $namaFileClean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaGuru);
        $filename = "Template_Nilai_" . $namaFileClean . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    public function importNilai()
    {
        SessionHelper::checkLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $file = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

                // Tarik mapel guru untuk validasi pencocokan sheet
                $guruId = $this->getGuruId();
                $stmtMapel = $this->db->prepare("SELECT id, kode_mapel, nama_mapel, tingkat_jurusan FROM mata_pelajaran WHERE guru_id = :guru_id");
                $stmtMapel->execute(['guru_id' => $guruId]);
                $listMapel = $stmtMapel->fetchAll();

                // Lakukan scan di seluruh Sheets dalam Excel
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $sheetTitle = $sheet->getTitle();
                    $rows = $sheet->toArray();

                    $mapel_id = null;
                    
                    // Verifikasi Mapel apa yang sesuai dengan nama sheet yang di-import
                    foreach ($listMapel as $m) {
                        $tingkatJurusan = $m['tingkat_jurusan'];
                        $stmtKelas = $this->db->prepare("SELECT DISTINCT kelas FROM siswa WHERE kelas LIKE :tingkat");
                        $stmtKelas->execute(['tingkat' => "$tingkatJurusan%"]);
                        $daftarKelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($daftarKelas as $kls) {
                            $kodeAtauNama = !empty($m['kode_mapel']) ? $m['kode_mapel'] : $m['nama_mapel'];
                            $expectedTitle = substr(preg_replace('/[\\\\\/\?\*\[\]\:]/', '', $kodeAtauNama . ' - ' . $kls), 0, 31);
                            
                            // Jika format Mapel - Kelas cocok dengan nama Sheet, kunci mapel_id nya
                            if ($sheetTitle === $expectedTitle) {
                                $mapel_id = $m['id'];
                                break 2;
                            }
                        }
                    }

                    if (!$mapel_id) continue; // Skip jika sheet ini tidak teridentifikasi mapel nya

                    // Looping data siswa di row 8+
                    for ($i = 8; $i < count($rows); $i++) {
                        $siswa_id = trim($rows[$i][7] ?? ''); 
                        $nak = trim($rows[$i][6] ?? '');  

                        if (empty($siswa_id) || $nak === '') continue;

                        $nak = max(0, min(100, (int)$nak));

                        $stmtCek = $this->db->prepare("SELECT id FROM nilai WHERE siswa_id = :sid AND mapel_id = :mid LIMIT 1");
                        $stmtCek->execute(['sid' => $siswa_id, 'mid' => $mapel_id]);
                        $exist = $stmtCek->fetch();

                        if ($exist) {
                            $stmtUp = $this->db->prepare("UPDATE nilai SET nilai_akhir = :nak WHERE id = :id");
                            $stmtUp->execute(['nak' => $nak, 'id' => $exist['id']]);
                        } else {
                            $stmtIn = $this->db->prepare("INSERT INTO nilai (siswa_id, mapel_id, nilai_akhir) VALUES (:siswa_id, :mapel_id, :nak)");
                            $stmtIn->execute(['siswa_id' => $siswa_id, 'mapel_id' => $mapel_id, 'nak' => $nak]);
                        }
                    }
                }

                header("Location: /guru/nilai?success=1");
                exit();
            } catch (\Exception $e) {
                header("Location: /guru/nilai?error=1");
                exit();
            }
        }
    }}