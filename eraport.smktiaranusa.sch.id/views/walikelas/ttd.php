<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Unggah Tanda Tangan Digital</h2>
    <p class="text-sm text-gray-500 mt-1">Unggah berkas tanda tangan agar otomatis disematkan pada lembar pengesahan raport.</p>
</div>

<!-- Notifikasi Alert -->
<?php if (isset($_GET['success'])): ?>
    <div class="mb-4 p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl" role="alert">
        Tanda tangan digital berhasil diperbarui.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="mb-4 p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl" role="alert">
        Gagal mengunggah berkas. Pastikan format file adalah JPG, JPEG, atau PNG.
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Form Upload -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Form Berkas Tanda Tangan</h3>
        <form action="/walikelas/ttd/store" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label for="tanda_tangan" class="block mb-2 text-sm font-medium text-gray-700">Pilih Berkas Gambar (Format: PNG / JPG, Transparan disarankan)</label>
                <input type="file" id="tanda_tangan" name="tanda_tangan" accept=".jpg, .jpeg, .png" required
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-xl cursor-pointer bg-gray-50 focus:outline-none p-2.5">
                <p class="text-xs text-gray-500 mt-2">Maksimal ukuran berkas 2MB dengan latar belakang transparan atau putih bersih.</p>
            </div>
            <div class="pt-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                    Simpan Tanda Tangan
                </button>
            </div>
        </form>
    </div>

    <!-- Pratinjau Tanda Tangan Saat Ini -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900 mb-4">Pratinjau Tanda Tangan Saat Ini</h3>
            <div class="flex items-center justify-center p-6 bg-gray-50 border border-dashed border-gray-300 rounded-xl min-h-[180px]">
                <?php if (!empty($userTtd['tanda_tangan'])): ?>
                    <img src="<?= htmlspecialchars($userTtd['tanda_tangan']) ?>" alt="Tanda Tangan Wali Kelas" class="max-h-32 object-contain">
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic">Belum ada tanda tangan yang diunggah.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-4 text-xs text-gray-500">
            Tanda tangan ini akan otomatis tertera di bagian penandatanganan lembar raport kelas binaan.
        </div>
    </div>
</div>