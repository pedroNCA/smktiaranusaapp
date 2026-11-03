<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::piket();

$userRole = $_SESSION['role'] ?? 'piket';
$isPiket = true;

date_default_timezone_set('Asia/Jakarta');

$pageTitle = "Finalisasi Detail Presensi";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?route=piket/finalisasi");
    exit;
}

$id = (int) $_GET['id'];
$sql = "
SELECT
    vk.id,
    vk.kelas_id,
    vk.status,
    vk.tanggal,
    vk.guru_id,
    vk.guru_at,
    vk.piket_id,
    vk.piket_at,
    k.nama_kelas,
    k.wali_kelas_id,
    g.nama_lengkap AS nama_guru_verifikasi,
    COUNT(s.id) AS jumlah_siswa
FROM verifikasi_kelas vk
JOIN kelas k ON k.id = vk.kelas_id
LEFT JOIN users g ON g.id = vk.guru_id
LEFT JOIN siswa s ON s.kelas_id = k.id AND s.status = 'Aktif'
WHERE vk.id = ?
GROUP BY
    vk.id,
    vk.kelas_id,
    vk.status,
    vk.tanggal,
    vk.guru_id,
    vk.guru_at,
    vk.piket_id,
    vk.piket_at,
    k.nama_kelas,
    k.wali_kelas_id,
    g.nama_lengkap
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$kelas = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelas) {
    header("Location: index.php?route=piket/finalisasi");
    exit;
}

$sql = "
SELECT
    s.id AS siswa_id,
    s.nis,
    s.nama_siswa AS nama,
    s.jenis_kelamin,
    a.id AS absensi_id,
    a.status,
    a.jam_datang
FROM siswa s
LEFT JOIN absensi a
    ON a.siswa_id = s.id
    AND a.tanggal = ?
WHERE s.kelas_id = ?
  AND s.status = 'Aktif'
ORDER BY s.nama_siswa ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $kelas['tanggal'], $kelas['kelas_id']);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusValid = ['Hadir', 'Sakit', 'Izin', 'Alfa', 'Terlambat'];
$summary = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alfa' => 0, 'terlambat' => 0, 'belum' => 0];

foreach ($siswa as $row) {
    $statusPresensi = trim($row['status'] ?? '');
    if (!in_array($statusPresensi, $statusValid, true)) {
        $summary['belum']++;
    } else {
        $key = strtolower($statusPresensi);
        if (isset($summary[$key])) {
            $summary[$key]++;
        } else {
            $summary['belum']++;
        }
    }
}

$totalSiswa = count($siswa);
$totalTerisi = $totalSiswa - $summary['belum'];
$persentase = ($totalSiswa > 0) ? round(($totalTerisi / $totalSiswa) * 100) : 0;

