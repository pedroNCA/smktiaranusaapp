<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Raport - <?= htmlspecialchars($siswa['nama_siswa'] ?? '') ?></title>
    <style>
        @page {
            size: 215mm 330mm;
            /* Kertas F4 / Folio Standar */
            margin: 10mm 15mm;
            /* Margin atas-bawah 10mm, kiri-kanan 15mm */
        }

        body {
            font-family: 'Calibri', Arial, sans-serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.15;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        /* Kontainer Utama - Dibuat Relative dengan Tinggi Pasti 1 Halaman */
        .raport-container {
            width: 100%;
            height: 305mm;
            /* Tinggi statis 1 halaman F4 dipotong margin */
            position: relative;
            box-sizing: border-box;
        }

        .raport-body {
            width: 100%;
        }

        .judul {
            font-family: 'Cambria', Times, serif;
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 10.5pt;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* Tabel Utama */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10.5pt;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 3px 5px;
        }

        .content-table th {
            text-align: center;
            background-color: #d9d9d9; /* Warna abu-abu tipis untuk Header Kolom */
            font-weight: bold;
        }

        .section-header {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
            padding-right: 8px; /* Memberikan sedikit ruang dari garis kanan kolom */
        }

        /* Tabel Absensi */
        .attendance-table {
            width: 45%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-top: 5px;
            float: left;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 3px 5px;
        }

        /* Bagian Tanda Tangan - Dikunci Mentok di Bawah */
        .signature-section {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            font-size: 10.5pt;
        }

        .ortu-container {
            float: left;
            width: 200px;
            text-align: center;
            padding-left: 20px;
        }

        .walikelas-container {
            float: right;
            width: 250px;
            text-align: center;
        }

        .kepsek-container {
            width: 100%;
            clear: both;
            text-align: center;
            margin-top: 5px;
        }

        .kepsek-img,
        .walikelas-img {
            height: 50px;
            margin: 2px auto;
            display: block;
        }

        .spacer-ttd {
            height: 75px;
            /* Ruang kosong jika tidak ada ttd */
        }
    </style>
</head>

<body onload="window.print()">

    <div class="raport-container">
        <div class="raport-body">
            <!-- Judul Raport -->
            <div class="judul">
                <?php
                $rawJudul = $sekolah['nama_asesmen'] ?? 'Asesmen Sumatif Tengah Semester';
                $judulRaport = (stripos($rawJudul, 'Laporan') === false) ? 'Laporan Hasil ' . $rawJudul : $rawJudul;
                echo htmlspecialchars($judulRaport);
                ?>
            </div>

            <!-- Informasi Identitas -->
            <table class="header-table">
                <tr>
                    <td style="width: 15%;">Nama Sekolah</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 43%; font-weight: bold;"><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'SMK TIARA NUSA') ?></td>
                    <td style="width: 15%;">Kelas</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 23%;"><?= htmlspecialchars($siswa['kelas'] ?? '') ?></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($sekolah['alamat_sekolah'] ?? '') ?></td>
                    <td>Fase</td>
                    <td>:</td>
                    <?php $faseKelas = (strpos($siswa['kelas'] ?? '', 'X ') === 0) ? 'E' : 'F'; ?>
                    <td><?= htmlspecialchars($siswa['fase'] ?? $faseKelas) ?></td>
                </tr>
                <tr>
                    <td>Nama</td>
                    <td>:</td>
                    <td style="font-weight: bold;"><?= htmlspecialchars($siswa['nama_siswa'] ?? '') ?></td>
                    <td>Semester</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($sekolah['semester'] ?? 'Ganjil') ?></td>
                </tr>
                <tr>
                    <td>NIS / NISN</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($siswa['nis'] ?? '') ?> / <?= htmlspecialchars($siswa['nisn'] ?? '') ?></td>
                    <td>Tahun Pelajaran</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($sekolah['tahun_pelajaran'] ?? '') ?></td>
                </tr>
            </table>

            <!-- Tabel Mata Pelajaran -->
            <table class="content-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">NO.</th>
                        <th style="width: 64%;">MATA PELAJARAN</th>
                        <th style="width: 15%;">KKM / KKTP</th>
                        <th style="width: 15%;">NILAI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $kelompokUmum = [];
                    $kelompokKejuruan = [];
                    $kelompokLainnya = [];

                    if (!empty($listNilaiSiswa)) {
                        foreach ($listNilaiSiswa as $ns) {
                            $km = strtolower($ns['kelompok_mapel'] ?? '');
                            if (strpos($km, 'umum') !== false || strpos($km, 'nasional') !== false) {
                                $kelompokUmum[] = $ns;
                            } elseif (strpos($km, 'kejuruan') !== false) {
                                $kelompokKejuruan[] = $ns;
                            } else {
                                $kelompokLainnya[] = $ns;
                            }
                        }
                    }

                    // Kunci Jumlah Baris secara Statis agar tabel tidak berubah-ubah ukurannya
                    $maxBarisUmum = 9;
                    $maxBarisKejuruan = 13;
                    ?>

                    <!-- A. Kelompok Mata Pelajaran Umum -->
                    <tr class="section-header">
                        <td colspan="4">A. Kelompok Mata Pelajaran Umum</td>
                    </tr>
                    <?php
                    $no = 1;
                    $rowCountUmum = 0;
                    foreach ($kelompokUmum as $item) {
                        ?>
                        <tr style="height: 21px;">
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($item['nama_mapel'] ?? '') ?></td>
                            <td class="text-center"><?= ($sekolah['kkm_kktp'] ?? 75) ?></td>
                            <td class="text-center" style="font-weight: bold;"><?= ($item['nilai_akhir'] > 0) ? round($item['nilai_akhir']) : '' ?></td>
                        </tr>
                        <?php
                        $rowCountUmum++;
                    }

                    // Isi sisa baris kosong untuk kelompok Umum
                    while ($rowCountUmum < $maxBarisUmum) {
                        ?>
                        <tr style="height: 21px;">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
                        $rowCountUmum++;
                    }
                    ?>

                    <!-- B. Kelompok Mata Pelajaran Kejuruan -->
                    <tr class="section-header">
                        <td colspan="4">B. Kelompok Mata Pelajaran Kejuruan</td>
                    </tr>
                    <?php
                    $noKejuruan = 1;
                    $charKejuruan = 'a';
                    $headerSubPrinted = false;
                    $rowCountKejuruan = 0;
                    
                   // Ambil string Jurusan dari Kelas (Contoh: "X MPLB" -> "MPLB")
                    $kelasParts = explode(' ', $siswa['kelas'] ?? '');
                    $tingkat = $kelasParts[0] ?? 'X'; // Mengambil tingkat kelas (X, XI, atau XII)
                    $jurusan = $kelasParts[1] ?? 'MPLB'; 

                    $allKejuruan = array_merge($kelompokKejuruan, $kelompokLainnya);
                    
                    foreach ($allKejuruan as $item) {
                        // Jika ini sub-mapel pertama, buat row Keterangan
                        if (isset($item['is_sub']) && $item['is_sub'] == 1 && !$headerSubPrinted) {
                            
                            // Menentukan text judul berdasarkan kelas
                            $judulKeahlian = ($tingkat === 'X') ? 'Dasar-dasar Program Keahlian' : 'Konsentrasi Keahlian';
                            
                            ?>
                            <tr style="height: 21px;">
                                <td class="text-center"><?= $noKejuruan++ ?></td>
                                <td><?= $judulKeahlian ?> <?= htmlspecialchars($jurusan) ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php
                            $headerSubPrinted = true;
                            $rowCountKejuruan++;
                        }
                        
                        $isSub = isset($item['is_sub']) && $item['is_sub'] == 1;
                        ?>
                        <tr style="height: 21px;">
                            <!-- Nomor: Rata tengah untuk angka, Rata kanan untuk huruf abjad -->
                            <td class="<?= $isSub ? 'text-right' : 'text-center' ?>">
                                <?= $isSub ? ($charKejuruan++ . '.') : $noKejuruan++ ?>
                            </td>
                            <!-- Tambah sedikit padding indentasi jika dia sub mapel -->
                            <td style="<?= $isSub ? 'padding-left: 15px;' : '' ?>">
                                <?= htmlspecialchars($item['nama_mapel'] ?? '') ?>
                            </td>
                            <td class="text-center"><?= ($sekolah['kkm_kktp'] ?? 75) ?></td>
                            <td class="text-center" style="font-weight: bold;"><?= ($item['nilai_akhir'] > 0) ? round($item['nilai_akhir']) : '' ?></td>
                        </tr>
                        <?php
                        $rowCountKejuruan++;
                    }

                    // Isi sisa baris kosong untuk kelompok Kejuruan
                    while ($rowCountKejuruan < $maxBarisKejuruan) {
                        ?>
                        <tr style="height: 21px;">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
                        $rowCountKejuruan++;
                    }
                    ?>
                </tbody>
            </table>

            <!-- Bagian Ketidakhadiran -->
            <div style="width: 100%; overflow: hidden;">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th colspan="2" style="text-align: left; padding-left: 8px;">Ketidakhadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 60%;">Sakit</td>
                            <td>: <?= $absensi['sakit'] ?? 0 ?> Hari</td>
                        </tr>
                        <tr>
                            <td>Izin</td>
                            <td>: <?= $absensi['izin'] ?? 0 ?> Hari</td>
                        </tr>
                        <tr>
                            <td>Tanpa Keterangan</td>
                            <td>: <?= $absensi['tanpa_keterangan'] ?? 0 ?> Hari</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bagian Tanda Tangan Terkunci di Paling Bawah (absolute bottom) -->
        <div class="signature-section">
            <div style="width: 100%; overflow: hidden;">
                <!-- Kolom Orang Tua / Wali Murid di Kiri -->
                <div class="ortu-container">
                    <p style="margin: 0;"><br>Orang Tua/Wali</p>
                    <div class="spacer-ttd"></div>
                    <p style="border-bottom: 1px solid #000; width: 100%; margin: 0;"></p>
                </div>

                <!-- Kolom Wali Kelas di Kanan -->
                <div class="walikelas-container">
                    <!--<p style="margin: 0;">Depok, <?= date('d F Y') ?><br>Wali Kelas</p>-->
					<p style="margin: 0;">Depok, 9 Oktober 2026<br>Wali Kelas</p>
                    <?php if (!empty($waliKelasTtd)) : ?>
                        <img src="<?= htmlspecialchars($waliKelasTtd) ?>" alt="TTD Wali Kelas" class="walikelas-img">
                    <?php else: ?>
                        <div class="spacer-ttd"></div>
                    <?php endif; ?>
                    <p style="font-weight: bold; text-decoration: underline; margin: 0;"><?= htmlspecialchars($namaWaliKelas ?? '') ?></p>
                    <p style="margin: 0;">NIP. <?= htmlspecialchars($nipWaliKelas ?? '') ?></p>
                </div>
            </div>

            <!-- Kepala Sekolah di Tengah Bawah -->
            <div class="kepsek-container">
                <p style="margin: 0;">Mengetahui,<br>Kepala Sekolah</p>
                <?php if (!empty($sekolah['tanda_tangan_kepsek'])) : ?>
                    <img src="<?= htmlspecialchars($sekolah['tanda_tangan_kepsek']) ?>" alt="TTD Kepsek" class="kepsek-img">
                <?php else: ?>
                    <div class="spacer-ttd"></div>
                <?php endif; ?>
                <p style="font-weight: bold; text-decoration: underline; margin: 0;"><?= htmlspecialchars($sekolah['nama_kepala_sekolah'] ?? '') ?></p>
                <p style="margin: 0;">NRKS. <?= htmlspecialchars($sekolah['nrks'] ?? '-') ?></p>
            </div>
        </div>
    </div>

</body>

</html>