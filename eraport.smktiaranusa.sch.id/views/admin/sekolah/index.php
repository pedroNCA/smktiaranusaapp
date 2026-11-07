<div x-data="{ showPopup: <?= isset($_GET['error']) || isset($_GET['success']) ? 'true' : 'false' ?> }">

    <!-- HEADER -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Pengaturan Sekolah</h2>
        <p class="text-sm text-gray-500 mt-1">Konfigurasi profil institusi, tahun pelajaran, dan parameter raport.</p>
    </div>

    <!-- POP-UP NOTIFIKASI -->
    <div x-show="showPopup" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="showPopup = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden text-center p-6">
            <?php if (isset($_GET['error'])): ?>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold">!</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Terjadi Kesalahan</h3>
                <?php if ($_GET['error'] == 'format'): ?>
                    <p class="text-sm text-gray-500 mb-6">Format gambar tidak didukung. Harap unggah file JPG atau PNG.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mb-6">Gagal menyimpan pengaturan sekolah. Terjadi kesalahan pada sistem.</p>
                <?php endif; ?>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold">✓</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Berhasil</h3>
                <p class="text-sm text-gray-500 mb-6">Pengaturan sekolah berhasil diperbarui dan disimpan.</p>
            <?php endif; ?>
            <button @click="showPopup = false; window.history.replaceState({}, document.title, window.location.pathname);" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
                Tutup
            </button>
        </div>
    </div>

    <!-- FORM PENGATURAN -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <form action="/admin/sekolah/simpan" method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Kolom Kiri -->
                <div class="space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nama Sekolah</label>
                        <input type="text" name="nama_sekolah" value="<?= htmlspecialchars($sekolah['nama_sekolah'] ?? '') ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nama Asesmen <span class="font-normal text-gray-400">(Contoh: Sumatif Akhir Semester)</span></label>
                        <input type="text" name="nama_asesmen" value="<?= htmlspecialchars($sekolah['nama_asesmen'] ?? '') ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Tahun Pelajaran</label>
                            <input type="text" name="tahun_pelajaran" value="<?= htmlspecialchars($sekolah['tahun_pelajaran'] ?? '') ?>" required placeholder="Contoh: 2025/2026" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Semester</label>
                            <select name="semester" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                <option value="Ganjil" <?= (isset($sekolah['semester']) && $sekolah['semester'] == 'Ganjil') ? 'selected' : '' ?>>Ganjil</option>
                                <option value="Genap" <?= (isset($sekolah['semester']) && $sekolah['semester'] == 'Genap') ? 'selected' : '' ?>>Genap</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">KKM / KKTP Minimal</label>
                        <input type="number" name="kkm_kktp" value="<?= htmlspecialchars($sekolah['kkm_kktp'] ?? '75') ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nama Kepala Sekolah</label>
                        <input type="text" name="nama_kepala_sekolah" value="<?= htmlspecialchars($sekolah['nama_kepala_sekolah'] ?? '') ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">NIP / NRKS Kepala Sekolah</label>
                        <input type="text" name="nrks" value="<?= htmlspecialchars($sekolah['nrks'] ?? '') ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Alamat Lengkap Sekolah</label>
                        <textarea name="alamat_sekolah" rows="3" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"><?= htmlspecialchars($sekolah['alamat_sekolah'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Upload Tanda Tangan Kepsek</label>
                        <?php if (!empty($sekolah['tanda_tangan_kepsek'])): ?>
                            <div class="mb-3 p-2 bg-gray-50 border border-gray-200 rounded-lg inline-block">
                                <img src="<?= htmlspecialchars($sekolah['tanda_tangan_kepsek']) ?>" alt="Tanda Tangan Kepsek" class="h-16 object-contain">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="tanda_tangan" accept="image/png, image/jpeg" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-slate-800 file:text-white file:text-sm hover:file:bg-slate-700 focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500">Format .PNG transparan disarankan. Maksimal 2MB. Kosongkan jika tidak ingin mengubah tanda tangan.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-5 border-t border-gray-200">
                <button type="submit" class="text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 font-semibold rounded-xl text-sm px-6 py-3 transition shadow-sm">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>