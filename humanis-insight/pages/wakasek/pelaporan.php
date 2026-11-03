<?php

/**
 * Halaman Pelaporan Kinerja Pribadi untuk Wakil Kepala Sekolah
 * Diperbarui: Mendukung Input Realisasi Angka Aktual secara Dinamis
 */
require_once '../../includes/wakasek_header.php';
require_once '../../includes/wakasek_sidebar.php';
require_once '../../includes/wakasek_navbar.php';
require_once '../../config/database.php';
require_once '../../actions/upload_gdrive.php';

/** @var PDO $pdo */
$nama_saya = $_SESSION['nama'] ?? '';

// Ambil pesan flash dari session (Pola PRG)
$pesan_sukses = $_SESSION['flash_sukses'] ?? '';
$pesan_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_sukses'], $_SESSION['flash_error']);

// Proses Hapus Laporan
if (isset($_GET['aksi_hapus']) && $_GET['aksi_hapus'] === 'hapus' && isset($_GET['id'])) {
    $id_pelaporan = intval($_GET['id']);
    try {
        $stmtCek = $pdo->prepare("SELECT * FROM tb_pelaporan WHERE id_pelaporan = :id AND nama_pic = :nama");
        $stmtCek->execute([':id' => $id_pelaporan, ':nama' => $nama_saya]);
        $lapData = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if ($lapData) {
            if ($lapData['status_verifikasi'] === 'Menunggu Persetujuan') {
                if (!empty($lapData['bukti_gdrive'])) {
                    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $lapData['bukti_gdrive'], $matches)) {
                        hapusFileGoogleDrive($matches[1]);
                    }
                }
                $stmtDel = $pdo->prepare("DELETE FROM tb_pelaporan WHERE id_pelaporan = :id");
                $stmtDel->execute([':id' => $id_pelaporan]);

                $_SESSION['flash_sukses'] = "Laporan dan file bukti di Google Drive berhasil dihapus.";
                echo "<script>window.location.href='pelaporan.php';</script>";
                exit;
            } else {
                throw new Exception("Laporan tidak dapat dihapus karena sudah divalidasi oleh Kepala Sekolah.");
            }
        } else {
            throw new Exception("Laporan tidak ditemukan.");
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Gagal menghapus laporan: " . $e->getMessage();
        echo "<script>window.location.href='pelaporan.php';</script>";
        exit;
    }
}

