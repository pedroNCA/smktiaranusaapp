<?php
// pages/bk/detail_jawaban.php

require_once __DIR__ . '/../../config/session.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$siswa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$siswa_id) die("ID Siswa tidak valid.");

// Ambil data siswa dan jawaban dari database (JOIN master_siswa & hasil_asesmen)
$stmt = $pdo->prepare("
    SELECT ms.nama, ms.kelas, ms.jenis_kelamin, ha.* 
    FROM master_siswa ms 
    JOIN hasil_asesmen ha ON ms.id = ha.siswa_id 
    WHERE ms.id = ?
");
$stmt->execute([$siswa_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Data tidak ditemukan atau siswa belum mengisi asesmen.");
if (empty($data['jawaban_lengkap'])) die("Siswa ini mengisi asesmen sebelum fitur rekam detail jawaban ditambahkan.");

$jawaban = json_decode($data['jawaban_lengkap'], true);
$page_title = 'Rincian Jawaban: ' . htmlspecialchars($data['nama']);

// Referensi Soal
$soal_minat = [
    'Akademik' => [1 => 'Saya senang membaca buku.', 2 => 'Saya suka mempelajari hal baru.', 3 => 'Saya senang mengerjakan soal yang menantang.', 4 => 'Saya tertarik mengikuti lomba akademik.', 5 => 'Saya ingin melanjutkan pendidikan setinggi mungkin.'],
    'Sains & Teknologi' => [6 => 'Saya suka melakukan eksperimen.', 7 => 'Saya tertarik pada teknologi.', 8 => 'Saya senang belajar matematika dan IPA.', 9 => 'Saya tertarik robotika/coding.', 10 => 'Saya ingin menciptakan inovasi baru.'],
    'Seni' => [11 => 'Saya senang menggambar.', 12 => 'Saya suka bernyanyi.', 13 => 'Saya tertarik pada desain.', 14 => 'Saya menikmati fotografi.', 15 => 'Saya suka tampil di depan umum.'],
    'Bahasa' => [16 => 'Saya senang menulis.', 17 => 'Saya suka membaca cerita.', 18 => 'Saya tertarik belajar bahasa asing.', 19 => 'Saya senang berbicara di depan umum.', 20 => 'Saya suka membuat cerita.'],
    'Sosial' => [21 => 'Saya senang membantu orang lain.', 22 => 'Saya mudah bergaul.', 23 => 'Saya suka bekerja dalam tim.', 24 => 'Saya tertarik menjadi guru/konselor.', 25 => 'Saya senang mengikuti kegiatan sosial.'],
    'Olahraga' => [26 => 'Saya senang berolahraga.', 27 => 'Saya suka aktivitas luar ruangan.', 28 => 'Saya menikmati permainan tim.', 29 => 'Saya menjaga kebugaran tubuh.', 30 => 'Saya ingin berprestasi di bidang olahraga.'],
    'Kewirausahaan' => [31 => 'Saya suka berjualan.', 32 => 'Saya ingin memiliki usaha sendiri.', 33 => 'Saya tertarik bisnis online.', 34 => 'Saya suka mencari peluang.', 35 => 'Saya senang membuat produk yang kreatif.'],
    'Teknologi Digital' => [36 => 'Saya suka menggunakan komputer.', 37 => 'Saya tertarik media sosial.', 38 => 'Saya suka membuat konten digital.', 39 => 'Saya tertarik desain grafis/editing.', 40 => 'Saya ingin menguasai teknologi masa depan.']
];
$soal_bakat = [
    1 => ['Linguistik (Bahasa)', 'Saya mudah mengungkapkan ide melalui tulisan atau lisan.'],
    2 => ['Logika Matematika', 'Saya suka berhitung dan memecahkan masalah logika.'],
    3 => ['Visual Spasial', 'Saya mudah memahami gambar dan desain.'],
    4 => ['Kinestetik', 'Saya aktif bergerak dan belajar melalui praktik.'],
    5 => ['Musik', 'Saya peka terhadap nada dan irama.'],
    6 => ['Interpersonal', 'Saya mudah memahami perasaan orang lain.'],
    7 => ['Intrapersonal', 'Saya mengenal diri saya dengan baik.'],
    8 => ['Naturalis', 'Saya menyukai tumbuhan, hewan, dan alam.']
];
$soal_riasec = [
    'R - Realistic' => [1 => 'Saya senang membuat atau memperbaiki sesuatu dengan tangan.', 2 => 'Saya tertarik menggunakan alat atau mesin.', 3 => 'Saya senang melakukan kegiatan praktik daripada hanya membaca teori.', 4 => 'Saya tertarik dengan pekerjaan yang dilakukan di luar ruangan.', 5 => 'Saya senang membuat benda atau produk yang dapat digunakan.', 6 => 'Saya tertarik mempelajari cara kerja kendaraan, mesin, atau peralatan.'],
    'I - Investigative' => [7 => 'Saya senang mencari tahu mengapa sesuatu bisa terjadi.', 8 => 'Saya suka memecahkan soal atau masalah yang menantang.', 9 => 'Saya tertarik melakukan eksperimen atau penelitian sederhana.', 10 => 'Saya senang mengamati, membandingkan, dan menemukan pola.', 11 => 'Saya tertarik mempelajari sains, teknologi, atau ilmu pengetahuan.', 12 => 'Saya senang mencari informasi sebelum mengambil kesimpulan.'],
    'A - Artistic' => [13 => 'Saya senang menggambar, melukis, atau membuat karya visual.', 14 => 'Saya tertarik membuat desain atau karya kreatif.', 15 => 'Saya senang menulis cerita, puisi, atau membuat tulisan kreatif.', 16 => 'Saya menikmati kegiatan musik, bernyanyi, atau memainkan alat musik.', 17 => 'Saya senang menemukan cara yang berbeda dan unik dalam mengerjakan sesuatu.', 18 => 'Saya tertarik pada fotografi, video, animasi, atau desain digital.'],
    'S - Social' => [19 => 'Saya senang membantu teman yang sedang mengalami kesulitan.', 20 => 'Saya senang berdiskusi dan berbicara dengan orang lain.', 21 => 'Saya tertarik mengajar atau menjelaskan sesuatu kepada orang lain.', 22 => 'Saya senang bekerja dalam kelompok.', 23 => 'Saya mudah memahami perasaan atau keadaan orang lain.', 24 => 'Saya tertarik melakukan kegiatan yang bermanfaat bagi masyarakat.'],
    'E - Enterprising' => [25 => 'Saya senang menjadi pemimpin dalam kegiatan kelompok.', 26 => 'Saya berani menyampaikan ide atau pendapat di depan orang lain.', 27 => 'Saya tertarik membuat atau menjalankan usaha.', 28 => 'Saya senang meyakinkan orang lain terhadap ide yang saya miliki.', 29 => 'Saya senang mengambil inisiatif ketika melihat suatu masalah.', 30 => 'Saya tertarik mempelajari pemasaran, bisnis, atau cara menjual produk.'],
    'C - Conventional' => [31 => 'Saya senang menyusun data atau informasi agar lebih rapi.', 32 => 'Saya senang membuat daftar dan membuat jadwal kegiatan.', 33 => 'Saya teliti ketika mengerjakan tugas yang membutuhkan ketepatan.', 34 => 'Saya senang bekerja menggunakan komputer untuk mengolah data atau dokumen.', 35 => 'Saya nyaman mengikuti aturan dan langkah kerja yang jelas.', 36 => 'Saya senang mengarsipkan, mencatat atau mengelompokkan informasi.']
];

require_once __DIR__ . '/../../includes/bk_header.php';
?>
<!-- CSS PERBAIKAN KHUSUS CETAK -->
<style>
    @media print {

        /* 1. Sembunyikan elemen navigasi & tombol */
        aside,
        header,
        .no-print {
            display: none !important;
        }

        /* 2. Reset container induk agar tidak memicu scroll/pemotongan halaman */
        html,
        body,
        main,
        div {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
            position: static !important;
            float: none !important;
        }

        body {
            background: white !important;
            color: black !important;
            font-size: 11pt;
        }

        main {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        /* 3. Atur Margin Kertas Cetak */
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }

        /* 4. Tampilan Card & Tabel untuk Cetak Formal */
        .print-shadow-none {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            margin-bottom: 20px !important;
            border-radius: 0 !important;
        }

        /* Mencegah tabel terpotong secara canggung di tengah baris */
        .print-break-inside-avoid {
            page-break-inside: avoid;
        }

        tr {
            page-break-inside: avoid;
        }
    }
</style>
<?php
require_once __DIR__ . '/../../includes/bk_sidebar.php';
require_once __DIR__ . '/../../includes/bk_navbar.php';
?>

<div class="max-w-4xl mx-auto pb-12 space-y-6">

    <!-- Tombol Aksi (Disembunyikan saat cetak) -->
    <div class="flex items-center justify-between gap-4 mb-4 no-print">
        <a href="detail_siswa.php?id=<?= $siswa_id ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Detail
        </a>

        <button onclick="window.print()" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2 rounded-lg font-semibold hover:bg-primary-light transition shadow-sm border border-transparent">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Cetak Detail PDF
        </button>
    </div>

    <!-- Kop & Identitas Siswa saat Cetak -->
    <div class="hidden print:block mb-6 border-b-2 border-black pb-4">
        <h2 class="text-2xl font-bold uppercase text-center mb-1">Rincian Seluruh Jawaban Asesmen</h2>
        <p class="text-center font-medium mb-4">SMK Tiara Nusa</p>
        <table class="text-sm text-left w-full border-none">
            <tr>
                <th class="py-1 w-32">Nama Lengkap</th>
                <td>: <?= htmlspecialchars($data['nama']) ?></td>
            </tr>
            <tr>
                <th class="py-1">Kelas</th>
                <td>: <?= htmlspecialchars($data['kelas']) ?></td>
            </tr>
            <tr>
                <th class="py-1">Jenis Kelamin</th>
                <td>: <?= htmlspecialchars($data['jenis_kelamin']) ?></td>
            </tr>
        </table>
    </div>

    <!-- Bagian Pertanyaan Terbuka -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none print-break-inside-avoid">
        <h3 class="text-xl font-bold text-blue-800 mb-4 border-b pb-2">A. Jawaban Pertanyaan Terbuka</h3>
        <table class="w-full text-sm text-left border-collapse border border-gray-300">
            <tbody>
                <tr>
                    <th class="p-3 border border-gray-300 w-1/3 bg-gray-50 text-gray-700">Cita-cita</th>
                    <td class="p-3 border border-gray-300 font-medium"><?= htmlspecialchars($data['cita_cita']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th class="p-3 border border-gray-300 bg-gray-50 text-gray-700">Kegiatan Favorit</th>
                    <td class="p-3 border border-gray-300 font-medium"><?= htmlspecialchars($data['kegiatan_favorit']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th class="p-3 border border-gray-300 bg-gray-50 text-gray-700">Prestasi</th>
                    <td class="p-3 border border-gray-300 font-medium"><?= htmlspecialchars($data['prestasi']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th class="p-3 border border-gray-300 bg-gray-50 text-gray-700">Keterampilan yang ingin dikembangkan</th>
                    <td class="p-3 border border-gray-300 font-medium"><?= htmlspecialchars($data['keterampilan']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th class="p-3 border border-gray-300 bg-gray-50 text-gray-700">Dukungan yang dibutuhkan</th>
                    <td class="p-3 border border-gray-300 font-medium"><?= nl2br(htmlspecialchars($data['dukungan'])) ?: '-' ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bagian Minat -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none">
        <h3 class="text-xl font-bold text-blue-800 mb-4 border-b pb-2">B. Jawaban Aspek Minat</h3>
        <table class="w-full text-sm text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border border-gray-300 w-12 text-center">No</th>
                    <th class="p-3 border border-gray-300">Pernyataan</th>
                    <th class="p-3 border border-gray-300 w-24 text-center">Skor (1-4)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($soal_minat as $kat => $soals): ?>
                    <tr class="print-break-inside-avoid">
                        <td colspan="3" class="bg-blue-50 font-bold p-2 text-blue-900 border border-gray-300"><?= $kat ?></td>
                    </tr>
                    <?php foreach ($soals as $no => $tanya): ?>
                        <tr>
                            <td class="p-2 border border-gray-300 text-center font-medium"><?= $no ?></td>
                            <td class="p-2 border border-gray-300"><?= $tanya ?></td>
                            <td class="p-2 border border-gray-300 text-center font-bold text-primary"><?= $jawaban['minat'][$no] ?? '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bagian Bakat -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none print-break-inside-avoid">
        <h3 class="text-xl font-bold text-blue-800 mb-4 border-b pb-2">C. Jawaban Aspek Bakat</h3>
        <table class="w-full text-sm text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border border-gray-300 w-12 text-center">No</th>
                    <th class="p-3 border border-gray-300 w-48">Aspek</th>
                    <th class="p-3 border border-gray-300">Pernyataan</th>
                    <th class="p-3 border border-gray-300 w-24 text-center">Skor (1-4)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($soal_bakat as $no => $dataBakat): ?>
                    <tr>
                        <td class="p-2 border border-gray-300 text-center font-medium"><?= $no ?></td>
                        <td class="p-2 border border-gray-300 font-bold text-gray-800"><?= $dataBakat[0] ?></td>
                        <td class="p-2 border border-gray-300"><?= $dataBakat[1] ?></td>
                        <td class="p-2 border border-gray-300 text-center font-bold text-primary"><?= $jawaban['bakat'][$no] ?? '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bagian RIASEC -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none">
        <h3 class="text-xl font-bold text-blue-800 mb-4 border-b pb-2">D. Jawaban RIASEC (Holland)</h3>
        <table class="w-full text-sm text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border border-gray-300 w-12 text-center">No</th>
                    <th class="p-3 border border-gray-300">Pernyataan</th>
                    <th class="p-3 border border-gray-300 w-24 text-center">Skor (1-4)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($soal_riasec as $kat => $soals): ?>
                    <tr class="print-break-inside-avoid">
                        <td colspan="3" class="bg-blue-50 font-bold p-2 text-blue-900 border border-gray-300"><?= $kat ?></td>
                    </tr>
                    <?php foreach ($soals as $no => $tanya): ?>
                        <tr>
                            <td class="p-2 border border-gray-300 text-center font-medium"><?= $no ?></td>
                            <td class="p-2 border border-gray-300"><?= $tanya ?></td>
                            <td class="p-2 border border-gray-300 text-center font-bold text-primary"><?= $jawaban['riasec'][$no] ?? '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>