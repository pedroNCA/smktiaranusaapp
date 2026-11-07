<?php
$listMapel = $listMapel ?? [];
$kkm = $kkm ?? 0;
$totalPages = $totalPages ?? 1;
$page = $page ?? 1;
$perPage = $perPage ?? 10;
$search = $search ?? '';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Rekapitulasi Nilai Kelas</h2>
        <p class="text-sm text-gray-500 mt-1">Pemeriksaan dan rekap nilai seluruh mata pelajaran untuk kelas <?= htmlspecialchars($kelasBinaan ?? '') ?>.</p>
    </div>
    <!--<div>
        <a href="/walikelas/raport/pratinjau" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl transition-all shadow-sm">
            Pratinjau Raport Kelas
        </a>
    </div>-->
</div>

<!-- Fitur Search & Filter Bar -->
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
    <form action="/walikelas/nilai" method="GET" class="w-full sm:w-auto flex flex-col sm:flex-row items-center gap-3">
        <!-- Input Search -->
        <div class="relative w-full sm:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama siswa..."
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full pl-10 p-2.5">
        </div>

        <!-- Filter Jumlah Tampilkan -->
        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <span class="text-sm text-gray-600 whitespace-nowrap">Tampilkan:</span>
            <select name="per_page" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 p-2">
                <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </div>

        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-sm">
            Filter
        </button>
        <?php if (!empty($search)): ?>
            <a href="/walikelas/nilai" class="text-sm text-gray-600 hover:text-gray-900 underline whitespace-nowrap">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Rekapitulasi Nilai Siswa -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="overflow-x-auto relative">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="py-3 px-4 text-center w-12 sticky left-0 z-20 bg-gray-50 border-r border-gray-200">No</th>
                    <th class="py-3 px-4 sticky left-12 z-20 bg-gray-50 border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Nama Siswa</th>

                    <?php if (!empty($listMapel)): ?>
                        <?php foreach ($listMapel as $m):
                            // Mengambil langsung dari kolom kode_mapel database, dengan fallback jika kosong
                            $kodeMapel = trim($m['kode_mapel'] ?? '');
                            if (empty($kodeMapel)) {
                                $words = explode(' ', trim($m['nama_mapel']));
                                $kodeMapel = count($words) >= 2 ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($m['nama_mapel'], 0, 3));
                            }
                        ?>
                            <th class="py-3 px-3 text-center border-r border-gray-200" title="<?= htmlspecialchars($m['nama_mapel']) ?>">
                                <span class="cursor-help"><?= htmlspecialchars($kodeMapel) ?></span>
                            </th>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <th class="py-3 px-4 text-center sticky right-20 z-20 bg-gray-50 border-l border-gray-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]">Peringkat</th>
                    <th class="py-3 px-4 text-center sticky right-0 z-20 bg-gray-50">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                <?php if (!empty($listRekapNilai)): ?>
                    <?php
                    $no = ($offset ?? 0) + 1;
                    foreach ($listRekapNilai as $index => $r):
                        $bgRow = ($index % 2 == 0) ? 'bg-white' : 'bg-gray-50/60';
                    ?>
                        <tr class="<?= $bgRow ?> hover:bg-emerald-50/30 transition-colors">
                            <td class="py-4 px-4 text-center font-medium text-gray-500 sticky left-0 z-10 <?= $bgRow ?> border-r border-gray-200"><?= $no++ ?></td>
                            <td class="py-4 px-4 font-semibold text-gray-900 sticky left-12 z-10 <?= $bgRow ?> border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]"><?= htmlspecialchars($r['nama_siswa']) ?></td>

                            <!-- Looping Nilai Per Mapel -->
                            <?php foreach ($listMapel as $m): ?>
                                <?php
                                $val = isset($r['nilai_mapel'][$m['id']]) ? round($r['nilai_mapel'][$m['id']]) : 0;
                                $isUnderKKM = ($val > 0 && $val < $kkm);
                                ?>
                                <td class="py-4 px-3 text-center font-medium border-r border-gray-100 <?= $isUnderKKM ? 'text-red-600 font-bold bg-red-50/50' : 'text-gray-900' ?>">
                                    <?= $val > 0 ? $val : '-' ?>
                                </td>
                            <?php endforeach; ?>

                            <td class="py-4 px-4 text-center font-bold text-slate-900 sticky right-20 z-10 <?= $bgRow ?> border-l border-gray-200 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                <span class="px-2.5 py-1 bg-gray-100 rounded-lg"><?= $r['peringkat'] ?></span>
                            </td>
                            <td class="py-4 px-4 text-center sticky right-0 z-10 <?= $bgRow ?>">
                                <a href="/walikelas/nilai/edit?siswa_id=<?= $r['siswa_id'] ?>" class="text-emerald-600 hover:text-emerald-800 font-semibold text-xs bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors">
                                    Edit Nilai
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($listMapel) + 4 ?>" class="py-8 text-center text-gray-500">Belum ada data rekapitulasi nilai tersedia untuk kelas ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-500">
                Halaman <span class="font-semibold text-gray-700"><?= $page ?></span> dari <span class="font-semibold text-gray-700"><?= $totalPages ?></span>
            </div>
            <div class="flex space-x-1">
                <?php if ($page > 1): ?>
                    <a href="/walikelas/nilai?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&per_page=<?= $perPage ?>" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100 shadow-sm">Sebelumnya</a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/walikelas/nilai?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&per_page=<?= $perPage ?>" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100 shadow-sm">Berikutnya</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>