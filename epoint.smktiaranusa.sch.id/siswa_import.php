<?php
require_once 'config.php';
require_once 'includes/auth.php';
hanyaAdmin();

if (!function_exists('rrmdir')) {
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== "." && $object !== "..") {
                    $sub = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_dir($sub) && !is_link($sub)) {
                        rrmdir($sub);
                    } else {
                        @unlink($sub);
                    }
                }
            }
            @rmdir($dir);
        }
    }
}

// Download template Excel (.xlsx) atau CSV jika diminta
if (isset($_GET['download_template'])) {
    $fmt = strtolower($_GET['download_template']);
    if ($fmt === 'xlsx' || $fmt === '1' || $fmt === 'excel') {
        $file = __DIR__ . '/TEMPLATE_IMPORT_SISWA.xlsx';
        if (file_exists($file)) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="TEMPLATE_IMPORT_SISWA_EPOINT.xlsx"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }

    // Default template CSV
    $fileCsv = __DIR__ . '/TEMPLATE_IMPORT_SISWA.csv';
    if (file_exists($fileCsv)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="TEMPLATE_IMPORT_SISWA_EPOINT.csv"');
        header('Content-Length: ' . filesize($fileCsv));
        readfile($fileCsv);
        exit;
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=template_import_siswa_epoint.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin (L/P)', 'Kelas', 'Jurusan (MPLB/TJKT)', 'Nama Wali Kelas', 'No HP Ortu']);
        fputcsv($out, ['260501', '0103860501', 'Contoh Siswa Laki-Laki', 'L', 'X TJKT 1', 'TJKT', 'Nunu Gunawan, S.Kom., Gr', '081234567890']);
        fputcsv($out, ['260502', '0111252702', 'Contoh Siswi Perempuan', 'P', 'X MPLB 1', 'MPLB', 'Yulies Andi Santoso, S.Pd', '081298765432']);
        fclose($out);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirect('siswa.php', 'Gagal mengunggah file. Silakan coba kembali.', 'danger');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'csv'])) {
        redirect('siswa.php', 'Format file harus berupa .xlsx (Excel) atau .csv', 'danger');
    }

    $importedCount = 0;
    $updatedCount = 0;

    if ($ext === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        // Lewati BOM UTF-8 jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 2000, ',');
        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
            if (empty(array_filter($row))) continue;
            $nis = clean($koneksi, $row[0] ?? '');
            $nisn = clean($koneksi, $row[1] ?? '');
            $nama = clean($koneksi, $row[2] ?? '');
            $jkRaw = strtoupper(clean($koneksi, $row[3] ?? 'L'));
            $jk = in_array($jkRaw, ['L', 'P']) ? $jkRaw : ($jkRaw === 'PEREMPUAN' ? 'P' : 'L');
            $namaKelas = clean($koneksi, $row[4] ?? '');
            $jurusan = clean($koneksi, $row[5] ?? 'MPLB');
            $waliKelas = clean($koneksi, $row[6] ?? '');
            $hp = clean($koneksi, $row[7] ?? '');

            if (empty($nama) || empty($namaKelas)) continue;

            if (preg_match('/^10\s+/i', $namaKelas)) $namaKelas = preg_replace('/^10\s+/i', 'X ', $namaKelas);
            elseif (preg_match('/^11\s+/i', $namaKelas)) $namaKelas = preg_replace('/^11\s+/i', 'XI ', $namaKelas);
            elseif (preg_match('/^12\s+/i', $namaKelas)) $namaKelas = preg_replace('/^12\s+/i', 'XII ', $namaKelas);

            // Cari atau buat kelas
            $cekKelas = mysqli_query($koneksi, "SELECT id FROM kelas WHERE nama_kelas='$namaKelas'");
            if ($kRow = mysqli_fetch_assoc($cekKelas)) {
                $kelasId = $kRow['id'];
                if (!empty($waliKelas)) {
                    mysqli_query($koneksi, "UPDATE kelas SET wali_kelas='$waliKelas' WHERE id=$kelasId");
                }
            } else {
                mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas, jurusan, wali_kelas) VALUES ('$namaKelas', '$jurusan', '$waliKelas')");
                $kelasId = mysqli_insert_id($koneksi);
            }

            // Cek apakah NIS sudah ada
            if (empty($nis)) {
                $lastNis = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT MAX(id) m FROM siswa"))['m'] ?? 0;
                $nis = '26' . str_pad($lastNis + 1, 4, '0', STR_PAD_LEFT);
            }

            $cekSiswa = false;
            if (!empty($nisn)) {
                $cekSiswa = mysqli_query($koneksi, "SELECT id FROM siswa WHERE nisn='$nisn'");
            } elseif (!empty($nis)) {
                $cekSiswa = mysqli_query($koneksi, "SELECT id FROM siswa WHERE nis='$nis'");
            }

            if ($cekSiswa && ($sRow = mysqli_fetch_assoc($cekSiswa))) {
                mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', nisn=" . ($nisn?"'$nisn'":"NULL") . ", kelas_id=$kelasId, jenis_kelamin='$jk', no_hp_ortu='$hp' WHERE id={$sRow['id']}");
                $updatedCount++;
            } else {
                mysqli_query($koneksi, "INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin, no_hp_ortu, status) VALUES ('$nis', " . ($nisn?"'$nisn'":"NULL") . ", '$nama', $kelasId, '$jk', '$hp', 'aktif')");
                $importedCount++;
            }
        }
        fclose($handle);
        redirect('siswa.php', "Import CSV selesai: $importedCount siswa baru ditambahkan, $updatedCount diperbarui.");
    } else {
        // XLSX Parsing dengan penanganan murni PHP (Kompatibel Linux & Windows)
        $tempDir = __DIR__ . '/xlsx_upload_temp_' . time() . '_' . mt_rand(1000, 9999);
        @mkdir($tempDir, 0755, true);
        $unzipped = false;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) === true) {
                $zip->extractTo($tempDir);
                $zip->close();
                $unzipped = true;
            }
        }

        if (!$unzipped) {
            // Fallback ekstra: tar -xf (tersedia bawaan di Windows 10/11 dan server Linux)
            @exec('tar -xf ' . escapeshellarg($file['tmp_name']) . ' -C ' . escapeshellarg($tempDir));
            if (file_exists("$tempDir/xl/workbook.xml")) {
                $unzipped = true;
            }
        }

        if (!$unzipped || !file_exists("$tempDir/xl/workbook.xml")) {
            rrmdir($tempDir);
            redirect('siswa.php', 'Gagal membaca format file Excel .xlsx. Pastikan file valid atau gunakan template CSV.', 'danger');
        }

        // Parse shared strings
        $sharedStrings = [];
        if (file_exists("$tempDir/xl/sharedStrings.xml")) {
            $domStr = new DOMDocument();
            $domStr->load("$tempDir/xl/sharedStrings.xml");
            foreach ($domStr->getElementsByTagName('si') as $si) {
                $text = '';
                foreach ($si->getElementsByTagName('t') as $t) $text .= $t->nodeValue;
                $sharedStrings[] = $text;
            }
        }

        $domWb = new DOMDocument();
        $domWb->load("$tempDir/xl/workbook.xml");
        $sheets = $domWb->getElementsByTagName('sheet');

        foreach ($sheets as $idx => $s) {
            $sheetName = trim($s->getAttribute('name'));
            $sheetFile = "$tempDir/xl/worksheets/sheet" . ($idx + 1) . ".xml";
            if (!file_exists($sheetFile)) continue;

            $domSheet = new DOMDocument();
            $domSheet->load($sheetFile);

            $rows = $domSheet->getElementsByTagName('row');
            $sheetRows = [];
            foreach ($rows as $row) {
                $rNum = (int)$row->getAttribute('r');
                $rowCols = [];
                foreach ($row->getElementsByTagName('c') as $c) {
                    $ref = $c->getAttribute('r');
                    preg_match('/^([A-Z]+)/', $ref, $m);
                    $col = $m[1] ?? '';
                    $t = $c->getAttribute('t');
                    $vNode = $c->getElementsByTagName('v')->item(0);
                    $val = $vNode ? $vNode->nodeValue : '';
                    if ($t === 's') $val = $sharedStrings[(int)$val] ?? $val;
                    $rowCols[$col] = trim($val);
                }
                $sheetRows[$rNum] = $rowCols;
            }

            if (empty($sheetRows)) continue;

            // DETEKSI FORMAT: Apakah Tabel Datar (Flat Table / Template Resmi) atau Format Lembar Rombel?
            $isFlatTable = false;
            $headerMap = [];
            foreach (($sheetRows[1] ?? []) as $colLetter => $val) {
                $v = strtoupper(trim($val));
                if (strpos($v, 'NISN') !== false) {
                    $headerMap['nisn'] = $colLetter;
                } elseif (preg_match('/\bNIS\b/', $v) || $v === 'NIS') {
                    $headerMap['nis'] = $colLetter;
                } elseif (strpos($v, 'NAMA') !== false) {
                    $headerMap['nama'] = $colLetter;
                } elseif (strpos($v, 'KELAMIN') !== false || $v === 'JK' || strpos($v, 'L/P') !== false) {
                    $headerMap['jk'] = $colLetter;
                } elseif (strpos($v, 'KELAS') !== false && strpos($v, 'WALI') === false) {
                    $headerMap['kelas'] = $colLetter;
                } elseif (strpos($v, 'JURUSAN') !== false) {
                    $headerMap['jurusan'] = $colLetter;
                } elseif (strpos($v, 'WALI') !== false) {
                    $headerMap['wali'] = $colLetter;
                } elseif (strpos($v, 'HP') !== false || strpos($v, 'WA') !== false || strpos($v, 'ORTU') !== false) {
                    $headerMap['hp'] = $colLetter;
                }
            }

            if (isset($headerMap['nama']) && (isset($headerMap['kelas']) || isset($headerMap['nisn']) || isset($headerMap['nis']))) {
                $isFlatTable = true;
            }

            if ($isFlatTable) {
                // Mode 1: Format Tabel Datar (Satu Baris = Satu Siswa beserta Kelasnya)
                foreach ($sheetRows as $rNum => $cols) {
                    if ($rNum < 2) continue;
                    $nama = clean($koneksi, $cols[$headerMap['nama'] ?? 'D'] ?? '');
                    if (empty($nama) || stripos($nama, 'PETUNJUK') !== false) continue;

                    $nis = clean($koneksi, $cols[$headerMap['nis'] ?? 'B'] ?? '');
                    $nisn = clean($koneksi, $cols[$headerMap['nisn'] ?? 'C'] ?? '');
                    $jkRaw = strtoupper(clean($koneksi, $cols[$headerMap['jk'] ?? 'E'] ?? 'L'));
                    $jk = in_array($jkRaw, ['L', 'P']) ? $jkRaw : ($jkRaw === 'PEREMPUAN' ? 'P' : 'L');
                    $className = clean($koneksi, $cols[$headerMap['kelas'] ?? 'F'] ?? '');
                    $jurusan = clean($koneksi, $cols[$headerMap['jurusan'] ?? 'G'] ?? '');
                    $waliKelas = clean($koneksi, $cols[$headerMap['wali'] ?? 'H'] ?? '');
                    $hp = clean($koneksi, $cols[$headerMap['hp'] ?? 'I'] ?? '');

                    if (empty($className)) continue;

                    if (preg_match('/^10\s+/i', $className)) $className = preg_replace('/^10\s+/i', 'X ', $className);
                    elseif (preg_match('/^11\s+/i', $className)) $className = preg_replace('/^11\s+/i', 'XI ', $className);
                    elseif (preg_match('/^12\s+/i', $className)) $className = preg_replace('/^12\s+/i', 'XII ', $className);

                    if (empty($jurusan)) {
                        $jurusan = (stripos($className, 'MPLB') !== false) ? 'MPLB' : 'TJKT';
                    }

                    // Sinkronisasi Kelas
                    $namaKelasEsc = clean($koneksi, $className);
                    $waliEsc = clean($koneksi, $waliKelas);
                    $jurusanEsc = clean($koneksi, $jurusan);

                    $cekK = mysqli_query($koneksi, "SELECT id FROM kelas WHERE nama_kelas='$namaKelasEsc'");
                    if ($kRow = mysqli_fetch_assoc($cekK)) {
                        $kelasId = $kRow['id'];
                        if (!empty($waliEsc)) {
                            mysqli_query($koneksi, "UPDATE kelas SET wali_kelas='$waliEsc' WHERE id=$kelasId");
                        }
                    } else {
                        mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas, jurusan, wali_kelas) VALUES ('$namaKelasEsc', '$jurusanEsc', '$waliEsc')");
                        $kelasId = mysqli_insert_id($koneksi);
                    }

                    // Cek NIS / NISN
                    $cekS = false;
                    if (!empty($nisn)) {
                        $cekS = mysqli_query($koneksi, "SELECT id FROM siswa WHERE nisn='$nisn'");
                    } elseif (!empty($nis)) {
                        $cekS = mysqli_query($koneksi, "SELECT id FROM siswa WHERE nis='$nis'");
                    }

                    if ($cekS && ($sRow = mysqli_fetch_assoc($cekS))) {
                        mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', kelas_id=$kelasId, jenis_kelamin='$jk', no_hp_ortu='$hp' WHERE id={$sRow['id']}");
                        $updatedCount++;
                    } else {
                        if (empty($nis)) {
                            $nis = '000000';
                        }
                        mysqli_query($koneksi, "INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin, no_hp_ortu, status) VALUES ('$nis', " . ($nisn ? "'$nisn'" : "NULL") . ", '$nama', $kelasId, '$jk', '$hp', 'aktif')");
                        $importedCount++;
                    }
                }
            } else {
                // Mode 2: Format Multi-Sheet per Kelas (seperti file DAFTAR SISWA TA 2026-2027.xlsx)
                $rawClass = $sheetRows[1]['A'] ?? $sheetName;
                $className = trim($rawClass);
                if (preg_match('/^10\s+/i', $className)) $className = preg_replace('/^10\s+/i', 'X ', $className);
                elseif (preg_match('/^11\s+/i', $className)) $className = preg_replace('/^11\s+/i', 'XI ', $className);
                elseif (preg_match('/^12\s+/i', $className)) $className = preg_replace('/^12\s+/i', 'XII ', $className);

                $waliKelas = $sheetRows[2]['C'] ?? ($sheetRows[1]['C'] ?? '');
                $waliKelas = trim(str_ireplace('Wali Kelas :', '', $waliKelas));
                if ($waliKelas === '' && isset($sheetRows[2]['B'])) $waliKelas = trim($sheetRows[2]['B']);

                $jurusan = (stripos($className, 'MPLB') !== false) ? 'MPLB' : 'TJKT';

                // Simpan / update kelas
                $namaKelasEsc = clean($koneksi, $className);
                $waliEsc = clean($koneksi, $waliKelas);
                $jurusanEsc = clean($koneksi, $jurusan);

                $cekK = mysqli_query($koneksi, "SELECT id FROM kelas WHERE nama_kelas='$namaKelasEsc'");
                if ($kRow = mysqli_fetch_assoc($cekK)) {
                    $kelasId = $kRow['id'];
                    if (!empty($waliEsc)) {
                        mysqli_query($koneksi, "UPDATE kelas SET wali_kelas='$waliEsc' WHERE id=$kelasId");
                    }
                } else {
                    mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas, jurusan, wali_kelas) VALUES ('$namaKelasEsc', '$jurusanEsc', '$waliEsc')");
                    $kelasId = mysqli_insert_id($koneksi);
                }

                // Siswa
                foreach ($sheetRows as $rNum => $cols) {
                    if ($rNum < 4) continue;
                    $nama = clean($koneksi, $cols['B'] ?? '');
                    $nisn = clean($koneksi, $cols['C'] ?? '');
                    $jkRaw = strtoupper(clean($koneksi, $cols['D'] ?? 'L'));
                    $jk = in_array($jkRaw, ['L', 'P']) ? $jkRaw : ($jkRaw === 'PEREMPUAN' ? 'P' : 'L');

                    if (empty($nama)) continue;

                    // Cek NISN atau generate NIS
                    $cekS = false;
                    if (!empty($nisn)) {
                        $cekS = mysqli_query($koneksi, "SELECT id FROM siswa WHERE nisn='$nisn'");
                    }
                    
                    if ($cekS && ($sRow = mysqli_fetch_assoc($cekS))) {
                        mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', kelas_id=$kelasId, jenis_kelamin='$jk' WHERE id={$sRow['id']}");
                        $updatedCount++;
                    } else {
                        $nis = '000000';
                        mysqli_query($koneksi, "INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin, status) VALUES ('$nis', " . ($nisn?"'$nisn'":"NULL") . ", '$nama', $kelasId, '$jk', 'aktif')");
                        $importedCount++;
                    }
                }
            }
        }

        rrmdir($tempDir);
        redirect('siswa.php', "Import Excel berhasil: $importedCount siswa baru ditambahkan, $updatedCount diperbarui.");
    }
}

redirect('siswa.php');