// --- Memanggil Layout Header Global ---
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">

            <div class="flex-1">
                <a href="index.php?route=piket/finalisasi" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-lg mb-4 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>

                <h1 class="text-2xl md:text-3xl font-bold mb-3 text-white">
                    Finalisasi Presensi Kelas <?= htmlspecialchars($kelas['nama_kelas']) ?>
                </h1>

                <div class="flex items-center gap-2 text-brand-100 text-sm md:text-base font-medium">
                    <svg class="w-5 h-5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="jamRealtime"></span>
                </div>
            </div>

            <div class="rounded-xl p-3 text-center min-w-[130px]">
                <?php
                $currentStatus = $kelas['status'] ?? 'draft';
                $guruIdVerifikasi = $kelas['guru_id'] ?? null;
                $waliKelasId = $kelas['wali_kelas_id'] ?? null;
                $namaGuruVerif = $kelas['nama_guru_verifikasi'] ?? '';

                $labelGuru = 'Draft / Belum Verifikasi';
                if (in_array($currentStatus, ['guru', 'selesai'], true) && !empty($guruIdVerifikasi)) {
                    if ((int)$guruIdVerifikasi === (int)$waliKelasId) {
                        $labelGuru = 'Diverifikasi Wali Kelas' . (!empty($namaGuruVerif) ? ' (' . htmlspecialchars($namaGuruVerif) . ')' : '');
                    } else {
                        $labelGuru = 'Diverifikasi Guru' . (!empty($namaGuruVerif) ? ': ' . htmlspecialchars($namaGuruVerif) : '');
                    }
                } else {
                    if ($currentStatus === 'sekretaris') {
                        $labelGuru = 'Dari Sekretaris';
                    }
                }

                $statusConfig = [
                    'draft' => ['bg-gray-100 text-gray-700 border-gray-200', 'bg-gray-400', $labelGuru],
                    'sekretaris' => ['bg-green-50 text-green-700 border-green-100', 'bg-green-500', $labelGuru],
                    'guru' => ['bg-blue-50 text-blue-700 border-blue-100', 'bg-blue-500', $labelGuru],
                    'selesai' => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-emerald-500', 'Selesai (Final Piket)']
                ];

                $sc = $statusConfig[$currentStatus] ?? ['bg-gray-100 text-gray-700 border-gray-200', 'bg-gray-400', $labelGuru];
                ?>
                <div class="inline-flex items-center gap-2.5 px-4 py-2 <?= $sc[0] ?> rounded-lg text-sm font-semibold shadow-sm border">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 <?= $sc[1] ?>"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 <?= $sc[1] ?>"></span>
                    </span>
                    <?= $sc[2] ?>
                </div>
            </div>
        </section>

        <!-- PROGRESS SECTION -->
        <section class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-3 transform transition-all duration-700 delay-75 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="flex justify-between items-center">
                <h2 class="text-base font-bold text-gray-900">Progress Kehadiran</h2>
                <span class="text-sm font-bold text-brand-600" id="progressText"><?= $totalTerisi ?> / <?= $totalSiswa ?> siswa terisi</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="bg-brand-600 h-3 rounded-full transition-all duration-1000 ease-out" id="progressBar" style="width: <?= $persentase ?>%"></div>
            </div>
        </section>

        <!-- WORKFLOW SECTION -->
        <section class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm transform transition-all duration-700 delay-100 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1">Workflow Verifikasi</h2>
                <p class="text-sm text-gray-500">Status proses presensi hari ini</p>
            </div>

            <?php
            $statusWorkflow = $kelas['status'] ?? 'draft';
            $step1 = in_array($statusWorkflow, ['sekretaris', 'guru', 'selesai'], true);
            $step2 = in_array($statusWorkflow, ['guru', 'selesai'], true);
            $step3 = ($statusWorkflow === 'selesai');
            ?>

            <div style="all: unset; display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem;" class="md:grid-cols-3">
                <div class="flex items-center gap-3.5 p-4 rounded-xl border transition-all <?= $step1 ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : 'bg-gray-50/80 border-gray-200 text-gray-500' ?>">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 <?= $step1 ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' ?>">
                        <?= $step1 ? '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>' : '1' ?>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Tahap 1</span>
                        <span class="block text-sm font-bold text-gray-900">Sekretaris</span>
                    </div>
                </div>

                <div class="flex items-center gap-3.5 p-4 rounded-xl border transition-all <?= $step2 ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : 'bg-gray-50/80 border-gray-200 text-gray-500' ?>">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 <?= $step2 ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' ?>">
                        <?= $step2 ? '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>' : '2' ?>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Tahap 2</span>
                        <span class="block text-sm font-bold text-gray-900">Wali Kelas / Guru Mapel</span>
                    </div>
                </div>

                <div class="flex items-center gap-3.5 p-4 rounded-xl border transition-all <?= $step3 ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : 'bg-gray-50/80 border-gray-200 text-gray-500' ?>">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 <?= $step3 ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' ?>">
                        <?= $step3 ? '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>' : '3' ?>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Tahap 3</span>
                        <span class="block text-sm font-bold text-gray-900">Guru Piket</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- TABEL SISWA CARD -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Daftar Kehadiran Siswa</h2>
                    <p class="text-sm text-gray-500"><?= (int) $kelas['jumlah_siswa'] ?> siswa aktif terdaftar</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                    <!-- Tombol Hadir Semua -->
                    <button type="button"
                        id="btnHadirSemua"
                        class="px-3.5 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-xl border border-emerald-200 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
                        style="display: none;">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Hadir Semua
                    </button>

                    <!-- Tombol Clear Isian -->
                    <button type="button"
                        id="btnClearSemua"
                        class="px-3.5 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold rounded-xl border border-rose-200 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
                        style="display: none;">
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear Isian
                    </button>

                    <!-- Search Box -->
                    <div class="relative w-full sm:w-56">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                            id="searchSiswa"
                            class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/30 shadow-sm transition-all"
                            placeholder="Cari nama siswa...">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto p-6 pt-2">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4 w-16">No</th>
                            <th class="py-3 px-4 w-36">NIS</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4 w-48">Status Kehadiran</th>
                            <th class="py-3 px-4 w-44">Jam Datang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        <?php
                        $no = 1;
                        foreach ($siswa as $row):
                            $statusPresensi = trim($row['status'] ?? '');
                            if ($statusPresensi === '') {
                                $statusPresensi = 'Belum ada keterangan';
                            }

                            $statusList = ['Hadir', 'Izin', 'Sakit', 'Terlambat', 'Alfa', 'Belum ada keterangan'];
                            $jamValue = '';
                            $menitValue = '';
                            $jamDatabase = '';

                            if (!empty($row['jam_datang'])) {
                                $jamValue = date('H', strtotime($row['jam_datang']));
                                $menitValue = date('i', strtotime($row['jam_datang']));
                                $jamDatabase = date('H:i:s', strtotime($row['jam_datang']));
                            }

                            $selectStyle = "border-gray-200 text-gray-800 bg-white";
                            if ($statusPresensi === 'Hadir') $selectStyle = "border-emerald-300 bg-emerald-50 text-emerald-700 font-bold";
                            elseif ($statusPresensi === 'Izin') $selectStyle = "border-blue-300 bg-blue-50 text-blue-700 font-bold";
                            elseif ($statusPresensi === 'Sakit') $selectStyle = "border-amber-300 bg-amber-50 text-amber-700 font-bold";
                            elseif ($statusPresensi === 'Terlambat') $selectStyle = "border-purple-300 bg-purple-50 text-purple-700 font-bold";
                            elseif ($statusPresensi === 'Alfa') $selectStyle = "border-red-300 bg-red-50 text-red-700 font-bold";
                        ?>
                            <tr class="student-row hover:bg-slate-50/60 transition-colors"
                                data-nama="<?= htmlspecialchars(strtolower($row['nama'])) ?>">

                                <td class="py-4 px-4 font-semibold text-gray-500"><?= $no++ ?></td>
                                <td class="py-4 px-4 font-medium text-gray-600"><?= htmlspecialchars($row['nis']) ?></td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($row['nama']) ?></td>

                                <!-- STATUS -->
                                <td class="py-4 px-4">
                                    <select class="status-select w-full px-3 py-2 border rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500/30 shadow-sm transition-all disabled:bg-gray-50 disabled:text-gray-500 <?= $selectStyle ?>"
                                        data-id="<?= (int) ($row['absensi_id'] ?? 0) ?>"
                                        data-siswa-id="<?= (int) ($row['siswa_id']) ?>"
                                        data-tanggal="<?= htmlspecialchars($kelas['tanggal']) ?>"
                                        disabled>
                                        <?php foreach ($statusList as $itemStatus): ?>
                                            <option value="<?= htmlspecialchars($itemStatus) ?>" class="bg-white text-gray-800 font-medium" <?= $statusPresensi === $itemStatus ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($itemStatus) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- JAM -->
                                <td class="py-4 px-4">
                                    <div class="jam-group flex items-center gap-1.5" style="<?= $statusPresensi === 'Terlambat' ? '' : 'display:none;' ?>">
                                        <select class="jam-select jam-hour w-16 px-2 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:outline-none shadow-sm disabled:bg-gray-50" disabled>
                                            <option value="">--</option>
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <?php $jam = sprintf('%02d', $i); ?>
                                                <option value="<?= $jam ?>" <?= $jam === $jamValue ? 'selected' : '' ?>><?= $jam ?></option>
                                            <?php endfor; ?>
                                        </select>

                                        <span class="font-bold text-gray-400">:</span>

                                        <select class="jam-select jam-minute w-16 px-2 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:outline-none shadow-sm disabled:bg-gray-50" disabled>
                                            <option value="">--</option>
                                            <?php for ($i = 0; $i < 60; $i++): ?>
                                                <?php $menit = sprintf('%02d', $i); ?>
                                                <option value="<?= $menit ?>" <?= $menit === $menitValue ? 'selected' : '' ?>><?= $menit ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" class="jam-input" value="<?= htmlspecialchars($jamDatabase) ?>">
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ACTION FOOTER -->
        <section class="action-footer flex flex-wrap items-center justify-end gap-3 pt-2 pb-12">
            <button type="button"
                class="btn btn-warning px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-sm transition-all cursor-pointer inline-flex items-center gap-2"
                id="btnEdit">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit Data
            </button>

            <button type="button"
                class="btn btn-secondary px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white text-sm font-bold rounded-xl shadow-sm transition-all cursor-pointer"
                id="btnBatal"
                style="display:none;">
                Batal
            </button>

            <button type="button"
                class="btn btn-success px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm transition-all cursor-pointer"
                id="btnSimpan"
                style="display:none;"
                data-id="<?= (int) $kelas['id'] ?>">
                Simpan & Finalisasi
            </button>

            <button type="button"
                class="btn btn-primary px-8 py-3.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-brand-500/30 transition-all cursor-pointer"
                id="btnFinalisasi"
                data-id="<?= (int) $kelas['id'] ?>">
                Finalisasi
            </button>
        </section>

    </div>
