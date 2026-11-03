<?php

/**
 * Halaman Persetujuan & Tindak Lanjut oleh Kepala Sekolah 
 * Diperbarui: Penyesuaian Notifikasi Berdasarkan Skema Database Asli
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */

$pesan_sukses = '';
$pesan_error = '';

// Proses Update Arahan & Status Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pelaporan'])) {
    try {
        // 1. Ambil data laporan sebelum diupdate (untuk keperluan notifikasi)
        $stmtCek = $pdo->prepare("SELECT nama_pic, bidang FROM tb_pelaporan WHERE id_pelaporan = :id");
        $stmtCek->execute([':id' => $_POST['id_pelaporan']]);
        $dataLama = $stmtCek->fetch(PDO::FETCH_ASSOC);

        // 2. Lakukan Update Keputusan
        $stmtUpdate = $pdo->prepare("UPDATE tb_pelaporan SET 
                                        arahan_kepsek = :arahan, 
                                        status_verifikasi = :status 
                                     WHERE id_pelaporan = :id");
        $stmtUpdate->execute([
            ':arahan' => trim($_POST['arahan_kepsek']),
            ':status' => $_POST['status_verifikasi'],
            ':id'     => $_POST['id_pelaporan']
        ]);

        // 3. Eksekusi Notifikasi Berdasarkan Skema Asli
        if ($dataLama) {
            $nama_pic = trim($dataLama['nama_pic']);
            $bidang = $dataLama['bidang'];
            $status_baru = $_POST['status_verifikasi'];

            // Cari data user PIC untuk mendapatkan username-nya
            $stmtUser = $pdo->prepare("SELECT username FROM tb_users WHERE nama_lengkap = :nama LIMIT 1");
            $stmtUser->execute([':nama' => $nama_pic]);
            $userTarget = $stmtUser->fetch(PDO::FETCH_ASSOC);

            $kata_status = '';
            if ($status_baru === 'Disetujui') {
                $kata_status = 'DISETUJUI';
            } elseif ($status_baru === 'Ditolak') {
                $kata_status = 'DITOLAK (Dikembalikan)';
            } else {
                $kata_status = 'direview';
            }

            $pesanNotif = "Laporan kinerja Anda (Bidang $bidang) telah $kata_status oleh Kepala Sekolah. Silakan cek detail arahan.";

            try {
                // Skema tabel: id_notifikasi, nama_penerima, pesan, link, dibaca, tanggal
                $stmtNotif = $pdo->prepare("INSERT INTO tb_notifikasi (nama_penerima, pesan, link, dibaca, tanggal) VALUES (:penerima, :pesan, 'pelaporan.php', 0, NOW())");
                
                // INSERT 1: Gunakan Username (Paling Sering Digunakan di Session Login)
                if ($userTarget && !empty($userTarget['username'])) {
                    $stmtNotif->execute([
                        ':penerima' => $userTarget['username'],
                        ':pesan'    => $pesanNotif
                    ]);
                }

                // INSERT 2: Gunakan Nama Lengkap (Sebagai Fallback/Jaga-jaga)
                // Kita kirim 2 versi agar dijamin tampil di lonceng PIC
                $stmtNotif->execute([
                    ':penerima' => $nama_pic,
                    ':pesan'    => $pesanNotif
                ]);

            } catch (Exception $e) {
                // Tangkap jika insert gagal dan tampilkan pesan aslinya
                error_log("Gagal Insert Notifikasi: " . $e->getMessage());
            }
        }

        $pesan_sukses = "Laporan berhasil diverifikasi dan arahan tersimpan!";
    } catch (Exception $e) {
        $pesan_error = "Gagal menyimpan: " . $e->getMessage();
    }
}

// Konfigurasi Parameter Pencarian, Filter, & Pagination
$search        = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_bidang = $_GET['bidang'] ?? '';
$limit         = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$page          = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset        = ($page - 1) * $limit;

