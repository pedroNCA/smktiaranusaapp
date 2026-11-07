<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pratinjau Raport - <?= htmlspecialchars($siswa['nama_siswa'] ?? '') ?></title>
    <style>
        /* Desain Lembar Kertas F4 / Folio untuk Pratinjau Web - Full 1 Halaman */
        .raport-container {
            width: 210mm;
            min-height: 330mm;
            margin: 0 auto;
            background: #fff;
            padding: 20mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            font-family: 'Calibri', Arial, sans-serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.15;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .raport-body {
            flex-grow: 1;
        }

        .judul {
            font-family: 'Cambria', Times, serif;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 11pt;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* Tabel Utama Mata Pelajaran */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11pt;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }

        .content-table th {
            text-align: center;
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .section-header {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        /* Tabel Ketidakhadiran & Tanda Tangan */
        .attendance-table {
            width: 45%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-top: 10px;
            float: left;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        .signature-section {
            width: 100%;
            margin-top: 30px;
            clear: both;
            font-size: 11pt;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .sig-table td {
            width: 50%;
            vertical-align: top;
        }

        .walikelas-container {
            float: right;
            width: 280px;
            text-align: center;
            /* Membuat kontainer utama berada di tengah area kanannya */
        }

        .walikelas-inner {
            display: inline-block;
            text-align: center;
            /* Membuat teks di dalamnya rata kiri secara rapi */
        }

        .ortu-container {
            float: left;
            width: 200px;
            text-align: center;
            padding-left: 20px;
        }

        .kepsek-container {
            width: 100%;
            clear: both;
            text-align: center;
            margin-top: 20px;
        }

        .kepsek-img {
            height: 70px;
            margin: 4px auto;
            display: block;
        }

        .walikelas-img {
            height: 70px;
            margin: 4px auto;
            display: block;
        }
    </style>
</head>

<body class="bg-gray-100 py-10">

    <!-- Navigasi Atas -->
    <div class="max-w-[210mm] mx-auto mb-6 flex items-center justify-between px-2">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Pratinjau Raport Peserta Didik</h2>
            <p class="text-sm text-gray-500">Mode pengecekan sampel siswa (Pencetakan langsung dinonaktifkan pada halaman ini).</p>
        </div>
        <div>
            <a href="/walikelas/nilai" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl transition-all shadow-sm">
                &larr; Kembali ke Rekap Nilai
            </a>
        </div>
    </div>

    <!-- Lembar Raport Ukuran F4 -->
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
                    <td><?= htmlspecialchars($siswa['fase'] ?? 'E') ?></td>
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
                        <th style="width: 8%;">NO.</th>
                        <th style="width: 62%;">MATA PELAJARAN</th>
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

                    // Baris minimum per kelompok agar tabel memenuhi satu halaman penuh F4
                    $minRowsPerGroup = 9;
                    ?>

                    <!-- A. Kelompok Mata Pelajaran Umum -->
                    <tr class="section-header">
                        <td colspan="4">A. Kelompok Mata Pelajaran Umum</td>
                    </tr>
                    <?php
                    $no = 1;
                    $totalBarisUmum = max(count($kelompokUmum), $minRowsPerGroup);
                    for ($i = 0; $i < $totalBarisUmum; $i++) {
                        $item = $kelompokUmum[$i] ?? null;
                    ?>
                        <tr style="height: 22px;">
                            <td class="text-center"><?= $item ? $no++ : '' ?></td>
                            <td><?= htmlspecialchars($item['nama_mapel'] ?? '') ?></td>
                            <td class="text-center"><?= $item ? ($sekolah['kkm_kktp'] ?? 75) : '' ?></td>
                            <td class="text-center" style="font-weight: bold;"><?= ($item && $item['nilai_akhir'] > 0) ? round($item['nilai_akhir']) : '' ?></td>
                        </tr>
                    <?php } ?>

                    <!-- B. Kelompok Mata Pelajaran Kejuruan -->
                    <tr class="section-header">
                        <td colspan="4">B. Kelompok Mata Pelajaran Kejuruan</td>
                    </tr>
                    <?php
                    $no = 1;
                    $allKejuruan = array_merge($kelompokKejuruan, $kelompokLainnya);
                    $totalBarisKejuruan = max(count($allKejuruan), $minRowsPerGroup);
                    for ($i = 0; $i < $totalBarisKejuruan; $i++) {
                        $item = $allKejuruan[$i] ?? null;
                    ?>
                        <tr style="height: 22px;">
                            <td class="text-center"><?= $item ? $no++ : '' ?></td>
                            <td><?= htmlspecialchars($item['nama_mapel'] ?? '') ?></td>
                            <td class="text-center"><?= $item ? ($sekolah['kkm_kktp'] ?? 75) : '' ?></td>
                            <td class="text-center" style="font-weight: bold;"><?= ($item && $item['nilai_akhir'] > 0) ? round($item['nilai_akhir']) : '' ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Bagian Ketidakhadiran -->
            <div style="width: 100%; overflow: hidden; margin-top: 10px;">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th colspan="2" style="text-align: left; padding-left: 8px;">Ketidakhadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sakit</td>
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

        <!-- Bagian Tanda Tangan -->
        <div class="signature-section">
            <div style="width: 100%; overflow: hidden;">
                <!-- Kolom Orang Tua / Wali Murid di Kiri -->
                <div class="ortu-container">
                    <p style="margin: 0; line-height: 1.15;"><br>Orang Tua/Wali,</p>
                    <br><br><br><br><br>
                    <p style="border-bottom: 1px solid #000; width: 200px; margin: 0;"></p>
                </div>

                <!-- Kolom Wali Kelas di Kanan (Rata Tengah Sempurna) -->
                <div class="walikelas-container">
                    <div class="walikelas-inner" style="text-align: center;">
                        <p style="margin: 0; line-height: 1.15;">Depok, <?= date('d F Y') ?></p>
                        <p style="margin: 0; line-height: 1.15;">Wali Kelas,</p>
                        <?php if (!empty($waliKelasTtd)) : ?>
                            <img src="<?= htmlspecialchars($waliKelasTtd) ?>" alt="Tanda Tangan Wali Kelas" class="walikelas-img">
                        <?php else: ?>
                            <br><br><br>
                        <?php endif; ?>
                        <p style="font-weight: bold; text-decoration: underline; margin: 0;"><?= htmlspecialchars($namaWaliKelas ?? '') ?></p>
                        <p style="margin: 0;">NIP. <?= htmlspecialchars($nipWaliKelas ?? '') ?></p>
                    </div>
                </div>
            </div>

            <!-- Kepala Sekolah di Tengah Bawah -->
            <div class="kepsek-container">
                <p style="margin: 0;">Mengetahui,</p>
                <p style="margin: 0;">Kepala Sekolah</p>
                <?php if (!empty($sekolah['tanda_tangan_kepsek'])) : ?>
                    <img src="<?= htmlspecialchars($sekolah['tanda_tangan_kepsek']) ?>" alt="Tanda Tangan Kepala Sekolah" class="kepsek-img">
                <?php else: ?>
                    <br><br>
                <?php endif; ?>
                <p style="font-weight: bold; text-decoration: underline; margin: 0;"><?= htmlspecialchars($sekolah['nama_kepala_sekolah'] ?? '') ?></p>
                <p style="margin: 0;">NRKS. <?= htmlspecialchars($sekolah['nrks'] ?? '-') ?></p>
            </div>
        </div>
    </div>

</body>

</html>