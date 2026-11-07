<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Cetak Dokumen Raport</h2>
        <p class="text-sm text-gray-500 mt-1">Pilih peserta didik untuk melakukan pencetakan lembar hasil belajar atau cetak seluruh kelas sekaligus.</p>
    </div>
    <div>
        <a href="/walikelas/raport/cetak-semua" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
            🖨️ Cetak Semua Raport Kelas
        </a>
    </div>
</div>

<!-- Tabel Daftar Siswa untuk Cetak Raport -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Daftar Peserta Didik Kelas <?= htmlspecialchars($kelasBinaan ?? '') ?></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="py-3 px-6 text-center w-16">No</th>
                    <th class="py-3 px-6">NIS / NISN</th>
                    <th class="py-3 px-6">Nama Siswa</th>
                    <th class="py-3 px-6">Fase / Semester</th>
                    <th class="py-3 px-6 text-center">Aksi Pencetakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                <?php if (!empty($listSiswa)): ?>
                    <?php $no = 1; foreach ($listSiswa as $s): ?>
                        <tr>
                            <td class="py-4 px-6 text-center font-medium text-gray-500"><?= $no++ ?></td>
                            <td class="py-4 px-6 font-medium text-gray-900"><?= htmlspecialchars($s['nis']) ?> / <?= htmlspecialchars($s['nisn']) ?></td>
                            <td class="py-4 px-6 font-semibold text-gray-900"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                            <td class="py-4 px-6"><?= htmlspecialchars($s['fase']) ?> / <?= htmlspecialchars($s['semester'] ?? 'Ganjil') ?></td>
                            <td class="py-4 px-6 text-center">
                                <!-- Menggunakan fungsi JavaScript cetak langsung tanpa pindah halaman penuh -->
                                <button onclick="cetakRaportSiswa(<?= $s['id'] ?>)" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">
                                    Cetak Raport
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">Belum ada data siswa terdaftar di kelas binaan ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Iframe Tersembunyi untuk Proses Cetak di Balik Layar -->
<iframe id="print-frame" name="print-frame" style="display:none;"></iframe>

<script>
function cetakRaportSiswa(siswaId) {
    // Buat iframe sementara atau gunakan iframe tersembunyi untuk memuat halaman cetak
    let iframe = document.getElementById('print-frame');
    iframe.src = '/walikelas/raport/cetak-pdf?siswa_id=' + siswaId;
}
</script>