try {
    $whereClauses = ["1=1"];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(i.indikator LIKE :search OR p.nama_pic LIKE :search OR p.bidang LIKE :search OR p.refleksi_kendala LIKE :search OR p.nilai_humanis LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if (!empty($filter_status)) {
        $whereClauses[] = "p.status_verifikasi = :status";
        $params[':status'] = $filter_status;
    }

    if (!empty($filter_bidang)) {
        $whereClauses[] = "p.bidang = :bidang";
        $params[':bidang'] = $filter_bidang;
    }

    $whereSql = implode(' AND ', $whereClauses);

    // Hitung total data untuk pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_pelaporan p JOIN tb_indikator i ON p.id_indikator = i.id_indikator WHERE $whereSql");
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // Ambil data laporan (dengan jenis_target untuk realisasi)
    $sql = "SELECT p.*, i.indikator, i.target, i.satuan, i.jenis_target 
            FROM tb_pelaporan p 
            JOIN tb_indikator i ON p.id_indikator = i.id_indikator 
            WHERE $whereSql 
            ORDER BY 
                CASE 
                    WHEN p.status_verifikasi = 'Menunggu Persetujuan' THEN 1 
                    ELSE 2 
                END ASC, 
                p.id_pelaporan DESC 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $listLaporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar bidang unik untuk dropdown filter
    $stmtBidang = $pdo->query("SELECT DISTINCT bidang FROM tb_indikator WHERE bidang IS NOT NULL AND bidang != '' ORDER BY bidang ASC");
    $listBidang = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $listLaporan = [];
    $listBidang = [];
    $totalPages = 1;
}
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-display font-bold text-slate-900">Persetujuan & Tindak Lanjut</h2>
        <p class="text-sm text-slate-500 mt-1">Daftar laporan kinerja bawahan yang perlu Anda tinjau dan berikan arahan.</p>
    </div>
</div>

<?php if ($pesan_sukses): ?>
    <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <?= $pesan_sukses ?>
    </div>
<?php endif; ?>

<?php if ($pesan_error): ?>
    <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm flex items-center gap-2">
        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <?= $pesan_error ?>
    </div>
<?php endif; ?>

<!-- Panel Filter, Search, & Limit -->
<div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm ring-1 ring-slate-200 mb-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pencarian</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari PIC, bidang, atau indikator..." class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
        </div>

        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Status Verifikasi</label>
            <select name="status" class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                <option value="">-- Semua Status --</option>
                <option value="Menunggu Persetujuan" <?= $filter_status === 'Menunggu Persetujuan' ? 'selected' : '' ?>>Menunggu Persetujuan</option>
                <option value="Disetujui" <?= $filter_status === 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
                <option value="Ditolak" <?= $filter_status === 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Bidang</label>
            <select name="bidang" class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                <option value="">-- Semua Bidang --</option>
                <?php foreach ($listBidang as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= $filter_bidang === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sm:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tampil</label>
            <select name="limit" class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
            </select>
        </div>

        <div class="sm:col-span-2 flex items-center gap-2">
            <button type="submit" class="w-full bg-slate-900 text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-colors">Filter</button>
            <a href="tindak_lanjut.php" class="bg-slate-100 text-slate-600 text-sm font-semibold px-3 py-2.5 rounded-xl hover:bg-slate-200 transition-colors text-center" title="Reset Filter">Reset</a>
        </div>
    </form>
</div>

<!-- Area Tabel dan Modal dengan Alpine.js -->
<div x-data="{ modalOpen: false, lap: {} }" class="mb-6">
    <!-- Daftar Laporan (Tabel View) -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Pelapor & Bidang</th>
                        <th class="px-4 py-4 w-1/3">Indikator Kinerja</th>
                        <th class="px-4 py-4 text-center">Realisasi</th>
                        <th class="px-4 py-4 text-center">Status</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (!empty($listLaporan)): ?>
                        <?php foreach ($listLaporan as $lap): ?>
                            <?php 
                                // Logic Ketercapaian
                                $real = floatval($lap['realisasi'] ?? 0);
                                $tgt = floatval($lap['target']);
                                $jenis = $lap['jenis_target'];
                                
                                $tercapai = false;
                                if ($jenis === '≥' && $real >= $tgt) $tercapai = true;
                                if ($jenis === '≤' && $real <= $tgt) $tercapai = true;
                                if ($jenis === '=' && $real == $tgt) $tercapai = true;
                                
                                $tercapai_badge = $tercapai ? '<span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">Target Tercapai</span>' : '<span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold">Tidak Tercapai</span>';
                                
                                if (!isset($lap['realisasi']) || trim($lap['realisasi']) === '') {
                                    $tercapai_badge = '<span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold">Belum Diinput</span>';
                                }

                                // Siapkan JSON untuk Modal
                                $lapJson = htmlspecialchars(json_encode([
                                    'id' => $lap['id_pelaporan'],
                                    'pic' => $lap['nama_pic'],
                                    'bidang' => $lap['bidang'],
                                    'indikator' => $lap['indikator'],
                                    'humanis' => $lap['nilai_humanis'] ?? '-',
                                    'capaian' => $lap['capaian'],
                                    'realisasi_text' => (isset($lap['realisasi']) && trim($lap['realisasi']) !== '') ? floatval($lap['realisasi']) . ' ' . htmlspecialchars($lap['satuan']) : 'Belum diinput',
                                    'target_text' => htmlspecialchars($jenis . ' ' . $tgt . ' ' . $lap['satuan']),
                                    'tercapai_html' => $tercapai_badge,
                                    'refleksi' => $lap['refleksi_kendala'] ?: 'Tidak ada catatan refleksi.',
                                    'rtl' => $lap['rencana_tindak_lanjut'] ?: 'Tidak ada rencana tindak lanjut.',
                                    'gdrive' => $lap['bukti_gdrive'],
                                    'status' => $lap['status_verifikasi'],
                                    'arahan' => $lap['arahan_kepsek'] ?? ''
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors <?= $lap['status_verifikasi'] === 'Menunggu Persetujuan' ? 'bg-amber-50/30' : '' ?>">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($lap['nama_pic']) ?></div>
                                    <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($lap['bidang']) ?></div>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-700 font-medium">
                                    <?= htmlspecialchars($lap['indikator']) ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php if (isset($lap['realisasi']) && trim($lap['realisasi']) !== ''): ?>
                                        <div class="font-bold text-slate-900"><?= floatval($lap['realisasi']) . ' ' . htmlspecialchars($lap['satuan']) ?></div>
                                        <?= $tercapai_badge ?>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">0 (Progres: <?= $lap['capaian'] ?>%)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php
                                    $statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($lap['status_verifikasi'] === 'Disetujui') $statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($lap['status_verifikasi'] === 'Ditolak') $statusBadge = 'bg-red-50 text-red-700 border-red-200';
                                    ?>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold border <?= $statusBadge ?>">
                                        <?= htmlspecialchars($lap['status_verifikasi']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button @click="lap = <?= $lapJson ?>; modalOpen = true;" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Review
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Tidak ada laporan kinerja yang sesuai dengan pencarian atau filter saat ini.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL REVIEW KEPALA SEKOLAH -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Background overlay -->
            <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalOpen = false"></div>

            <!-- Panel Modal -->
            <div x-show="modalOpen" x-transition class="relative inline-block w-full max-w-4xl overflow-hidden text-left align-middle transition-all transform bg-slate-50 shadow-2xl rounded-2xl">
                
                <!-- Header Modal -->
                <div class="bg-white px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-display font-bold text-slate-900">Review Laporan Kinerja</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Detail pelaporan dan form persetujuan.</p>
                    </div>
                    <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 p-2 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body Modal (Grid 2 Kolom) -->
                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Kiri: Detail Informasi Laporan -->
                    <div class="lg:col-span-2 space-y-5">
                        
                        <!-- Info Utama -->
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded" x-text="lap.bidang"></span>
                                <span class="text-sm font-medium text-slate-700">Pelapor: <strong class="text-slate-900" x-text="lap.pic"></strong></span>
                            </div>
                            <h4 class="text-base font-bold text-slate-900 mb-2 leading-snug" x-text="lap.indikator"></h4>
                            <div class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded border border-purple-100">
                                Integrasi Nilai HUMANIS: <span x-text="lap.humanis"></span>
                            </div>
                        </div>

                        <!-- Info Angka (Capaian & Realisasi) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
                                <span class="block text-xs font-bold text-slate-500 uppercase mb-1">Progres Tahapan</span>
                                <div class="text-2xl font-black text-blue-600 mb-1" x-text="lap.capaian + '%'"></div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-blue-600 h-1.5 rounded-full" :style="'width: ' + lap.capaian + '%'"></div>
                                </div>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center relative overflow-hidden">
                                <span class="block text-xs font-bold text-slate-500 uppercase mb-1">Realisasi Aktual</span>
                                <div class="text-xl font-black text-slate-900 mb-1" x-text="lap.realisasi_text"></div>
                                <div class="text-[10px] text-slate-500 mb-1">Target: <span x-text="lap.target_text"></span></div>
                                <div x-html="lap.tercapai_html"></div>
                            </div>
                        </div>

                        <!-- Refleksi & RTL -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                                <span class="block text-xs font-bold text-slate-700 uppercase mb-1">Refleksi & Kendala</span>
                                <p class="text-sm text-slate-600 whitespace-pre-line" x-text="lap.refleksi"></p>
                            </div>
                            <div class="p-4 bg-slate-50/50">
                                <span class="block text-xs font-bold text-slate-700 uppercase mb-1">Rencana Tindak Lanjut</span>
                                <p class="text-sm text-slate-600 whitespace-pre-line" x-text="lap.rtl"></p>
                            </div>
                        </div>

                        <!-- Bukti Dokumen -->
                        <div>
                            <template x-if="lap.gdrive">
                                <a :href="lap.gdrive" target="_blank" class="inline-flex items-center justify-center w-full text-sm font-bold text-blue-700 bg-blue-100 hover:bg-blue-600 hover:text-white px-4 py-3 rounded-xl transition-colors shadow-sm">
                                    Buka File Bukti Pendukung di Google Drive
                                </a>
                            </template>
                            <template x-if="!lap.gdrive">
                                <div class="text-center p-3 bg-slate-200 text-slate-500 text-sm font-medium rounded-xl">
                                    Tidak melampirkan file bukti
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Kanan: Form Arahan & Keputusan -->
                    <div class="lg:col-span-1">
                        <div class="bg-slate-900 rounded-2xl p-5 shadow-xl text-white sticky top-6">
                            <h4 class="font-display font-bold text-lg mb-4 text-blue-400">Form Tindak Lanjut</h4>
                            
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="id_pelaporan" :value="lap.id">

                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1.5 tracking-wider">Status Persetujuan</label>
                                    <select name="status_verifikasi" x-model="lap.status" class="w-full rounded-lg border-slate-700 bg-slate-800 text-white text-sm p-2.5 focus:ring-primary focus:border-primary">
                                        <option value="Menunggu Persetujuan">🟡 Menunggu</option>
                                        <option value="Disetujui">🟢 Disetujui (Selesai)</option>
                                        <option value="Ditolak">🔴 Ditolak (Perlu Diperbaiki)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1.5 tracking-wider">Arahan / Catatan (Opsional)</label>
                                    <textarea name="arahan_kepsek" x-model="lap.arahan" rows="5" class="w-full rounded-lg border-slate-700 bg-slate-800 text-white text-sm p-2.5 focus:ring-primary focus:border-primary placeholder:text-slate-500" placeholder="Ketik apresiasi, evaluasi, atau instruksi perbaikan di sini..."></textarea>
                                </div>

                                <button type="submit" class="w-full bg-primary text-white text-sm font-bold py-3 px-4 rounded-xl hover:bg-blue-600 transition-colors shadow-md mt-4">
                                    Simpan Keputusan
                                </button>
                                
                                <p class="text-[10px] text-slate-500 text-center mt-3 leading-tight">Sistem otomatis mengirim notifikasi ke pembuat laporan setelah Anda menekan tombol simpan.</p>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pagination Bar -->
<?php if ($totalPages > 1): ?>
    <div class="bg-white rounded-2xl p-4 shadow-sm ring-1 ring-slate-200 flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500">Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong></span>
        <div class="flex items-center gap-1.5">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&bidang=<?= urlencode($filter_bidang) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-100">&laquo; Prev</a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            if ($startPage > 1) echo '<span class="px-2 text-slate-400">...</span>';

            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&bidang=<?= urlencode($filter_bidang) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg border <?= $i === $page ? 'bg-primary text-white border-primary shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages) echo '<span class="px-2 text-slate-400">...</span>'; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&bidang=<?= urlencode($filter_bidang) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-100">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../../includes/kepsek_footer.php'; ?>