</div>

<script>
    function updateJam() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, "0");
        const mm = String(now.getMinutes()).padStart(2, "0");
        const ss = String(now.getSeconds()).padStart(2, "0");
        const target = document.getElementById("jamRealtime");
        if (target) {
            target.textContent = hh + ":" + mm + ":" + ss + " WIB";
        }
    }

    updateJam();
    setInterval(updateJam, 1000);

    function updateProgress() {
        let total = document.querySelectorAll('tbody tr').length;
        let terisi = 0;
        document.querySelectorAll('.status-select').forEach(function(select) {
            let val = select.value;
            if (val && val !== '' && val !== 'Belum ada keterangan') {
                terisi++;
            }
        });
        let persentase = total > 0 ? Math.round((terisi / total) * 100) : 0;
        let progressBar = document.getElementById('progressBar');
        let progressText = document.getElementById('progressText');
        if (progressBar) progressBar.style.width = persentase + '%';
        if (progressText) progressText.textContent = `${terisi} / ${total} siswa terisi`;
    }

    // Update Hidden Jam pada Select Jam/Menit
    document.querySelectorAll(".jam-group").forEach(function(group) {
        const jam = group.querySelector(".jam-hour");
        const menit = group.querySelector(".jam-minute");
        const hidden = group.closest('td').querySelector(".jam-input");

        function updateJamInput() {
            if (jam.value !== "" && menit.value !== "") {
                hidden.value = jam.value + ":" + menit.value + ":00";
            } else {
                hidden.value = "";
            }
        }

        if (jam && menit && hidden) {
            jam.addEventListener("change", updateJamInput);
            menit.addEventListener("change", updateJamInput);
        }
    });

    // Tombol Hadir Semua
    const btnHadirSemua = document.getElementById("btnHadirSemua");
    if (btnHadirSemua) {
        btnHadirSemua.addEventListener("click", function() {
            Swal.fire({
                title: 'Set Hadir Semua?',
                text: 'Tindakan ini akan mengubah status SELURUH siswa menjadi Hadir.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hadirkan Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelectorAll("tbody tr").forEach(function(row) {
                        const status = row.querySelector(".status-select");
                        if (!status || status.disabled) return;

                        status.value = "Hadir";
                        status.dispatchEvent(new Event("change", {
                            bubbles: true
                        }));
                    });
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Semua siswa telah diatur berstatus Hadir.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });
    }

    // Tombol Clear Isian
    const btnClearSemua = document.getElementById("btnClearSemua");
    if (btnClearSemua) {
        btnClearSemua.addEventListener("click", function() {
            Swal.fire({
                title: 'Kosongkan Semua Status?',
                text: 'Tindakan ini akan mereset status kehadiran seluruh siswa menjadi Belum ada keterangan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Kosongkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelectorAll("tbody tr").forEach(function(row) {
                        const status = row.querySelector(".status-select");
                        if (!status || status.disabled) return;
                        status.value = "Belum ada keterangan";
                        status.dispatchEvent(new Event("change", {
                            bubbles: true
                        }));
                    });
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dikosongkan',
                        text: 'Semua isian presensi telah direset.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });
    }

    // Live Search Siswa
    const searchSiswaInput = document.getElementById('searchSiswa');
    if (searchSiswaInput) {
        searchSiswaInput.addEventListener('keyup', function() {
            let keyword = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(function(row) {
                let nama = row.getAttribute('data-nama') || '';
                row.style.display = nama.includes(keyword) ? '' : 'none';
            });
        });
    }

    const btnEdit = document.getElementById('btnEdit');
    const btnBatal = document.getElementById('btnBatal');
    const btnSimpan = document.getElementById('btnSimpan');
    const btnFinalisasi = document.getElementById('btnFinalisasi');

    // Klik Edit
    if (btnEdit) {
        btnEdit.addEventListener('click', function() {
            document.querySelectorAll('.status-select, .jam-hour, .jam-minute').forEach(el => el.disabled = false);

            btnEdit.style.display = 'none';
            if (btnFinalisasi) btnFinalisasi.style.display = 'none';
            if (btnBatal) btnBatal.style.display = 'inline-flex';
            if (btnSimpan) btnSimpan.style.display = 'inline-flex';
            if (btnHadirSemua) btnHadirSemua.style.display = 'inline-flex';
            if (btnClearSemua) btnClearSemua.style.display = 'inline-flex';
        });
    }

    // Klik Batal
    if (btnBatal) {
        btnBatal.addEventListener('click', function() {
            Swal.fire({
                title: 'Batalkan Perubahan?',
                text: 'Perubahan yang belum disimpan tidak akan diterapkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        });
    }

    // Styling dinamis select & Auto-save per baris saat mode edit aktif
    document.querySelectorAll('.status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            let row = this.closest('tr');
            let siswaId = this.getAttribute('data-siswa-id');
            let status = this.value;
            let jamGroup = row.querySelector('.jam-group');
            let hourSelect = row.querySelector('.jam-hour');
            let minuteSelect = row.querySelector('.jam-minute');
            let hiddenInput = row.querySelector('.jam-input');

            select.className = 'status-select w-full px-3 py-2 border rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500/30 shadow-sm transition-all disabled:bg-gray-50 disabled:text-gray-500';
            if (status === 'Hadir') select.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-700', 'font-bold');
            else if (status === 'Izin') select.classList.add('border-blue-300', 'bg-blue-50', 'text-blue-700', 'font-bold');
            else if (status === 'Sakit') select.classList.add('border-amber-300', 'bg-amber-50', 'text-amber-700', 'font-bold');
            else if (status === 'Terlambat') select.classList.add('border-purple-300', 'bg-purple-50', 'text-purple-700', 'font-bold');
            else if (status === 'Alfa') select.classList.add('border-red-300', 'bg-red-50', 'text-red-700', 'font-bold');
            else select.classList.add('border-gray-200', 'bg-white', 'text-gray-800');

            if (status === 'Terlambat') {
                if (jamGroup) jamGroup.style.display = 'flex';
                let now = new Date();
                let hh = String(now.getHours()).padStart(2, '0');
                let mm = String(now.getMinutes()).padStart(2, '0');
                if (hourSelect) hourSelect.value = hh;
                if (minuteSelect) minuteSelect.value = mm;
                if (hiddenInput) hiddenInput.value = hh + ":" + mm + ":00";
            } else {
                if (jamGroup) jamGroup.style.display = 'none';
                if (hourSelect) hourSelect.value = "";
                if (minuteSelect) minuteSelect.value = "";
                if (hiddenInput) hiddenInput.value = "";
            }

            let jamDatang = hiddenInput ? hiddenInput.value : '';
            updateProgress();

            fetch('index.php?route=api/piket/simpan_finalisasi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `siswa_id=${siswaId}&status=${encodeURIComponent(status)}&jam_datang=${encodeURIComponent(jamDatang)}`
            }).then(response => response.json()).then(data => {
                if (data.status !== 'success') {
                    Swal.fire('Gagal', data.message || 'Gagal menyimpan data.', 'error');
                }
            });
        });
    });

    function hitungBelumDiisi() {
        let belumDiisi = 0;
        document.querySelectorAll('tbody tr').forEach(function(row) {
            let select = row.querySelector('.status-select');
            if (select) {
                let val = select.value;
                if (!val || val === '' || val === 'Belum ada keterangan') {
                    belumDiisi++;
                }
            }
        });
        return belumDiisi;
    }

    // Fungsi helper untuk mengirim request finalisasi ke backend
    function prosesFinalisasi(verifikasiId) {
        fetch('index.php?route=api/piket/finalisasi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `verifikasi_id=${verifikasiId}`
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Presensi kelas berhasil difinalisasi.',
                    confirmButtonColor: '#059669'
                }).then(() => {
                    window.location.href = 'index.php?route=piket/finalisasi';
                });
            } else {
                Swal.fire('Gagal', data.message || 'Terjadi kesalahan pada server.', 'error');
            }
        }).catch(() => {
            Swal.fire('Gagal', 'Terjadi kesalahan koneksi.', 'error');
        });
    }

    // Klik Tombol Simpan & Finalisasi (Jika dari mode Edit)
    if (btnSimpan) {
        btnSimpan.addEventListener('click', function() {
            let verifikasiId = this.getAttribute('data-id');
            let belumDiisi = hitungBelumDiisi();

            if (belumDiisi > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Lengkap!',
                    text: `Masih ada ${belumDiisi} siswa yang statusnya belum ada keterangan. Harap lengkapi sebelum finalisasi.`,
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            Swal.fire({
                title: 'Simpan & Finalisasi Kelas?',
                text: 'Perubahan data akan disimpan dan presensi kelas akan langsung difinalisasi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Simpan & Finalisasi!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    prosesFinalisasi(verifikasiId);
                }
            });
        });
    }

    // Klik Tombol Finalisasi Langsung (Tanpa Edit)
    if (btnFinalisasi) {
        btnFinalisasi.addEventListener('click', function() {
            let verifikasiId = this.getAttribute('data-id');
            let belumDiinput = hitungBelumDiisi();

            if (belumDiinput > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Lengkap!',
                    text: `Masih ada ${belumDiinput} siswa yang belum memiliki keterangan kehadiran.`,
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            Swal.fire({
                title: 'Finalisasi Presensi Kelas?',
                text: 'Pastikan data kehadiran sudah benar. Data yang difinalisasi tidak dapat diubah kembali.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Finalisasi Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    prosesFinalisasi(verifikasiId);
                }
            });
        });
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>