// Proses Simpan/Update Laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && ($_POST['aksi'] === 'tambah_laporan' || $_POST['aksi'] === 'update_laporan')) {
    $id_indikator          = $_POST['id_indikator'] ?? null;
    $capaian               = floatval($_POST['capaian']);
    $realisasi             = floatval($_POST['realisasi'] ?? 0); // Menangkap nilai realisasi
    $nilai_humanis         = trim($_POST['nilai_humanis']); // Kaitan Nilai HUMANIS
    $refleksi_kendala      = trim($_POST['refleksi_kendala']);
    $rencana_tindak_lanjut = trim($_POST['rencana_tindak_lanjut']);

    try {
        $bidang = 'Umum';
        if ($id_indikator) {
            $stmtInd = $pdo->prepare("SELECT bidang FROM tb_indikator WHERE id_indikator = :id");
            $stmtInd->execute([':id' => $id_indikator]);
            $indData = $stmtInd->fetch(PDO::FETCH_ASSOC);
            if ($indData) {
                $bidang = $indData['bidang'];
            }
        }

        $is_success = false;
        $jenis_aksi = '';
        $msg_sukses_temp = '';

        // Jika aksi adalah update (kirim ulang / perbaiki)
        if ($_POST['aksi'] === 'update_laporan') {
            $id_pelaporan = $_POST['id_pelaporan'];
            $link_gdrive_baru = null;

            if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === UPLOAD_ERR_OK) {
                $stmtOld = $pdo->prepare("SELECT bukti_gdrive FROM tb_pelaporan WHERE id_pelaporan = :id");
                $stmtOld->execute([':id' => $id_pelaporan]);
                $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

                if ($oldData && !empty($oldData['bukti_gdrive'])) {
                    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $oldData['bukti_gdrive'], $matches)) {
                        hapusFileGoogleDrive($matches[1]);
                    }
                }

                $fileTmpPath = $_FILES['file_bukti']['tmp_name'];
                $fileName    = 'Bukti_Update_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\-_.]/", "_", $_FILES['file_bukti']['name']);
                $link_gdrive_baru = uploadToGoogleDrive($fileTmpPath, $fileName);
            }

            if ($link_gdrive_baru) {
                $stmt = $pdo->prepare("UPDATE tb_pelaporan SET 
                                        capaian = :capaian, 
                                        realisasi = :realisasi,
                                        nilai_humanis = :nilai_humanis,
                                        bukti_gdrive = :gdrive,
                                        refleksi_kendala = :refleksi, 
                                        rencana_tindak_lanjut = :rtl, 
                                        status_verifikasi = 'Menunggu Persetujuan',
                                        arahan_kepsek = NULL
                                       WHERE id_pelaporan = :id AND nama_pic = :nama");
                $stmt->execute([
                    ':capaian'       => $capaian,
                    ':realisasi'     => $realisasi,
                    ':nilai_humanis' => $nilai_humanis,
                    ':gdrive'        => $link_gdrive_baru,
                    ':refleksi'      => $refleksi_kendala,
                    ':rtl'           => $rencana_tindak_lanjut,
                    ':id'            => $id_pelaporan,
                    ':nama'          => $nama_saya
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE tb_pelaporan SET 
                                        capaian = :capaian, 
                                        realisasi = :realisasi,
                                        nilai_humanis = :nilai_humanis,
                                        refleksi_kendala = :refleksi, 
                                        rencana_tindak_lanjut = :rtl, 
                                        status_verifikasi = 'Menunggu Persetujuan',
                                        arahan_kepsek = NULL
                                       WHERE id_pelaporan = :id AND nama_pic = :nama");
                $stmt->execute([
                    ':capaian'       => $capaian,
                    ':realisasi'     => $realisasi,
                    ':nilai_humanis' => $nilai_humanis,
                    ':refleksi'      => $refleksi_kendala,
                    ':rtl'           => $rencana_tindak_lanjut,
                    ':id'            => $id_pelaporan,
                    ':nama'          => $nama_saya
                ]);
            }
            $msg_sukses_temp = "Laporan berhasil diperbaiki dan dikirim ulang ke Kepala Sekolah.";
            $is_success = true;
            $jenis_aksi = 'diperbarui';
        } else {
            // Logika Insert Laporan Baru
            $stmtCekDup = $pdo->prepare("SELECT id_pelaporan FROM tb_pelaporan WHERE id_indikator = :id AND capaian = :capaian AND nama_pic = :nama");
            $stmtCekDup->execute([':id' => $id_indikator, ':capaian' => $capaian, ':nama' => $nama_saya]);

            if ($stmtCekDup->fetch()) {
                throw new Exception("Laporan dengan indikator dan capaian (tahap) yang sama sudah pernah diinput sebelumnya.");
            }

            $link_gdrive = '';
            if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['file_bukti']['tmp_name'];
                $fileName    = 'Bukti_' . time() . '_' . preg_replace("/[^a-zA-Z0-9\-_.]/", "_", $_FILES['file_bukti']['name']);
                $link_gdrive = uploadToGoogleDrive($fileTmpPath, $fileName);
            } else {
                throw new Exception("File bukti dukung wajib diunggah.");
            }

            $stmt = $pdo->prepare("INSERT INTO tb_pelaporan (id_indikator, nama_pic, bidang, capaian, realisasi, nilai_humanis, bukti_gdrive, refleksi_kendala, rencana_tindak_lanjut, status_verifikasi) 
                VALUES (:id_ind, :nama, :bidang, :capaian, :realisasi, :nilai_humanis, :gdrive, :refleksi, :rtl, 'Menunggu Persetujuan')");

            $stmt->execute([
                ':id_ind'        => $id_indikator,
                ':nama'          => $nama_saya,
                ':bidang'        => $bidang,
                ':capaian'       => $capaian,
                ':realisasi'     => $realisasi,
                ':nilai_humanis' => $nilai_humanis,
                ':gdrive'        => $link_gdrive,
                ':refleksi'      => $refleksi_kendala,
                ':rtl'           => $rencana_tindak_lanjut
            ]);

            $msg_sukses_temp = "Laporan dan file bukti berhasil diunggah ke Google Drive secara otomatis!";
            $is_success = true;
            $jenis_aksi = 'dikirim';
        }

        // ===================================================================================
        // JIKA BERHASIL SIMPAN/UPDATE, KIRIM NOTIFIKASI KE KEPALA SEKOLAH
        // ===================================================================================
        if ($is_success) {
            $pesan_notif = "Laporan kinerja baru saja $jenis_aksi oleh $nama_saya (Wakasek). Silakan ditinjau.";

            try {
                $stmtKepsek = $pdo->prepare("SELECT nama_lengkap FROM tb_users WHERE role = 'Kepala Sekolah'");
                $stmtKepsek->execute();
                $listKepsek = $stmtKepsek->fetchAll(PDO::FETCH_ASSOC);

                if ($listKepsek) {
                    $stmtNotifKepsek = $pdo->prepare("INSERT INTO tb_notifikasi (nama_penerima, pesan, link, tanggal, dibaca) VALUES (:nama, :pesan, 'tindak_lanjut.php', NOW(), 0)");
                    foreach ($listKepsek as $kepsek) {
                        $stmtNotifKepsek->execute([
                            ':nama' => $kepsek['nama_lengkap'],
                            ':pesan' => $pesan_notif
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Abaikan error notifikasi
            }

            // REDIRECT SETELAH SUKSES MENGGUNAKAN JAVASCRIPT
            $_SESSION['flash_sukses'] = $msg_sukses_temp;
            echo "<script>window.location.href='pelaporan.php';</script>";
            exit;
        }
        // ===================================================================================

    } catch (Exception $e) {
        // REDIRECT SETELAH ERROR MENGGUNAKAN JAVASCRIPT
        $_SESSION['flash_error'] = "Gagal memproses: " . $e->getMessage();
        echo "<script>window.location.href='pelaporan.php';</script>";
        exit;
    }
}

// Konfigurasi Parameter Pencarian, Filter, & Pagination
$search        = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_bidang = $_GET['bidang'] ?? '';
$limit         = 5;
$page          = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset        = ($page - 1) * $limit;

try {
    $stmtIndikator = $pdo->prepare("SELECT * FROM tb_indikator WHERE pic = :nama ORDER BY bidang ASC");
    $stmtIndikator->execute([':nama' => $nama_saya]);
    $listIndikator = $stmtIndikator->fetchAll(PDO::FETCH_ASSOC);

    $whereClauses = ["p.nama_pic = :nama"];
    $params = [':nama' => $nama_saya];

    if (!empty($search)) {
        $whereClauses[] = "(i.indikator LIKE :search OR p.refleksi_kendala LIKE :search OR p.rencana_tindak_lanjut LIKE :search OR p.nilai_humanis LIKE :search)";
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

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_pelaporan p JOIN tb_indikator i ON p.id_indikator = i.id_indikator WHERE $whereSql");
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // Ambil jenis_target juga untuk perhitungan ketercapaian target
    $sqlData = "SELECT p.*, i.indikator, i.target, i.satuan, i.jenis_target FROM tb_pelaporan p JOIN tb_indikator i ON p.id_indikator = i.id_indikator WHERE $whereSql ORDER BY p.id_pelaporan DESC LIMIT :limit OFFSET :offset";
    $stmtLaporan = $pdo->prepare($sqlData);

    foreach ($params as $key => $val) {
        $stmtLaporan->bindValue($key, $val);
    }
    $stmtLaporan->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtLaporan->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtLaporan->execute();
    $listLaporan = $stmtLaporan->fetchAll(PDO::FETCH_ASSOC);

    $stmtBidang = $pdo->prepare("SELECT DISTINCT bidang FROM tb_indikator WHERE pic = :nama");
    $stmtBidang->execute([':nama' => $nama_saya]);
    $listBidangFilter = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $listIndikator = [];
    $listLaporan = [];
    $totalPages = 1;
}
?>

<div x-data="{ 
    modalOpen: false, 
    editModal: false, 
    lap: {}, 
    isSubmitting: false,
    formIndikator: '',
    formRealisasi: '',
    masterIndikator: {
        <?php foreach ($listIndikator as $ind): ?>
            '<?= $ind['id_indikator'] ?>': { target: <?= $ind['target'] ?>, jenis: '<?= $ind['jenis_target'] ?>', satuan: '<?= htmlspecialchars($ind['satuan']) ?>' },
        <?php endforeach; ?>
    }
}">
    <!-- Header Halaman -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-display font-bold text-slate-900">Kinerja Saya</h2>
            <p class="text-sm text-slate-500 mt-1">Input progres tahapan dan realisasi kinerja Anda sebagai Wakil Kepala Sekolah.</p>
        </div>
        <button @click="modalOpen = true; formIndikator = ''; formRealisasi = '';" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 transition-colors shrink-0">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Input Laporan Baru
        </button>
    </div>

    <!-- Notifikasi -->
    <?php if (!empty($pesan_sukses)): ?>
        <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center shadow-sm">
            <span><?= $pesan_sukses ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 flex items-center shadow-sm">
            <span><?= $pesan_error ?></span>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm ring-1 ring-slate-200 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
            <div class="sm:col-span-5">
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pencarian</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari indikator, nilai HUMANIS, atau RTL..." class="w-full rounded-xl border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
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
                    <?php foreach ($listBidangFilter as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $filter_bidang === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full bg-slate-900 text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-colors">Filter</button>
                <a href="pelaporan.php" class="bg-slate-100 text-slate-600 text-sm font-semibold px-3 py-2.5 rounded-xl hover:bg-slate-200 transition-colors text-center" title="Reset Filter">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Laporan Wakasek -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Indikator Kinerja & Nilai HUMANIS</th>
                        <th class="px-4 py-4 text-center">Progres & Realisasi</th>
                        <th class="px-4 py-4">Bukti Dukung</th>
                        <th class="px-4 py-4">Refleksi Pelaksanaan</th>
                        <th class="px-4 py-4">Rencana Tindak Lanjut</th>
                        <th class="px-4 py-4">Arahan Kepala Sekolah</th>
                        <th class="px-4 py-4 text-center">Status</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($listLaporan) > 0): ?>
                        <?php foreach ($listLaporan as $lap): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-semibold text-slate-900 max-w-[250px]">
                                    <?= htmlspecialchars($lap['indikator']) ?>
                                    <div class="text-xs font-normal text-slate-500 mt-1">
                                        Bidang: <span class="font-medium text-slate-700"><?= htmlspecialchars($lap['bidang']) ?></span>
                                    </div>
                                    <?php if (!empty($lap['nilai_humanis'])): ?>
                                        <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-100">
                                            Kaitan Nilai: <?= htmlspecialchars($lap['nilai_humanis']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-4 py-4 text-center">
                                    <!-- Menampilkan Progres -->
                                    <div class="text-xs text-slate-500 mb-1">Progres Tahap: <span class="font-bold text-blue-600"><?= $lap['capaian'] ?>%</span></div>
                                    
                                    <!-- Menampilkan Realisasi Angka Aktual -->
                                    <?php if (isset($lap['realisasi']) && trim($lap['realisasi']) !== ''): ?>
                                        <div class="text-sm font-bold text-slate-900 mt-1">Realisasi: <?= floatval($lap['realisasi']) . ' ' . htmlspecialchars($lap['satuan']) ?></div>
                                        
                                        <?php 
                                            // Kalkulasi Ketercapaian Target
                                            $real = floatval($lap['realisasi']);
                                            $tgt = floatval($lap['target']);
                                            $jenis = $lap['jenis_target'];
                                            
                                            $status_tercapai = false;
                                            if ($jenis === '≥' && $real >= $tgt) $status_tercapai = true;
                                            if ($jenis === '≤' && $real <= $tgt) $status_tercapai = true;
                                            if ($jenis === '=' && $real == $tgt) $status_tercapai = true;
                                        ?>
                                        <?php if ($status_tercapai): ?>
                                            <div class="mt-1.5 inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Target Tercapai</div>
                                        <?php else: ?>
                                            <div class="mt-1.5 inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200" title="Target: <?= htmlspecialchars($jenis . ' ' . $tgt) ?>">Tidak Tercapai</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="mt-1.5 inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Belum diinput</div>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-4">
                                    <?php if (!empty($lap['bukti_gdrive'])): ?>
                                        <a href="<?= htmlspecialchars($lap['bukti_gdrive']) ?>" target="_blank" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:underline bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
                                            <svg class="w-3.5 h-3.5 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            GDrive
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Tidak ada file</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-600 max-w-[200px]"><?= htmlspecialchars($lap['refleksi_kendala'] ?? '-') ?></td>
                                <td class="px-4 py-4 text-xs text-slate-600 max-w-[200px]"><?= htmlspecialchars($lap['rencana_tindak_lanjut'] ?? '-') ?></td>
                                <td class="px-4 py-4 text-xs text-slate-600 max-w-[200px]">
                                    <?php if (!empty(trim($lap['arahan_kepsek'] ?? ''))): ?>
                                        <div class="bg-blue-50 p-2 rounded-lg border border-blue-100 text-blue-800">
                                            <span class="block font-bold text-[10px] uppercase text-blue-500 mb-0.5">Kepsek:</span>
                                            <?= htmlspecialchars($lap['arahan_kepsek']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">Menunggu review Kepala Sekolah</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php
                                    $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($lap['status_verifikasi'] === 'Disetujui') $badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($lap['status_verifikasi'] === 'Ditolak') $badgeColor = 'bg-red-50 text-red-700 border-red-200';
                                    ?>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border <?= $badgeColor ?>">
                                        <?= htmlspecialchars($lap['status_verifikasi']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php if ($lap['status_verifikasi'] === 'Menunggu Persetujuan' || $lap['status_verifikasi'] === 'Ditolak'): ?>
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button" @click='editModal = true; formIndikator = <?= $lap['id_indikator'] ?>; lap = {"id": <?= $lap['id_pelaporan'] ?>, "id_indikator": <?= $lap['id_indikator'] ?>, "capaian": <?= $lap['capaian'] ?>, "realisasi": <?= floatval($lap['realisasi'] ?? 0) ?>, "nilai_humanis": <?= json_encode($lap['nilai_humanis'] ?? '') ?>, "refleksi": <?= json_encode($lap['refleksi_kendala'] ?? '') ?>, "rtl": <?= json_encode($lap['rencana_tindak_lanjut'] ?? '') ?>}'
                                                class="px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                                <?= $lap['status_verifikasi'] === 'Ditolak' ? 'Perbaiki' : 'Edit' ?>
                                            </button>
                                            <?php if ($lap['status_verifikasi'] === 'Menunggu Persetujuan'): ?>
                                                <a href="pelaporan.php?aksi_hapus=hapus&id=<?= $lap['id_pelaporan'] ?>" onclick="return confirm('Yakin ingin menghapus?')" class="px-2.5 py-1 text-xs font-semibold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">Hapus</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[11px] text-slate-400 italic">Terkunci</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">Tidak ada data laporan yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($totalPages > 1): ?>
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong></span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&bidang=<?= urlencode($filter_bidang) ?>" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100">&laquo; Sebelumnya</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&bidang=<?= urlencode($filter_bidang) ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg border <?= $i === $page ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&bidang=<?= urlencode($filter_bidang) ?>" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100">Berikutnya &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL INPUT LAPORAN BARU -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalOpen = false"></div>

            <div x-show="modalOpen" x-transition class="relative inline-block w-full max-w-xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-4">Input Kinerja Saya</h3>

                <form method="POST" enctype="multipart/form-data" class="space-y-4" @submit="isSubmitting = true">
                    <input type="hidden" name="aksi" value="tambah_laporan">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pilih Indikator Kinerja (KPI)</label>
                        <select name="id_indikator" x-model="formIndikator" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                            <option value="">-- Pilih Indikator Tanggung Jawab Anda --</option>
                            <?php foreach ($listIndikator as $ind): ?>
                                <option value="<?= $ind['id_indikator'] ?>">[<?= htmlspecialchars($ind['bidang']) ?>] <?= htmlspecialchars($ind['indikator']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Progres Tahap Kinerja</label>
                            <select name="capaian" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                                <option value="25">Tahap Perencanaan (25%)</option>
                                <option value="50">Tahap Pelaksanaan (50%)</option>
                                <option value="75">Tahap Evaluasi (75%)</option>
                                <option value="100">Selesai Sepenuhnya (100%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1 text-purple-700">Kaitan Nilai HUMANIS</label>
                            <select name="nilai_humanis" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-purple-500 focus:border-purple-500 bg-purple-50">
                                <option value="">-- Pilih Nilai --</option>
                                <option value="Harmoni">Harmoni</option>
                                <option value="Unggul">Unggul</option>
                                <option value="Mulia">Mulia</option>
                                <option value="Akses Terbuka">Akses Terbuka</option>
                                <option value="Nyaman">Nyaman</option>
                                <option value="Inklusif">Inklusif</option>
                                <option value="Sejahtera">Sejahtera</option>
                            </select>
                        </div>
                    </div>

                    <!-- Input Realisasi yang muncul dinamis -->
                    <div x-show="formIndikator !== ''" style="display: none;" class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <label class="block text-xs font-semibold text-blue-900 uppercase mb-2">Realisasi Aktual</label>
                        <div class="flex items-center gap-3">
                            <input type="number" step="0.01" name="realisasi" x-model="formRealisasi" :required="formIndikator !== ''" class="w-2/3 rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="Ketik angka hasil nyata...">
                            <span class="w-1/3 text-sm font-bold text-slate-700" x-text="formIndikator !== '' ? masterIndikator[formIndikator].satuan : ''"></span>
                        </div>
                        <div class="mt-2 text-xs text-blue-800">
                            Target KPI: <strong x-text="formIndikator !== '' ? (masterIndikator[formIndikator].jenis + ' ' + masterIndikator[formIndikator].target + ' ' + masterIndikator[formIndikator].satuan) : ''"></strong>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Upload Bukti Pendukung (Dokumen/Foto)</label>
                        <input type="file" name="file_bukti" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1">
                        <p class="text-[10px] text-slate-400 mt-1">File yang diunggah otomatis tersimpan ke Google Drive Anda.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Refleksi & Kendala</label>
                        <textarea name="refleksi_kendala" rows="2" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="Jelaskan refleksi pelaksanaan atau hambatan yang ditemui..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Rencana Tindak Lanjut (RTL)</label>
                        <textarea name="rencana_tindak_lanjut" rows="2" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="Langkah perbaikan atau rencana ke depan..."></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50" :disabled="isSubmitting">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-800 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" :disabled="isSubmitting">
                            <span x-show="!isSubmitting">Kirim Laporan</span>
                            <span x-show="isSubmitting">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PERBAIKI / EDIT LAPORAN FULL -->
    <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="editModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="editModal = false"></div>

            <div x-show="editModal" x-transition class="relative inline-block w-full max-w-xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-4">Perbaiki Kinerja Saya</h3>

                <form method="POST" enctype="multipart/form-data" class="space-y-4" @submit="isSubmitting = true">
                    <input type="hidden" name="aksi" value="update_laporan">
                    <input type="hidden" name="id_pelaporan" x-model="lap.id">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Indikator Kinerja (KPI)</label>
                        <select x-model="lap.id_indikator" disabled class="w-full rounded-lg bg-slate-100 border-slate-300 text-sm p-2.5 text-slate-500 cursor-not-allowed">
                            <?php foreach ($listIndikator as $ind): ?>
                                <option value="<?= $ind['id_indikator'] ?>">[<?= htmlspecialchars($ind['bidang']) ?>] <?= htmlspecialchars($ind['indikator']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="id_indikator" x-model="lap.id_indikator">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Progres Tahap Kinerja</label>
                            <select name="capaian" x-model="lap.capaian" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                                <option value="25">Tahap Perencanaan (25%)</option>
                                <option value="50">Tahap Pelaksanaan (50%)</option>
                                <option value="75">Tahap Evaluasi (75%)</option>
                                <option value="100">Selesai Sepenuhnya (100%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1 text-purple-700">Kaitan Nilai HUMANIS</label>
                            <select name="nilai_humanis" x-model="lap.nilai_humanis" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-purple-500 focus:border-purple-500 bg-purple-50">
                                <option value="">-- Pilih Nilai --</option>
                                <option value="Harmoni">Harmoni</option>
                                <option value="Unggul">Unggul</option>
                                <option value="Mulia">Mulia</option>
                                <option value="Akses Terbuka">Akses Terbuka</option>
                                <option value="Nyaman">Nyaman</option>
                                <option value="Inklusif">Inklusif</option>
                                <option value="Sejahtera">Sejahtera</option>
                            </select>
                        </div>
                    </div>

                    <!-- Input Realisasi Form Edit -->
                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <label class="block text-xs font-semibold text-blue-900 uppercase mb-2">Realisasi Aktual</label>
                        <div class="flex items-center gap-3">
                            <input type="number" step="0.01" name="realisasi" x-model="lap.realisasi" required class="w-2/3 rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                            <span class="w-1/3 text-sm font-bold text-slate-700" x-text="formIndikator !== '' ? masterIndikator[formIndikator].satuan : ''"></span>
                        </div>
                        <div class="mt-2 text-xs text-blue-800">
                            Target KPI: <strong x-text="formIndikator !== '' ? (masterIndikator[formIndikator].jenis + ' ' + masterIndikator[formIndikator].target + ' ' + masterIndikator[formIndikator].satuan) : ''"></strong>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Upload Bukti Pendukung (Opsional)</label>
                        <input type="file" name="file_bukti" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1">
                        <p class="text-[10px] text-slate-400 mt-1">Biarkan kosong jika tidak ingin mengganti file bukti sebelumnya.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Refleksi & Kendala</label>
                        <textarea name="refleksi_kendala" x-model="lap.refleksi" rows="2" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Rencana Tindak Lanjut (RTL)</label>
                        <textarea name="rencana_tindak_lanjut" x-model="lap.rtl" rows="2" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="editModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50" :disabled="isSubmitting">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-800 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" :disabled="isSubmitting">
                            <span x-show="!isSubmitting">Kirim Ulang Laporan</span>
                            <span x-show="isSubmitting">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/wakasek_footer.php'; ?>