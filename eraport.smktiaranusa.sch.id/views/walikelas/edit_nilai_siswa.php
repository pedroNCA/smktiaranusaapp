<?php $siswa = $siswa ?? []; ?>
<div x-data="{ isEditing: false }">
    <!-- HEADER & TOMBOL AKSI -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Edit Nilai Peserta Didik</h2>
            <p class="text-sm text-gray-500 mt-1">Mengubah nilai mata pelajaran untuk siswa: <span class="font-semibold text-gray-900"><?= htmlspecialchars(($siswa['nama_siswa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span> (NIS: <?= htmlspecialchars(($siswa['nis'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | Kelas: <?= htmlspecialchars(($siswa['kelas'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</p>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Tombol Aktifkan / Nonaktifkan Edit -->
            <button type="button" @click="isEditing = !isEditing"
                :class="isEditing ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-slate-900 hover:bg-slate-800 text-white'"
                class="px-4 py-2 text-sm font-semibold rounded-xl transition-all shadow-sm flex items-center space-x-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span x-text="isEditing ? 'Batalkan Edit' : 'Edit Nilai'"></span>
            </button>

            <a href="/walikelas/nilai" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-xl transition-all">
                &larr; Kembali
            </a>
        </div>
    </div>

    <!-- FORM TABEL NILAI -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ kkm: <?= $kkm ?? 75 ?> }">
        <form action="/walikelas/nilai/update" method="POST">
            <input type="hidden" name="siswa_id" value="<?= htmlspecialchars((string) ($siswa['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3 px-6 text-center w-16">No</th>
                            <th class="py-3 px-6">Mata Pelajaran</th>
                            <th class="py-3 px-6">Kelompok</th>
                            <th class="py-3 px-6">Kelas</th>
                            <th class="py-3 px-6">Guru Pengampu</th>
                            <th class="py-3 px-6 text-center w-28">KKM / KKTP</th>
                            <th class="py-3 px-6 text-center w-40">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                        <?php if (!empty($listNilaiMapel)): ?>
                            <?php $no = 1;
                            foreach ($listNilaiMapel as $nm):
                                $nilaiVal = isset($nm['nilai_akhir']) ? round($nm['nilai_akhir']) : 0;
                            ?>
                                <input type="hidden" name="mapel_id[]" value="<?= $nm['mapel_id'] ?>">
                                <tr>
                                    <td class="py-4 px-6 text-center font-medium text-gray-500"><?= $no++ ?></td>
                                    <td class="py-4 px-6 font-semibold text-gray-900"><?= htmlspecialchars($nm['nama_mapel']) ?></td>
                                    <td class="py-4 px-6 text-gray-500"><?= htmlspecialchars($nm['kelompok_mapel']) ?></td>
                                    <td class="py-4 px-6 text-gray-600 font-medium"><?= htmlspecialchars($nm['kelas_mapel'] ?? $siswa['kelas']) ?></td>
                                    <td class="py-4 px-6 text-gray-600"><?= htmlspecialchars($nm['nama_guru']) ?></td>
                                    <td class="py-4 px-6 text-center font-medium text-gray-600"><?= htmlspecialchars((string) ($kkm ?? 75), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="py-4 px-6 text-center" x-data="{ val: '<?= $nilaiVal ?>' }">
                                        <input type="number" name="nilai_akhir[]"
                                            x-model="val"
                                            @input="if(val > 100) val = 100; if(val < 0) val = 0; val = Math.floor(val);"
                                            :disabled="!isEditing"
                                            :readonly="!isEditing"
                                            min="0" max="100" step="1" required
                                            onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                            :class="{
                                                   'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none select-none': !isEditing,
                                                   'bg-gray-50 text-gray-900': isEditing,
                                                   'text-red-600 font-bold': parseInt(val) < kkm
                                               }"
                                            class="w-32 text-center border border-gray-300 rounded-lg p-2 text-sm font-bold focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-500">Belum ada data mata pelajaran untuk kelas ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TOMBOL SIMPAN (Hanya muncul dan aktif jika tombol Edit Nilai diaktifkan) -->
            <?php if (!empty($listNilaiMapel)): ?>
                <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end" x-show="isEditing" style="display: none;">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                        Simpan Perubahan Nilai
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>