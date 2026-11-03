<?php

/**
 * Halaman Laporan & Cetak Dokumen Resmi Kepala Sekolah (Humanis Insight)
 * Diperbarui: Mendukung Kalkulasi Ketercapaian Target (Realisasi) per Bidang
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';
require_once '../../includes/app_config.php'; // Memuat variabel $app_data untuk KOP dan Tanda Tangan

/** @var PDO $pdo */

$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// 1. PROSES SIMPAN REFLEKSI
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_refleksi'])) {
    $refleksi_teks = trim($_POST['refleksi_teks']);

    try {
        // Karena tb_pengaturan berisi 1 baris pengaturan global, kita update langsung
        $stmt = $pdo->prepare("UPDATE tb_pengaturan SET refleksi_laporan = :nilai");
        $stmt->execute([':nilai' => $refleksi_teks]);
        $pesan_sukses = "Catatan refleksi kinerja berhasil diperbarui.";
    } catch (Exception $e) {
        $pesan_error = "Gagal menyimpan refleksi: " . $e->getMessage();
    }
}

// ==========================================
// 2. AMBIL DATA LAPORAN & REFLEKSI
// ==========================================
$filter_bidang = $_GET['bidang'] ?? 'Semua';

try {
    // Ambil data Refleksi dari kolom refleksi_laporan di tb_pengaturan
    $stmtRefleksi = $pdo->query("SELECT refleksi_laporan FROM tb_pengaturan LIMIT 1");
    $refleksi_tersimpan = $stmtRefleksi->fetchColumn();

    // Pastikan jika nilai false/null, kita ubah jadi string kosong
    if (empty($refleksi_tersimpan)) {
        $refleksi_tersimpan = '';
    }

    // Ambil daftar bidang unik untuk filter
    $stmtBidang = $pdo->query("SELECT DISTINCT bidang FROM tb_indikator WHERE bidang IS NOT NULL AND bidang != '' ORDER BY bidang ASC");
    $listBidang = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);

    // Query rekapitulasi kinerja per bidang (termasuk kalkulasi target tercapai dari realisasi)
    $sqlRekap = "SELECT 
                    i.bidang, 
                    COUNT(i.id_indikator) as total_indikator,
                    SUM(CASE WHEN p.id_pelaporan IS NOT NULL THEN 1 ELSE 0 END) as total_laporan,
                    AVG(COALESCE(p.capaian, 0)) as rata_capaian,
                    SUM(
                        CASE 
                            WHEN p.realisasi IS NOT NULL AND p.realisasi != '' AND i.jenis_target = '≥' AND CAST(p.realisasi AS DECIMAL(10,2)) >= CAST(i.target AS DECIMAL(10,2)) THEN 1
                            WHEN p.realisasi IS NOT NULL AND p.realisasi != '' AND i.jenis_target = '≤' AND CAST(p.realisasi AS DECIMAL(10,2)) <= CAST(i.target AS DECIMAL(10,2)) THEN 1
                            WHEN p.realisasi IS NOT NULL AND p.realisasi != '' AND i.jenis_target = '=' AND CAST(p.realisasi AS DECIMAL(10,2)) = CAST(i.target AS DECIMAL(10,2)) THEN 1
                            ELSE 0 
                        END
                    ) as target_tercapai
                 FROM tb_indikator i
                 LEFT JOIN (
                    SELECT p1.id_pelaporan, p1.id_indikator, p1.capaian, p1.realisasi
                    FROM tb_pelaporan p1
                    INNER JOIN (
                        SELECT id_indikator, MAX(id_pelaporan) as max_id 
                        FROM tb_pelaporan 
                        GROUP BY id_indikator
                    ) p2 ON p1.id_pelaporan = p2.max_id
                 ) p ON i.id_indikator = p.id_indikator";

    if ($filter_bidang !== 'Semua') {
        $sqlRekap .= " WHERE i.bidang = :bidang";
    }

    $sqlRekap .= " GROUP BY i.bidang ORDER BY i.bidang ASC";

    $stmt = $pdo->prepare($sqlRekap);
    if ($filter_bidang !== 'Semua') {
        $stmt->execute([':bidang' => $filter_bidang]);
    } else {
        $stmt->execute();
    }
    $rekapData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rekapData = [];
    $listBidang = [];
    $refleksi_tersimpan = '';
}
?>

