<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::piket();

date_default_timezone_set('Asia/Jakarta');

$pageTitle = "Detail Presensi Kelas (Guru Piket)";
$kelasId = $_GET['kelas_id'] ?? null;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

if (!$kelasId || !is_numeric($kelasId)) {
    header("Location: index.php?route=guru_piket/presensi");
    exit;
}

// Ambil Informasi Kelas
$stmt = $conn->prepare("SELECT * FROM kelas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $kelasId);
$stmt->execute();
$kelasInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelasInfo) {
    header("Location: index.php?route=guru_piket/presensi");
    exit;
}

// Ambil Data Verifikasi Workflow jika sudah ada
$stmt = $conn->prepare("SELECT * FROM verifikasi_kelas WHERE kelas_id = ? AND tanggal = ? LIMIT 1");
$stmt->bind_param("is", $kelasId, $tanggal);
$stmt->execute();
$workflow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$currentStatus = $workflow['status'] ?? 'draft';
$workflowId = $workflow['id'] ?? null;

// Ambil Daftar Siswa beserta Absensinya pada Tanggal Tersebut
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
$stmt->bind_param("si", $tanggal, $kelasId);
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

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">

            <div class="flex-1">
                <a href="index.php?route=piket/presensi&tanggal=<?= htmlspecialchars($tanggal) ?>" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-lg mb-4 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Kelas
                </a>

                <h1 class="text-2xl md:text-3xl font-bold mb-3 text-white">
                    Presensi Kelas <?= htmlspecialchars($kelasInfo['nama_kelas']) ?>
                </h1>

                <div class="flex items-center gap-4 text-brand-100 text-sm md:text-base font-medium">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span><?= DateHelper::tanggal($tanggal) ?></span>
                    </div>
                    <span>•</span>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="jamRealtime"></span>
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="rounded-xl p-3 text-center min-w-[130px]">
                <?php
                $statusConfig = [
                    'draft' => ['bg-gray-100 text-gray-700 border-gray-200', 'bg-gray-400', 'Draft / Belum Diisi'],
                    'sekretaris' => ['bg-amber-50 text-amber-700 border-amber-100', 'bg-amber-500', 'Menunggu Verifikasi'],
                    'guru' => ['bg-blue-50 text-blue-700 border-blue-100', 'bg-blue-500', 'Diverifikasi Guru'],
                    'selesai' => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-emerald-500', 'Selesai (Final)']
                ];
                $sc = $statusConfig[$currentStatus] ?? ['bg-gray-100 text-gray-700 border-gray-200', 'bg-gray-400', 'Draft'];
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

        <!-- TABEL SISWA CARD -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Daftar Kehadiran Siswa</h2>
                    <p class="text-sm text-gray-500"><?= (int) $totalSiswa ?> siswa aktif terdaftar</p>
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
                        foreach ($siswa as$row):
                            $statusPresensi = trim($row['status'] ?? '');
                            if ($statusPresensi === '') {$statusPresensi = 'Belum ada keterangan';
                            }

                            $statusList = ['Hadir', 'Izin', 'Sakit', 'Terlambat', 'Alfa', 'Belum ada keterangan'];$jamValue = '';
                            $menitValue = '';$jamDatabase = '';

                            if (!empty($row['jam_datang'])) {
                                $jamValue = date('H', strtotime($row['jam_datang']));
                                $menitValue = date('i', strtotime($row['jam_datang']));
                                $jamDatabase = date('H:i:s', strtotime($row['jam_datang']));
                            }

                            $selectStyle = "border-gray-200 text-gray-800 bg-white";
                            if ($statusPresensi === 'Hadir')$selectStyle = "border-emerald-300 bg-emerald-50 text-emerald-700 font-bold";
                            elseif ($statusPresensi === 'Izin')$selectStyle = "border-blue-300 bg-blue-50 text-blue-700 font-bold";
                            elseif ($statusPresensi === 'Sakit')$selectStyle = "border-amber-300 bg-amber-50 text-amber-700 font-bold";
                            elseif ($statusPresensi === 'Terlambat')$selectStyle = "border-purple-300 bg-purple-50 text-purple-700 font-bold";
                            elseif ($statusPresensi === 'Alfa')$selectStyle = "border-red-300 bg-red-50 text-red-700 font-bold";
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
                                        data-tanggal="<?= htmlspecialchars($tanggal) ?>"
                                        disabled>
                                        <?php foreach ($statusList as$itemStatus): ?>
                                            <option value="<?= htmlspecialchars($itemStatus) ?>" class="bg-white text-gray-800 font-medium" <?= $statusPresensi ===$itemStatus ? 'selected' : '' ?>>
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
                                                <?php $jam = sprintf('%02d',$i); ?>
                                                <option value="<?= $jam ?>" <?= $jam === $jamValue ? 'selected' : '' ?>><?= $jam ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <span class="font-bold text-gray-400">:</span>
                                        <select class="jam-select jam-minute w-16 px-2 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:outline-none shadow-sm disabled:bg-gray-50" disabled>
                                            <option value="">--</option>
                                            <?php for ($i = 0; $i < 60; $i++): ?>
                                                <?php $menit = sprintf('%02d',$i); ?>
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
                data-kelas="<?= (int) $kelasId ?>"
                data-tanggal="<?= htmlspecialchars($tanggal) ?>">
                Simpan Perubahan
            </button>

            <button type="button"
                class="btn btn-primary px-8 py-3.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-brand-500/30 transition-all cursor-pointer"
                id="btnFinalisasiPiket"
                data-kelas="<?= (int) $kelasId ?>"
                data-tanggal="<?= htmlspecialchars($tanggal) ?>">
                Finalisasi (Selesai)
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
    document.getElementById("btnHadirSemua")?.addEventListener("click", function() {
        Swal.fire({
            title: 'Set Hadir Semua?',
            text: 'Mengubah status SELURUH siswa menjadi Hadir.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hadirkan Semua!'
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
                    text: 'Semua siswa diatur Hadir.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });

    // Tombol Clear Isian
    document.getElementById("btnClearSemua")?.addEventListener("click", function() {
        Swal.fire({
            title: 'Kosongkan Status?',
            text: 'Mereset status kehadiran seluruh siswa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Kosongkan!'
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
                    title: 'Dikosongkan',
                    text: 'Isian direset.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });

    // Live Search Siswa
    document.getElementById('searchSiswa')?.addEventListener('keyup', function() {
        let keyword = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(function(row) {
            let nama = row.getAttribute('data-nama') || '';
            row.style.display = nama.includes(keyword) ? '' : 'none';
        });
    });

    // Tombol Edit
    const btnEdit = document.getElementById('btnEdit');
    const btnBatal = document.getElementById('btnBatal');
    const btnSimpan = document.getElementById('btnSimpan');
    const btnFinalisasiPiket = document.getElementById('btnFinalisasiPiket');

    btnEdit?.addEventListener('click', function() {
        document.querySelectorAll('.status-select, .jam-hour, .jam-minute').forEach(el => el.disabled = false);
        btnEdit.style.display = 'none';
        if (btnFinalisasiPiket) btnFinalisasiPiket.style.display = 'none';
        if (btnBatal) btnBatal.style.display = 'inline-flex';
        if (btnSimpan) btnSimpan.style.display = 'inline-flex';
        document.getElementById('btnHadirSemua').style.display = 'inline-flex';
        document.getElementById('btnClearSemua').style.display = 'inline-flex';
    });

    btnBatal?.addEventListener('click', function() {
        location.reload();
    });

    // Auto-save per baris saat status berubah (Opsional atau bisa dilewati krn sudah ada Simpan Massal)
    document.querySelectorAll('.status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            let row = this.closest('tr');
            let status = this.value;
            let jamGroup = row.querySelector('.jam-group');
            let hourSelect = row.querySelector('.jam-hour');
            let minuteSelect = row.querySelector('.jam-minute');
            let hiddenInput = row.querySelector('.jam-input');

            // Styling dinamis
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
            updateProgress();
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

    // Fungsi pembantu untuk mengumpulkan semua data baris siswa
    function gatherPresensiData() {
        let data = [];
        document.querySelectorAll('tbody tr').forEach(function(row) {
            let select = row.querySelector('.status-select');
            let hiddenInput = row.querySelector('.jam-input');
            if (select) {
                data.push({
                    siswa_id: select.getAttribute('data-siswa-id'),
                    status: select.value,
                    jam_datang: hiddenInput ? hiddenInput.value : ''
                });
            }
        });
        return data;
    }

    // Tombol Simpan Perubahan / Finalisasi Langsung
    btnSimpan?.addEventListener('click', function() {
        kirimDataPresensi('Simpan Perubahan?');
    });

    btnFinalisasiPiket?.addEventListener('click', function() {
        kirimDataPresensi('Finalisasi Presensi Kelas?');
    });

    function kirimDataPresensi(titleText) {
        let kelasId = btnSimpan.getAttribute('data-kelas') || btnFinalisasiPiket.getAttribute('data-kelas');
        let tanggal = btnSimpan.getAttribute('data-tanggal') || btnFinalisasiPiket.getAttribute('data-tanggal');
        let belumDiisi = hitungBelumDiisi();

        if (belumDiisi > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Lengkap!',
                text: `Masih ada ${belumDiisi} siswa yang belum memiliki keterangan status kehadiran.`,
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        Swal.fire({
            title: titleText,
            text: 'Data presensi akan disimpan dan status kelas akan langsung diperbarui.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Proses Sekarang!'
        }).then((result) => {
            if (result.isConfirmed) {
                let absensiArray = gatherPresensiData();

                // Kirim via Fetch ke API massal & verifikasi
                fetch('index.php?route=api/piket/simpan_dan_verifikasi', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        kelas_id: kelasId,
                        tanggal: tanggal,
                        absensi: absensiArray
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'index.php?route=piket/presensi&tanggal=' + tanggal;
                        });
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Gagal', 'Terjadi kesalahan koneksi ke server.', 'error');
                });
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>