<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Dashboard Wali Kelas</h2>
    <p class="text-sm text-gray-500 mt-1">Selamat datang, <span class="font-semibold"><?= htmlspecialchars($_SESSION['nama'] ?? 'Wali Kelas') ?></span>. Berikut adalah ringkasan kelas binaan dan mata pelajaran Anda.</p>
</div>

<!-- Kartu Statistik Terpisah -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Card Info Kelas Binaan -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Kelas Binaan</h3>
            <p class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($kelasBinaan ?? '--') ?></p>
        </div>
    </div>

    <!-- Card Total Siswa Kelas Binaan -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Jumlah Siswa</h3>
            <p class="text-2xl font-bold text-gray-900"><?= $totalSiswaKelas ?? '0' ?></p>
        </div>
    </div>

    <!-- Card Total Mapel Kelas Binaan -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Mapel Kelas Binaan</h3>
            <p class="text-2xl font-bold text-gray-900"><?= count($listMapelBinaan ?? []) ?></p>
        </div>
    </div>

    <!-- Card Total Mapel Diampu -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Mapel yang Diampu</h3>
            <p class="text-2xl font-bold text-gray-900"><?= count($listMapelDiampu ?? []) ?></p>
        </div>
    </div>
</div>

<!-- TABEL 1: Mata Pelajaran Kelas Binaan -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Mata Pelajaran Kelas Binaan (<?= htmlspecialchars($kelasBinaan ?? '--') ?>)</h3>
            <p class="text-sm text-gray-500 mt-0.5">Pemantauan progres kelengkapan nilai dari seluruh guru pengampu di kelas ini.</p>
        </div>
        <a href="/walikelas/raport" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            Cetak Raport PDF
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="py-3 px-6">No</th>
                    <th class="py-3 px-6">Mata Pelajaran</th>
                    <th class="py-3 px-6">Kelompok</th>
                    <th class="py-3 px-6">Guru Pengampu</th>
                    <th class="py-3 px-6 text-center">Status Pengisian</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                <?php if (!empty($listMapelBinaan)): ?>
                    <?php $no = 1;
                    foreach ($listMapelBinaan as $m): ?>
                        <tr>
                            <td class="py-4 px-6 text-center font-medium text-gray-500"><?= $no++ ?></td>
                            <td class="py-4 px-6 font-semibold text-gray-900"><?= htmlspecialchars($m['nama_mapel']) ?></td>
                            <td class="py-4 px-6"><?= htmlspecialchars($m['kelompok_mapel']) ?></td>
                            <td class="py-4 px-6"><?= htmlspecialchars($m['nama_guru'] ?? 'Belum Ditentukan') ?></td>
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
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-500">Belum ada data mata pelajaran untuk kelas binaan ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TABEL 2: Mata Pelajaran yang Diampu Sendiri -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Mata Pelajaran yang Anda Ampu</h3>
            <p class="text-sm text-gray-500 mt-0.5">Daftar mata pelajaran yang ditugaskan kepada Anda sebagai guru pengampu.</p>
        </div>
        <a href="/guru/nilai" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            Input Nilai Mapel
        </a>
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
                                <a href="/guru/nilai?mapel_id=<?= $m['id'] ?>" class="text-emerald-600 hover:text-emerald-800 font-semibold text-xs bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors">
                                    Input Nilai
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