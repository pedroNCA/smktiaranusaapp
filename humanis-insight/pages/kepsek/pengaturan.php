<?php

/**
 * Halaman Pengaturan Sistem (Kepala Sekolah) - Konfigurasi Lengkap
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

$pesan_sukses = '';

// Ambil data awal dari database
$setting = $pdo->query("SELECT * FROM tb_pengaturan LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

// Fungsi Enkripsi & Dekripsi Sederhana untuk SMTP Password
function encryptPass($text)
{
    return !empty($text) ? base64_encode($text) : '';
}
function decryptPass($text)
{
    return !empty($text) ? base64_decode($text) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Penanganan file upload (jika ada file baru)
    $logo = !empty($_FILES['logo']['name']) ? 'logo_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION) : ($setting['logo'] ?? '');
    $favicon = !empty($_FILES['favicon']['name']) ? 'favicon_' . time() . '.' . pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION) : ($setting['favicon'] ?? '');

    if (!empty($_FILES['logo']['name'])) move_uploaded_file($_FILES['logo']['tmp_name'], '../../assets/img/' . $logo);
    if (!empty($_FILES['favicon']['name'])) move_uploaded_file($_FILES['favicon']['tmp_name'], '../../assets/img/' . $favicon);

    try {
        // Kolom yayasan dan semester aktif telah dihapus
        $sql = "REPLACE INTO tb_pengaturan (id_pengaturan, nama_sekolah, tahun_ajaran, kepala_sekolah, nrks, alamat, kompetensi_keahlian_1, kompetensi_keahlian_2, logo, favicon, tipe_sekolah, no_telepon, email_sekolah, website, slogan, npsn, target_mutu_global, smtp_host, smtp_user, smtp_pass, smtp_port) 
                VALUES (1, :ns, :ta, :ks, :nrks, :alm, :kk1, :kk2, :logo, :fav, :tipe, :telp, :email, :web, :sl, :npsn, :target, :sh, :su, :sp, :port)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ns'         => $_POST['nama_sekolah'] ?? '',
            ':ta'         => $_POST['tahun_ajaran'] ?? '',
            ':ks'         => $_POST['kepala_sekolah'] ?? '',
            ':nrks'       => $_POST['nrks'] ?? '',
            ':alm'        => $_POST['alamat'] ?? '',
            ':kk1'        => $_POST['kompetensi_keahlian_1'] ?? '',
            ':kk2'        => $_POST['kompetensi_keahlian_2'] ?? '',
            ':logo'       => $logo,
            ':fav'        => $favicon,
            ':tipe'       => $_POST['tipe_sekolah'] ?? '',
            ':telp'       => $_POST['no_telepon'] ?? '',
            ':email'      => $_POST['email_sekolah'] ?? '',
            ':web'        => $_POST['website'] ?? '',
            ':sl'         => $_POST['slogan'] ?? '',
            ':npsn'       => $_POST['npsn'] ?? '',
            ':target'     => $_POST['target_mutu_global'] ?? 85,
            ':sh'         => $_POST['smtp_host'] ?? '',
            ':su'         => $_POST['smtp_user'] ?? '',
            ':sp'         => encryptPass($_POST['smtp_pass'] ?? ''),
            ':port'       => $_POST['smtp_port'] ?? 587
        ]);

        $pesan_sukses = "Pengaturan sistem berhasil diperbarui.";
        // Refresh data setelah update
        $setting = $pdo->query("SELECT * FROM tb_pengaturan LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "<div class='p-4 bg-red-100 text-red-700 rounded-xl mb-4'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="max-w-5xl mx-auto pb-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Pengaturan Lengkap Sistem</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola identitas institusi, kompetensi keahlian, target mutu, dan konfigurasi SMTP.</p>
    </div>

    <?php if ($pesan_sukses): ?>
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-sm font-medium"><?= $pesan_sukses ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-8">

        <!-- Bagian 1: Identitas & Institusi Sekolah -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 uppercase tracking-wider">Identitas Institusi & Akademik</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Nama Sekolah</label>
                    <input type="text" name="nama_sekolah" value="<?= htmlspecialchars($setting['nama_sekolah'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Tipe Sekolah</label>
                    <select name="tipe_sekolah" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary bg-white">
                        <?php foreach (['SD', 'SMP', 'SMA', 'SMK'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($setting['tipe_sekolah'] ?? '') == $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Slogan / Moto Sekolah</label>
                    <input type="text" name="slogan" value="<?= htmlspecialchars($setting['slogan'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">NPSN</label>
                    <input type="text" name="npsn" value="<?= htmlspecialchars($setting['npsn'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Target Mutu Global (%)</label>
                    <input type="number" name="target_mutu_global" value="<?= htmlspecialchars($setting['target_mutu_global'] ?? 85) ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Tahun Ajaran</label>
                    <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($setting['tahun_ajaran'] ?? '') ?>" placeholder="Contoh: 2026/2027" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Kompetensi Keahlian 1</label>
                    <input type="text" name="kompetensi_keahlian_1" value="<?= htmlspecialchars($setting['kompetensi_keahlian_1'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Kompetensi Keahlian 2</label>
                    <input type="text" name="kompetensi_keahlian_2" value="<?= htmlspecialchars($setting['kompetensi_keahlian_2'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        <!-- Bagian 2: Pimpinan & Kontak Sekolah -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 uppercase tracking-wider">Pimpinan & Informasi Kontak Sekolah</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Kepala Sekolah</label>
                    <input type="text" name="kepala_sekolah" value="<?= htmlspecialchars($setting['kepala_sekolah'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">NRKS Kepala Sekolah</label>
                    <input type="text" name="nrks" value="<?= htmlspecialchars($setting['nrks'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">No Telepon Sekolah</label>
                    <input type="text" name="no_telepon" value="<?= htmlspecialchars($setting['no_telepon'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Email Sekolah</label>
                    <input type="email" name="email_sekolah" value="<?= htmlspecialchars($setting['email_sekolah'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Website Resmi Sekolah</label>
                    <input type="text" name="website" value="<?= htmlspecialchars($setting['website'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Alamat Lengkap Sekolah</label>
                    <textarea name="alamat" rows="2" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary"><?= htmlspecialchars($setting['alamat'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Bagian 3: Konfigurasi Teknis, SMTP & Aset -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 uppercase tracking-wider">Konfigurasi Teknis & Aset Visual</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!--<div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?= htmlspecialchars($setting['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?= htmlspecialchars($setting['smtp_port'] ?? '587') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">SMTP User (Email Pengirim)</label>
                    <input type="text" name="smtp_user" value="<?= htmlspecialchars($setting['smtp_user'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">SMTP Pass (App Password)</label>
                    <div class="relative">
                        <input type="password" id="smtp_pass" name="smtp_pass" value="<?= decryptPass($setting['smtp_pass'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-primary focus:border-primary pr-10">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                            <svg id="eye_icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>-->

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Logo Sekolah</label>
                    <input type="file" name="logo" class="w-full text-sm border border-slate-300 rounded-lg p-1.5 file:mr-4 file:py-1 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <?php if (!empty($setting['logo'])): ?>
                        <p class="text-[11px] text-slate-500 mt-1">File saat ini: <?= htmlspecialchars($setting['logo']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase mb-1 text-slate-700">Favicon</label>
                    <input type="file" name="favicon" class="w-full text-sm border border-slate-300 rounded-lg p-1.5 file:mr-4 file:py-1 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <?php if (!empty($setting['favicon'])): ?>
                        <p class="text-[11px] text-slate-500 mt-1">File saat ini: <?= htmlspecialchars($setting['favicon']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-primary px-8 py-3 text-white rounded-xl font-bold text-sm hover:bg-blue-800 transition-colors shadow-sm">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<!-- Script untuk Toggle Password -->
<script>
    function togglePassword() {
        const passwordInput = document.getElementById('smtp_pass');
        const eyeIcon = document.getElementById('eye_icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />`;
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
        }
    }
</script>

<?php require_once '../../includes/kepsek_footer.php'; ?>