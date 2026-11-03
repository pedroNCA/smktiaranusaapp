<?php
require_once '../../includes/pic_header.php';
require_once '../../includes/pic_sidebar.php';
require_once '../../includes/pic_navbar.php';
require_once '../../config/database.php';
require_once '../../actions/upload_gdrive.php';

$id = $_GET['id'] ?? 0;
$nama_pic = $_SESSION['nama'];

// Ambil data laporan lama
$stmt = $pdo->prepare("SELECT p.*, i.indikator FROM tb_pelaporan p JOIN tb_indikator i ON p.id_indikator = i.id_indikator WHERE p.id_pelaporan = :id AND p.nama_pic = :nama");
$stmt->execute([':id' => $id, ':nama' => $nama_pic]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data || $data['status_verifikasi'] !== 'Menunggu Persetujuan') {
    die("Laporan tidak ditemukan atau tidak dapat diedit.");
}

// Proses Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $capaian = floatval($_POST['capaian']);
    $nilai_humanis = trim($_POST['nilai_humanis']); // Tambahan Input Nilai HUMANIS
    $refleksi = trim($_POST['refleksi_kendala']);
    $rtl = trim($_POST['rencana_tindak_lanjut']);
    $link_gdrive = $data['bukti_gdrive'];

    // Jika ada file baru
    if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === UPLOAD_ERR_OK) {
        // Hapus file lama di Drive
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $data['bukti_gdrive'], $matches)) {
            require_once '../../actions/upload_gdrive.php';
            hapusFileGoogleDrive($matches[1]);
        }
        // Upload file baru
        $fileTmpPath = $_FILES['file_bukti']['tmp_name'];
        $fileName = 'Bukti_' . time() . '_' . $_FILES['file_bukti']['name'];
        $link_gdrive = uploadToGoogleDrive($fileTmpPath, $fileName);
    }

    // Query Update diperbarui dengan field nilai_humanis
    $stmtUpd = $pdo->prepare("UPDATE tb_pelaporan SET capaian = :capaian, nilai_humanis = :nilai_humanis, bukti_gdrive = :gdrive, refleksi_kendala = :refleksi, rencana_tindak_lanjut = :rtl WHERE id_pelaporan = :id");
    $stmtUpd->execute([
        ':capaian' => $capaian,
        ':nilai_humanis' => $nilai_humanis,
        ':gdrive' => $link_gdrive,
        ':refleksi' => $refleksi,
        ':rtl' => $rtl,
        ':id' => $id
    ]);

    echo "<script>alert('Laporan berhasil diperbarui!'); window.location='pelaporan.php';</script>";
}
?>

<div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl shadow-sm ring-1 ring-slate-200">
    <h2 class="text-xl font-bold mb-4">Edit Laporan: <?= htmlspecialchars($data['indikator']) ?></h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase mb-1">Capaian</label>
                <select name="capaian" class="w-full rounded-lg border-slate-300 p-2 text-sm focus:ring-primary focus:border-primary">
                    <option value="25" <?= $data['capaian'] == 25 ? 'selected' : '' ?>>Tahap Perencanaan (25%)</option>
                    <option value="50" <?= $data['capaian'] == 50 ? 'selected' : '' ?>>Tahap Pelaksanaan (50%)</option>
                    <option value="75" <?= $data['capaian'] == 75 ? 'selected' : '' ?>>Tahap Evaluasi (75%)</option>
                    <option value="100" <?= $data['capaian'] == 100 ? 'selected' : '' ?>>Selesai Sepenuhnya (100%)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase mb-1 text-purple-700">Kaitan Nilai HUMANIS</label>
                <select name="nilai_humanis" required class="w-full rounded-lg border-slate-300 p-2 text-sm focus:ring-purple-500 focus:border-purple-500 bg-purple-50">
                    <option value="">-- Pilih Nilai --</option>
                    <?php
                    $nilai_list = ['Harmoni', 'Unggul', 'Mulia', 'Akses Terbuka', 'Nyaman', 'Inklusif', 'Sejahtera'];
                    foreach ($nilai_list as $n) {
                        $selected = ($data['nilai_humanis'] === $n) ? 'selected' : '';
                        echo "<option value=\"$n\" $selected>$n</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase mb-1">Upload Bukti Baru (Kosongkan jika tetap)</label>
            <input type="file" name="file_bukti" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="w-full text-sm border border-slate-300 p-2 rounded-lg focus:ring-primary focus:border-primary">
            <?php if (!empty($data['bukti_gdrive'])): ?>
                <p class="text-[10px] text-slate-400 mt-1">Bukti saat ini: <a href="<?= htmlspecialchars($data['bukti_gdrive']) ?>" target="_blank" class="text-blue-600 underline font-semibold">Lihat File</a></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase mb-1">Refleksi Pelaksanaan</label>
            <textarea name="refleksi_kendala" rows="3" required class="w-full rounded-lg border-slate-300 p-2 text-sm focus:ring-primary focus:border-primary"><?= htmlspecialchars($data['refleksi_kendala']) ?></textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase mb-1">Rencana Tindak Lanjut (RTL)</label>
            <textarea name="rencana_tindak_lanjut" rows="3" required class="w-full rounded-lg border-slate-300 p-2 text-sm focus:ring-primary focus:border-primary"><?= htmlspecialchars($data['rencana_tindak_lanjut']) ?></textarea>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="bg-primary text-white text-sm font-semibold px-5 py-2.5 rounded-lg hover:bg-blue-800 transition-colors">Simpan Perubahan</button>
            <a href="pelaporan.php" class="bg-slate-100 text-slate-700 text-sm font-semibold px-5 py-2.5 rounded-lg hover:bg-slate-200 transition-colors">Batal</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/pic_footer.php'; ?>