<!-- CSS Tambahan untuk Menyembunyikan Navbar, Sidebar, dan Mengatur Print Warna -->
<style>
    @media print {
        /* Sembunyikan elemen navigasi utama layout dashboard */
        header, nav, aside, .print\:hidden {
            display: none !important;
        }

        /* Pastikan kontainer utama memenuhi halaman cetak tanpa batasan scroll */
        html, body, main {
            background-color: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Memastikan container pembungkus utama juga tidak menyebabkan scroll */
        .h-screen, .overflow-y-auto {
            height: auto !important;
            overflow: visible !important;
        }

        /* Hilangkan bayangan dan border luar agar bersih */
        .print\:shadow-none { box-shadow: none !important; }
        .print\:ring-0 { --tw-ring-shadow: 0 0 #000 !important; box-shadow: none !important; }
        .print\:border-transparent { border-color: transparent !important; }

        /* Memastikan border tabel tidak terpotong saat dicetak */
        table {
            border-collapse: collapse !important;
            width: 100% !important;
        }

        th, td {
            border: 1px solid #94a3b8 !important; /* Warna slate-400 */
        }
    }
</style>

<div x-data="{ modalRefleksi: false }">

    <!-- Bagian Konten & Aksi (Hilang saat dicetak berkat print:hidden) -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
        <div>
            <h2 class="text-2xl font-display font-bold text-slate-900">Laporan & Cetak Dokumen</h2>
            <p class="text-sm text-slate-500 mt-1">Unduh atau cetak laporan resmi penjaminan mutu SMK Tiara Nusa</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Form Filter Bidang -->
            <form method="GET" class="flex items-center gap-2">
                <select name="bidang" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary bg-white shadow-sm">
                    <option value="Semua">Semua Bidang</option>
                    <?php foreach ($listBidang as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $filter_bidang === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <button @click="modalRefleksi = true" class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors shrink-0">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Tulis Refleksi
            </button>

            <a href="laporan_excel.php" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors shrink-0">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Excel
            </a>

            <button onclick="window.print()" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 transition-colors shrink-0">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- Notifikasi Pop-up -->
    <?php if (!empty($pesan_sukses)): ?>
        <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center shadow-sm print:hidden">
            <svg class="w-5 h-5 mr-2 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?= $pesan_sukses ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 flex items-center shadow-sm print:hidden">
            <svg class="w-5 h-5 mr-2 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?= $pesan_error ?></span>
        </div>
    <?php endif; ?>

    <!-- Dokumen Laporan Resmi (Area yang akan dicetak) -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8 md:p-12 max-w-4xl mx-auto print:shadow-none print:ring-0 print:p-0 print:w-full print:border-transparent">

        <!-- KOP Surat Sesuai Format -->
        <div class="border-b-2 border-slate-900 mb-[2px] pb-3 print:pb-2">
            <div class="flex items-center justify-between">
                <!-- Logo Kiri -->
                <div class="w-28 shrink-0 flex justify-center">
                    <img src="../../assets/img/<?= htmlspecialchars($app_data['logo'] ?? 'logo.png') ?>" alt="Logo Sekolah" class="h-24 w-auto object-contain" onerror="this.style.display='none'">
                </div>

                <!-- Teks Tengah Kop -->
                <div class="flex-1 text-center px-4">
                    <h3 class="text-xl font-bold text-blue-900 uppercase tracking-wide" style="color: #1e3a8a;">SEKOLAH MENENGAH KEJURUAN</h3>
                    <h1 class="text-4xl font-serif font-black text-red-600 uppercase tracking-widest mt-1 mb-2" style="font-family: 'Times New Roman', Times, serif; color: #dc2626;">
                        TIARA NUSA
                    </h1>

                    <p class="text-xs font-semibold text-slate-800">Kompetensi Keahlian :</p>
                    <p class="text-xs font-bold text-blue-900 uppercase leading-tight" style="color: #1e3a8a;">
                        <?= htmlspecialchars($app_data['kompetensi_keahlian_1'] ?? 'TEKNIK JARINGAN KOMPUTER DAN TELEKOMUNIKASI') ?><br>
                        <?= htmlspecialchars($app_data['kompetensi_keahlian_2'] ?? 'MANAJEMEN PERKANTORAN DAN LAYANAN BISNIS') ?>
                    </p>
                </div>

                <!-- Spacer Kanan agar Teks Tengah Tetap Seimbang -->
                <div class="w-28 shrink-0"></div>
            </div>
        </div>

        <!-- Garis Ganda Bawah & Alamat -->
        <div class="border-b border-slate-900 pb-1 mb-8 text-center print:mb-6">
            <p class="text-[9px] sm:text-[10px] text-slate-800">
                <?= htmlspecialchars($app_data['alamat_sekolah'] ?? 'Jl. Situ Jatijajar No. 04 Rt 007/003 Kel. Jatijajar Tapos Depok') ?>
                Telp : <?= htmlspecialchars($app_data['no_telepon'] ?? '021-87919619') ?>
                Email : <span class="text-blue-600"><?= htmlspecialchars($app_data['email_sekolah'] ?? 'smktiaranusa@yahoo.com') ?></span>
                Website : <span class="text-blue-600"><?= htmlspecialchars($app_data['website'] ?? 'smktiaranusa.sch.id') ?></span>
            </p>
        </div>

        <!-- Judul Laporan -->
        <div class="text-center mb-8">
            <h2 class="text-lg font-bold text-slate-900 uppercase decoration-2 underline-offset-4">LAPORAN REKAPITULASI KINERJA SEKOLAH</h2>
            <p class="text-sm text-slate-700 mt-2">Periode Tahun Ajaran <?= htmlspecialchars($app_data['tahun_ajaran'] ?? (date('Y') . '/' . date('Y', strtotime('+1 year')))) ?></p>
        </div>

        <!-- Konten Laporan -->
        <div class="space-y-6 text-sm text-slate-800">
            <p class="leading-relaxed text-justify">
                Laporan resmi ini diterbitkan secara otomatis oleh sistem <strong>Humanis Insight <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?></strong> berdasarkan evaluasi dan pelaporan berbasis data dari masing-masing penanggung jawab bidang struktural.
            </p>

            <!-- Tabel Rekapitulasi Data -->
            <table class="w-full border-collapse border border-slate-400 mt-4 text-left">
                <thead>
                    <tr class="bg-slate-100 text-slate-900">
                        <th class="border border-slate-400 p-2.5 text-xs text-center w-12 print:border-slate-800">No</th>
                        <th class="border border-slate-400 p-2.5 text-xs print:border-slate-800">Bidang Struktural</th>
                        <th class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800">Total Indikator</th>
                        <th class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800">Laporan Masuk</th>
                        <th class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800">Rata-rata Progres</th>
                        <th class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800">Target Tercapai</th>
                        <th class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800">Status Mutu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rekapData)): ?>
                        <?php $no = 1;
                        foreach ($rekapData as $row): ?>
                            <?php $avg = round((float)$row['rata_capaian'], 2); ?>
                            <tr>
                                <td class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800"><?= $no++ ?></td>
                                <td class="border border-slate-400 p-2.5 text-xs font-semibold text-slate-900 print:border-slate-800"><?= htmlspecialchars($row['bidang']) ?></td>
                                <td class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800"><?= $row['total_indikator'] ?> KPI</td>
                                <td class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800"><?= $row['total_laporan'] ?> Laporan</td>
                                <td class="border border-slate-400 p-2.5 text-xs text-center font-bold text-slate-900 print:border-slate-800"><?= $avg ?>%</td>
                                <td class="border border-slate-400 p-2.5 text-xs text-center print:border-slate-800 text-emerald-700 font-bold">
                                    <?= $row['target_tercapai'] ?> <span class="text-slate-500 font-normal">dari <?= $row['total_indikator'] ?></span>
                                </td>
                                <td class="border border-slate-400 p-2.5 text-xs text-center font-semibold print:border-slate-800">
                                    <?= $avg >= 85 ? 'Optimal' : ($avg >= 50 ? 'Cukup' : 'Perlu Atensi') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="border border-slate-400 p-4 text-center text-slate-500 print:border-slate-800">Belum ada data rekapitulasi bidang yang tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- BAGIAN REFLEKSI KEPALA SEKOLAH (Hanya muncul jika sudah diisi) -->
            <?php if (!empty($refleksi_tersimpan)): ?>
                <div class="mt-8 border border-slate-300 p-5 rounded-lg bg-slate-50 print:bg-transparent print:border-none print:p-0">
                    <h4 class="text-sm font-bold text-slate-900 mb-2 uppercase underline underline-offset-4 decoration-2 print:mb-3">Catatan Refleksi & Evaluasi Kepala Sekolah:</h4>
                    <div class="text-sm text-slate-800 leading-relaxed text-justify whitespace-pre-wrap"><?= htmlspecialchars($refleksi_tersimpan) ?></div>
                </div>
            <?php endif; ?>

            <!-- Paragraf Penutup / Kesimpulan -->
            <p class="text-sm text-slate-800 leading-relaxed pt-4 text-justify">
                Demikian laporan rekapitulasi kinerja ini disusun guna menjadi acuan strategis pimpinan dalam pengambilan keputusan dan perbaikan berkelanjutan mutu pendidikan di lingkungan <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?>.
            </p>

            <!-- Tanda Tangan Dinamis (Hanya Kepala Sekolah, Rata Kanan) -->
            <div class="mt-6 flex justify-end pt-2 page-break-inside-avoid">
                <div class="text-center w-64">
                    <?php
                    // Array nama bulan dalam bahasa Indonesia
                    $bulanIndo = [
                        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    // Format: Tanggal Bulan(Indo) Tahun
                    $tanggal_cetak = date('d') . ' ' . $bulanIndo[(int)date('m')] . ' ' . date('Y');
                    ?>
                    <p class="text-sm text-slate-900">Depok, <?= $tanggal_cetak ?></p>
                    <p class="text-sm font-bold text-slate-900 mt-1">Kepala <?= htmlspecialchars($app_data['nama_sekolah'] ?? 'SMK Tiara Nusa') ?></p>
                    <div class="h-24"></div>
                    <p class="text-sm font-bold text-slate-900">
                        <span class="border-b border-slate-900 pb-px inline-block min-w-[150px]">
                            <?= !empty($app_data['kepala_sekolah']) ? htmlspecialchars($app_data['kepala_sekolah']) : '' ?>
                        </span>
                    </p>
                    <p class="text-xs text-slate-900 mt-1">
                        NRKS. <?= !empty($app_data['nrks']) ? htmlspecialchars($app_data['nrks']) : '________________' ?>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TULIS REFLEKSI -->
    <div x-show="modalRefleksi" class="fixed inset-0 z-50 overflow-y-auto print:hidden" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalRefleksi" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalRefleksi = false"></div>

            <div x-show="modalRefleksi" x-transition class="relative inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-2">Tulis Catatan Refleksi Kinerja</h3>
                <p class="text-xs text-slate-500 mb-4">Catatan ini akan tercetak sebagai Executive Summary pada laporan penjaminan mutu periode ini.</p>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="simpan_refleksi" value="1">

                    <div>
                        <textarea name="refleksi_teks" rows="8" class="w-full rounded-xl border border-slate-300 p-4 text-sm focus:ring-primary focus:border-primary shadow-sm" placeholder="Tuliskan evaluasi, pandangan, atau catatan strategis Bapak/Ibu Kepala Sekolah di sini... (Biarkan kosong jika ingin menyembunyikan bagian refleksi)"><?= htmlspecialchars($refleksi_tersimpan) ?></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="modalRefleksi = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 shadow-sm">Simpan Catatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/kepsek_footer.php'; ?>