<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Dashboard Guru Mata Pelajaran</h2>
    <p class="text-sm text-gray-500 mt-1">Selamat datang, <span class="font-semibold"><?= htmlspecialchars($_SESSION['nama'] ?? 'Guru') ?></span>. Berikut adalah ringkasan tugas mengajar Anda.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Card Mapel Diampu -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Mata Pelajaran Diampu</h3>
            <p class="text-2xl font-bold text-gray-900"><?= count($listMapelDiampu ?? []) ?></p>
        </div>
    </div>

    <!-- Quick Action Card 
    <div class="bg-slate-900 rounded-xl shadow-sm border border-slate-800 p-6 flex items-center justify-between text-white hover:shadow-md transition-shadow">
        <div>
            <h3 class="text-lg font-bold">Input Nilai Siswa</h3>
            <p class="text-sm text-slate-300 mt-1">Perbarui nilai akhir peserta didik.</p>
        </div>
        <a href="/guru/nilai" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
            Mulai Input
        </a>
    </div>-->
</div>

<!-- List Mata Pelajaran & Status Pengisian -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-900">Daftar Mata Pelajaran & Status Nilai</h3>
        <p class="text-sm text-gray-500 mt-0.5">Pemantauan progres pengisian nilai berdasarkan kelas yang diajar.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="py-3 px-6">No</th>
                    <th class="py-3 px-6">Mata Pelajaran</th>
                    <th class="py-3 px-6">Kelompok</th>
                    <th class="py-3 px-6">Tingkat / Jurusan</th>
                    <th class="py-3 px-6 text-center">Status Pengisian</th>
                    <th class="py-3 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                <?php if (!empty($listMapelDiampu)): ?>
                    <?php $no = 1;
                    foreach ($listMapelDiampu as $m): ?>
                        <tr>
                            <td class="py-4 px-6 text-center font-medium text-gray-500"><?= $no++ ?></td>
                            <td class="py-4 px-6 font-semibold text-gray-900"><?= htmlspecialchars($m['nama_mapel']) ?></td>
                            <td class="py-4 px-6"><?= htmlspecialchars($m['kelompok_mapel']) ?></td>
                            <td class="py-4 px-6"><?= htmlspecialchars($m['tingkat_jurusan']) ?></td>
                            <td class="py-4 px-6 text-center">
                                <?php if (($m['sudah_diisi'] ?? 0) > 0 && ($m['sudah_diisi'] >= $m['total_siswa'])): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        Sudah Lengkap (<?= $m['sudah_diisi'] ?>/<?= $m['total_siswa'] ?> Siswa)
                                    </span>
                                <?php elseif (($m['sudah_diisi'] ?? 0) > 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        Sebagian (<?= $m['sudah_diisi'] ?>/<?= $m['total_siswa'] ?> Siswa)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        Belum Diisi (0/<?= $m['total_siswa'] ?? 0 ?> Siswa)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <a href="/guru/nilai?mapel_id=<?= $m['id'] ?>" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-lg text-xs font-semibold transition">
                                    Kelola Nilai
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-500">Belum ada mata pelajaran yang ditugaskan kepada Anda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>