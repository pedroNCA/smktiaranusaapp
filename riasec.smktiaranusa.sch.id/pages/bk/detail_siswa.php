<?php
// pages/bk/detail_siswa.php

require_once __DIR__ . '/../../config/session.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$siswa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$siswa_id) {
    die("ID Siswa tidak valid.");
}

// Ambil data siswa dan hasil asesmen menggunakan JOIN ke master_siswa
$stmt = $pdo->prepare("
    SELECT 
        ms.nama, ms.nis, ms.kelas, ms.jenis_kelamin, 
        ha.* 
    FROM master_siswa ms 
    JOIN hasil_asesmen ha ON ms.id = ha.siswa_id 
    WHERE ms.id = ?
");
$stmt->execute([$siswa_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data siswa tidak ditemukan atau siswa belum mengisi asesmen.");
}

$page_title = 'Detail Hasil Asesmen';

// --- FORMAT TANGGAL BAHASA INDONESIA ---
$bulan_indo = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];
$waktu_pengisian = strtotime($data['tanggal_pengisian']);
$tanggal_indo = date('d', $waktu_pengisian) . ' ' . $bulan_indo[date('m', $waktu_pengisian)] . ' ' . date('Y', $waktu_pengisian);


// --- FUNGSI BANTU INTERPRETASI SESUAI PEDOMAN ---

// 1. Kategori Minat
function getKategoriMinat($skor)
{
    if ($skor >= 17) return ['Sangat Tinggi', 'bg-blue-600 text-white'];
    if ($skor >= 13) return ['Tinggi', 'bg-blue-400 text-white'];
    if ($skor >= 9) return ['Sedang', 'bg-gray-300 text-gray-800'];
    return ['Rendah', 'bg-gray-100 text-gray-500'];
}

$minat_data = [
    'Akademik' => $data['m1_akademik'],
    'Sains & Teknologi' => $data['m2_sains'],
    'Seni' => $data['m3_seni'],
    'Bahasa' => $data['m4_bahasa'],
    'Sosial' => $data['m5_sosial'],
    'Olahraga' => $data['m6_olahraga'],
    'Kewirausahaan' => $data['m7_wirausaha'],
    'Teknologi Digital' => $data['m8_digital'],
];

// 2. Indikasi Potensi Bakat (Cari 3 Teratas)
$bakat_data = [
    'Linguistik' => $data['b1_linguistik'],
    'Logika-Matematika' => $data['b2_logika'],
    'Visual-Spasial' => $data['b3_spasial'],
    'Kinestetik' => $data['b4_kinestetik'],
    'Musik' => $data['b5_musik'],
    'Interpersonal' => $data['b6_interpersonal'],
    'Intrapersonal' => $data['b7_intrapersonal'],
    'Naturalis' => $data['b8_naturalis'],
];
arsort($bakat_data);
$top_bakat = array_slice($bakat_data, 0, 3, true);

// 3. Deskripsi RIASEC
$riasec_desc = [
    'R' => ['Realistic (Praktis)', 'Cenderung menyukai praktik, alat, mesin, teknologi, pekerjaan lapangan, dan membuat/memperbaiki sesuatu.'],
    'I' => ['Investigative (Peneliti)', 'Cenderung menyukai penelitian, eksperimen, sains, analisis, pemecahan masalah, dan mencari informasi.'],
    'A' => ['Artistic (Kreatif)', 'Cenderung menyukai seni, desain, menulis, musik, fotografi, dan kreativitas.'],
    'S' => ['Social (Sosial)', 'Cenderung menyukai membantu orang, mengajar, berkomunikasi, bekerja sama, dan kegiatan sosial.'],
    'E' => ['Enterprising (Wirausaha)', 'Cenderung menyukai memimpin, memengaruhi, berorganisasi, berjualan, bisnis, dan mengambil inisiatif.'],
    'C' => ['Conventional (Rutin)', 'Cenderung menyukai keteraturan, data, administrasi, pencatatan, dan pekerjaan yang sistematis.']
];
$kode_holland = str_split($data['kode_holland']);

// Panggil Komponen Atas
require_once __DIR__ . '/../../includes/bk_header.php';
?>
<!-- TAMBAHAN CSS CETAK PROFESIONAL -->
<style>
    @media print {

        /* 1. Sembunyikan elemen UI web */
        aside,
        header,
        .no-print {
            display: none !important;
        }

        /* 2. Reset Layout (mencegah scrollbar & terpotong) */
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

        /* 3. Pengaturan Kertas Formal */
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }

        body {
            background: white !important;
            color: black !important;
            font-size: 11pt !important;
            line-height: 1.5;
        }

        main {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        /* 4. Paksa Warna Background Tercetak (Untuk Progress bar, Badge, dll) */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 5. Ubah Card menjadi Block Formal & Cegah Terpotong */
        .print-shadow-none {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
            margin-bottom: 20px !important;
            padding: 20px !important;
        }

        .print-break-inside-avoid {
            page-break-inside: avoid;
        }
    }
</style>
<?php
require_once __DIR__ . '/../../includes/bk_sidebar.php';
require_once __DIR__ . '/../../includes/bk_navbar.php';
?>

<!-- ================= KONTEN UTAMA ================= -->
<div class="max-w-5xl mx-auto space-y-6 pb-12">

    <!-- Header & Action Buttons (Hanya tampil di Web) -->
    <div class="flex flex-col sm:flex-row justify-between items-center no-print gap-4">
        <a href="asesmen.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
        <div class="flex gap-2 sm:gap-3 flex-wrap justify-end">
            <!-- Tombol Hapus -->
            <a href="../../proses/hapus_siswa.php?id=<?= $siswa_id ?>" onclick="return confirm('Yakin ingin menghapus data <?= htmlspecialchars($data['nama']) ?>?');" class="inline-flex items-center gap-2 bg-red-50 text-red-600 px-4 py-2 rounded-lg font-semibold hover:bg-red-600 hover:text-white transition shadow-sm border border-red-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus
            </a>

            <!-- Tombol Detail Jawaban -->
            <a href="detail_jawaban.php?id=<?= $siswa_id ?>" class="inline-flex items-center gap-2 bg-blue-100 text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-200 transition shadow-sm border border-blue-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Seluruh Jawaban
            </a>

            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg font-semibold hover:bg-primary-light transition shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Cetak Laporan PDF
            </button>
        </div>
    </div>

    <!-- Kop Surat Formal (Hanya Tampil Saat Dicetak) -->
    <div class="hidden print:block mb-8 border-b-2 border-black pb-4 text-center">
        <h1 class="text-2xl font-black uppercase tracking-wider text-gray-900">Laporan Hasil Asesmen Minat & Bakat</h1>
        <h2 class="text-xl font-bold mt-1 text-gray-800">SMK Tiara Nusa</h2>
        <p class="text-sm font-medium mt-2 text-gray-600">Tanggal Pengisian: <?= $tanggal_indo ?></p>
    </div>

    <!-- 1. Identitas Peserta Didik -->
    <div class="bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm print-shadow-none print-break-inside-avoid">
        <!-- Judul Web (Sembunyikan saat dicetak karena sudah ada kop surat) -->
        <div class="border-b-2 border-primary pb-4 mb-6 flex justify-between items-end print:hidden">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 uppercase">Laporan Asesmen Minat & Bakat</h2>
                <p class="text-sm text-gray-500 font-medium mt-1">SMK Tiara Nusa</p>
            </div>
            <div class="text-right text-sm text-gray-500">
                Tanggal: <span class="font-semibold text-gray-900"><?= $tanggal_indo ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 print:grid-cols-2 print:gap-4">
            <table class="w-full text-sm text-left">
                <tbody>
                    <tr class="border-b border-gray-100 print:border-gray-300">
                        <th class="py-2 text-gray-500 w-1/3 print:text-gray-900">Nama Lengkap</th>
                        <td class="py-2 font-bold text-gray-900">: <?= htmlspecialchars($data['nama']) ?></td>
                    </tr>
                    <tr class="border-b border-gray-100 print:border-gray-300">
                        <th class="py-2 text-gray-500 print:text-gray-900">NIS</th>
                        <td class="py-2 font-semibold text-gray-900">: <?= htmlspecialchars(decryptData($data['nis'])) ?></td>
                    </tr>
                    <tr class="border-b border-gray-100 print:border-gray-300">
                        <th class="py-2 text-gray-500 print:text-gray-900">Kelas</th>
                        <td class="py-2 font-semibold text-gray-900">: <?= htmlspecialchars($data['kelas']) ?></td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 print:text-gray-900">Jenis Kelamin</th>
                        <td class="py-2 font-semibold text-gray-900">: <?= htmlspecialchars($data['jenis_kelamin']) ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 print:border-gray-300 print:bg-white text-sm space-y-2">
                <p><span class="font-semibold text-gray-500 print:text-gray-900">Cita-cita:</span> <?= htmlspecialchars($data['cita_cita']) ?: '-' ?></p>
                <p><span class="font-semibold text-gray-500 print:text-gray-900">Hobi/Kegiatan:</span> <?= htmlspecialchars($data['kegiatan_favorit']) ?: '-' ?></p>
                <p><span class="font-semibold text-gray-500 print:text-gray-900">Prestasi:</span> <?= htmlspecialchars($data['prestasi']) ?: '-' ?></p>
            </div>
        </div>
    </div>

    <!-- 2. Aspek Minat -->
    <div class="bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm print-shadow-none print-break-inside-avoid">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="bg-primary print:bg-gray-800 text-white w-8 h-8 rounded-lg flex items-center justify-center">A</span>
            Profil Aspek Minat
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 print:grid-cols-2">
            <?php foreach ($minat_data as $bidang => $skor):
                $kat = getKategoriMinat($skor);
                // Hitung persentase untuk lebar progress bar (Max 20)
                $percent = ($skor / 20) * 100;
            ?>
                <div class="mb-2">
                    <div class="flex justify-between items-end mb-1">
                        <span class="text-sm font-semibold text-gray-700"><?= $bidang ?></span>
                        <div class="text-right">
                            <span class="text-xs font-bold mr-2"><?= $skor ?>/20</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider <?= $kat[1] ?>"><?= $kat[0] ?></span>
                        </div>
                    </div>
                    <!-- Gunakan bg-gray-200 & bg-primary agar tetap berwarna saat dicetak -->
                    <div class="w-full bg-gray-200 rounded-full h-2 print:border print:border-gray-300">
                        <div class="bg-primary h-2 rounded-full print:bg-blue-600" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:grid-cols-2 print:gap-4">
        <!-- 3. Aspek Bakat (Multiple Intelligences) -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none print-break-inside-avoid h-full">
            <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                <span class="bg-primary print:bg-gray-800 text-white w-8 h-8 rounded-lg flex items-center justify-center">B</span>
                Indikasi Kekuatan Potensi
            </h3>
            <p class="text-xs text-gray-500 mb-4">*Berdasarkan 3 skor tertinggi kecerdasan majemuk.</p>

            <div class="space-y-3">
                <?php foreach ($top_bakat as $aspek => $skor): ?>
                    <div class="flex items-center gap-4 bg-blue-50 border border-blue-100 print:bg-white print:border-gray-300 p-3 rounded-lg">
                        <div class="bg-white border-2 border-primary print:border-gray-800 print:text-gray-800 text-primary font-bold w-10 h-10 rounded-full flex items-center justify-center text-lg shadow-sm">
                            <?= $skor ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900"><?= $aspek ?></p>
                            <p class="text-xs text-gray-600">Skor skala 1-4</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600 border border-gray-200 print:bg-white print:border-gray-300">
                <strong>Catatan BK:</strong> Perlu diverifikasi lebih lanjut melalui observasi, wawancara, atau portofolio siswa.
            </div>
        </div>

        <!-- 4. RIASEC -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none print-break-inside-avoid h-full">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span class="bg-primary print:bg-gray-800 text-white w-8 h-8 rounded-lg flex items-center justify-center">C</span>
                Kecenderungan Karier (RIASEC)
            </h3>

            <div class="flex justify-center mb-6">
                <div class="text-center">
                    <p class="text-sm font-semibold text-gray-500 mb-1">KODE HOLLAND SISWA</p>
                    <div class="text-4xl font-black text-primary print:text-gray-900 tracking-widest border-4 border-primary print:border-gray-800 px-6 py-2 rounded-xl inline-block shadow-sm bg-white">
                        <?= $data['kode_holland'] ?>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <?php foreach ($kode_holland as $huruf): ?>
                    <div class="border-l-4 border-primary print:border-gray-800 pl-3">
                        <h4 class="text-sm font-bold text-gray-900"><?= $riasec_desc[$huruf][0] ?></h4>
                        <p class="text-xs text-gray-600 leading-relaxed"><?= $riasec_desc[$huruf][1] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 5. Kesimpulan Otomatis untuk BK -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm print-shadow-none mt-6 print-break-inside-avoid">
        <h3 class="text-md font-bold text-gray-900 mb-2">💡 Catatan Interpretasi Praktis</h3>
        <p class="text-sm text-gray-700 leading-relaxed">
            Profil minat siswa menunjukkan kecenderungan kuat pada
            <?php
            // Ambil bidang minat yang kategorinya 'Sangat Tinggi' atau 'Tinggi'
            $minat_kuat = [];
            foreach ($minat_data as $bidang => $skor) {
                if ($skor >= 13) $minat_kuat[] = "<strong>$bidang</strong>";
            }
            echo !empty($minat_kuat) ? implode(", ", $minat_kuat) : "berbagai bidang secara merata";
            ?>.
            Profil ini dapat menjadi bahan eksplorasi lebih lanjut terhadap pilihan kegiatan, mata pelajaran, pengalaman belajar, dan rencana karier siswa.
            Keputusan karir tidak boleh ditentukan hanya berdasarkan satu lembar asesmen ini.
        </p>
    </div>

</div>
<!-- ================= AKHIR KONTEN UTAMA ================= -->

<?php require_once __DIR__ . '/../../includes/bk_footer.php'; ?>