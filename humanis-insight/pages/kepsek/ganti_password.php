<?php

/**
 * Halaman Ubah Password Kepala Sekolah dengan Validasi Keamanan Ketat
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';

/** @var PDO $pdo */

$user_id = $_SESSION['user_id'] ?? 0;
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    try {
        // 1. Validasi input kosong
        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            throw new Exception("Semua kolom form wajib diisi.");
        }

        // 2. Validasi kecocokan password baru
        if ($password_baru !== $konfirmasi_password) {
            throw new Exception("Konfirmasi password baru tidak cocok.");
        }

        // 3. Validasi kompleksitas password kuat (Minimal 8 karakter, ada huruf besar, kecil, angka, & simbol)
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        if (!preg_match($pattern, $password_baru)) {
            throw new Exception("Password baru harus minimal 8 karakter dan mengandung gabungan huruf besar, huruf kecil, angka, dan simbol/karakter khusus (Contoh: Tinus#2026!).");
        }

        // 4. Ambil hash password lama dari database
        $stmt = $pdo->prepare("SELECT password FROM tb_users WHERE id_user = :id LIMIT 1");
        $stmt->execute([':id' => $user_id]);
        $currentHash = $stmt->fetchColumn();

        if (!$currentHash || !password_verify($password_lama, $currentHash)) {
            throw new Exception("Password lama yang Anda masukkan salah.");
        }

        // 5. Enkripsi password baru menggunakan BCRYPT
        $newHash = password_hash($password_baru, PASSWORD_BCRYPT);

        // 6. Simpan ke database
        $stmtUpdate = $pdo->prepare("UPDATE tb_users SET password = :password WHERE id_user = :id");
        $stmtUpdate->execute([
            ':password' => $newHash,
            ':id'       => $user_id
        ]);

        $pesan_sukses = "Password berhasil diperbarui! Keamanan akun Anda telah diperbarui.";
    } catch (Exception $e) {
        $pesan_error = $e->getMessage();
    }
}
?>

<div class="mb-8">
    <h2 class="text-2xl font-display font-bold text-slate-900">Ubah Password</h2>
    <p class="text-sm text-slate-500 mt-1">Perbarui kata sandi Anda secara berkala menggunakan kombinasi karakter yang kuat.</p>
</div>

<?php if (!empty($pesan_sukses)): ?>
    <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200"><?= $pesan_sukses ?></div>
<?php endif; ?>

<?php if (!empty($pesan_error)): ?>
    <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200"><?= $pesan_error ?></div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 max-w-2xl">

    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-800 space-y-1">
        <p class="font-bold">Ketentuan Password Aman:</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Minimal terdiri dari 8 karakter.</li>
            <li>Mengandung huruf besar (A-Z) dan huruf kecil (a-z).</li>
            <li>Mengandung angka (0-9).</li>
            <li>Mengandung simbol atau karakter khusus (contoh: @, #, !, $, %).</li>
        </ul>
    </div>

    <form method="POST" class="space-y-5" x-data="{ showP1: false, showP2: false, showP3: false }">

        <!-- Password Lama -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Password Lama</label>
            <div class="relative">
                <input :type="showP1 ? 'text' : 'password'" name="password_lama" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 pr-10 focus:ring-primary focus:border-primary" placeholder="••••••••">
                <button type="button" @click="showP1 = !showP1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg x-show="!showP1" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showP1" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Password Baru -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Password Baru</label>
            <div class="relative">
                <input :type="showP2 ? 'text' : 'password'" name="password_baru" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 pr-10 focus:ring-primary focus:border-primary" placeholder="••••••••">
                <button type="button" @click="showP2 = !showP2" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg x-show="!showP2" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showP2" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Konfirmasi Password Baru -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Konfirmasi Password Baru</label>
            <div class="relative">
                <input :type="showP3 ? 'text' : 'password'" name="konfirmasi_password" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 pr-10 focus:ring-primary focus:border-primary" placeholder="••••••••">
                <button type="button" @click="showP3 = !showP3" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg x-show="!showP3" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showP3" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-primary text-white text-sm font-semibold py-2.5 px-6 rounded-lg hover:bg-blue-800 transition-colors shadow-sm">
                Perbarui Password
            </button>
        </div>
    </form>
</div>

<?php
require_once '../../includes/kepsek_footer.php';
?>