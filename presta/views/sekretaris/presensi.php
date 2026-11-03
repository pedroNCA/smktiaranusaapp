<?php
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';
require_once __DIR__ . '/../../app/Services/AttendanceService.php';

Auth::sekretaris();

date_default_timezone_set('Asia/Jakarta');
$tanggal = date('Y-m-d');$hariAngka = date('N'); // 1 (Senin) sampai 7 (Minggu)

// Pemetaan angka hari ke teks database
$namaHariMap = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu'
];$hariIniTeks = $namaHariMap[$hariAngka] ?? 'Senin';

// Sabotase otomatis libur jika hari Sabtu (6) atau Minggu (7)
$isLiburWeekend = ($hariAngka >= 6);

// Ambil pengaturan khusus hari ini dari database
$stmtSetting =$conn->prepare("SELECT jam_mulai, jam_tutup FROM pengaturan_jam_presensi WHERE hari = ? LIMIT 1");
$stmtSetting->bind_param("s", $hariIniTeks);$stmtSetting->execute();
$setting =$stmtSetting->get_result()->fetch_assoc();

$jamSekarang = date('H:i:s');
$mulaiInput  =$setting['jam_mulai'] ?? '06:00:00';
$batasInput  =$setting['jam_tutup'] ?? '07:10:00';

$TEST_MODE = AttendanceService::TEST_MODE;
if ($TEST_MODE || $isLiburWeekend) {$belumMulai = false;
    $inputDitutup =$isLiburWeekend; 
} else {
    $belumMulai = $jamSekarang <$mulaiInput;
    $inputDitutup = $jamSekarang >=$batasInput;
}

$userId = Auth::id();

$stmt =$conn->prepare("SELECT k.id, k.nama_kelas, u.nama_lengkap AS wali_kelas FROM kelas k LEFT JOIN users u ON u.id = k.wali_kelas_id WHERE k.sekretaris_id=?");
$stmt->bind_param("i", $userId);$stmt->execute();
$kelas =$stmt->get_result()->fetch_assoc();
if (!$kelas) die("Sekretaris belum memiliki kelas.");
$kelasId =$kelas['id'];

$stmt =$conn->prepare("SELECT * FROM verifikasi_kelas WHERE kelas_id=? AND tanggal=?");
$stmt->bind_param("is", $kelasId, $tanggal);$stmt->execute();
$workflow =$stmt->get_result()->fetch_assoc() ?: ['status' => 'draft'];
$presensiTerkunci = in_array($workflow['status'], ['sekretaris', 'mapel', 'selesai']);

$stmt =$conn->prepare("SELECT id, nis, nama_siswa, jenis_kelamin FROM siswa WHERE kelas_id=? AND status='Aktif' ORDER BY nama_siswa ASC");
$stmt->bind_param("i", $kelasId);$stmt->execute();
$siswa =$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt =$conn->prepare("SELECT a.siswa_id, a.status, a.jam_datang FROM absensi a INNER JOIN siswa s ON s.id = a.siswa_id WHERE a.tanggal = ? AND s.kelas_id = ?");
$stmt->bind_param("si", $tanggal,$kelasId);
$stmt->execute();$result = $stmt->get_result();$absensi = [];
while ($row = $result->fetch_assoc())$absensi[$row['siswa_id']] =$row;

$summary = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alfa' => 0, 'terlambat' => 0, 'belum' => 0];
foreach ($siswa as$item) {
    if (!isset($absensi[$item['id']])) {$summary['belum']++;
        continue;
    }
    $status = $absensi[$item['id']]['status'];
    $key = strtolower($status);
    if (isset($summary[$key])) $summary[$key]++;
    else $summary['belum']++;
}

$totalSiswa = count($siswa);
$totalTerisi =$summary['hadir'] + $summary['izin'] +$summary['sakit'] + $summary['alfa'] +$summary['terlambat'];
$persentase = ($totalSiswa > 0) ? round(($totalTerisi / $totalSiswa) * 100) : 0;

// --- Memanggil Layout Global ---
$pageTitle = "Input Presensi - PRESTA";
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ================= HERO ================= -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-3 text-white"><?= htmlspecialchars($kelas['nama_kelas']) ?></h1>

                <div class="flex items-center gap-2 text-brand-100 text-sm md:text-base mb-2 font-medium">
                    <h4 class="text-sm md:text-base text-white mb-0">
                        <?php if ($isLiburWeekend): ?>
                            <span class="bg-amber-500 text-white px-2.5 py-0.5 rounded-lg text-xs font-bold">Hari Libur (Weekend)</span> Hari Sabtu & Minggu pengisian presensi libur.
                        <?php else: ?>
                            Waktu Pengisian (<?= $hariIniTeks ?>): <?= substr($mulaiInput, 0, 5) ?> - <?= substr($batasInput, 0, 5) ?> WIB
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="flex items-center gap-2 text-brand-100 text-sm md:text-base font-medium">
                    <svg class="w-5 h-5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="jamRealtime"></span>
                </div>
            </div>
            <div class="rounded-xl p-3 text-center min-w-[130px]">
                <!-- Status Badge -->
                <?php
                $currentStatus = $workflow['status'] ?? 'draft';$statusConfig = [
                    'draft' => ['bg-gray-100 text-gray-700 border-gray-200', 'bg-gray-400', 'Belum Mengisi'],
                    'sekretaris' => ['bg-green-50 text-green-700 border-green-100', 'bg-green-500', 'Sudah Dikirim'],
                    'mapel' => ['bg-blue-50 text-blue-700 border-blue-100', 'bg-blue-500', 'Diverifikasi Guru'],
                    'selesai' => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-emerald-500', 'Selesai']
                ];
                $sc =$statusConfig[$currentStatus] ?? $statusConfig['draft'];
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

        <!-- ================= PROGRESS ================= -->
        <section class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-3 transform transition-all duration-700 delay-75 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="flex justify-between items-center">
                <h2 class="text-base font-bold text-gray-900">Progress Input Presensi</h2>
                <span class="text-sm font-bold text-brand-600"><?= $totalTerisi ?> / <?= $totalSiswa ?> siswa</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="bg-brand-600 h-3 rounded-full transition-all duration-1000 ease-out" style="width: <?= $persentase ?>%"></div>
            </div>
        </section>

        <!-- ================= TABEL PRESENSI ================= -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-200 ease-out" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Daftar Kehadiran Siswa</h2>
                    <p class="text-sm text-gray-500"><?= $totalSiswa ?> siswa aktif terdaftar</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                    <?php $readonly = ($isLiburWeekend ||$belumMulai || $inputDitutup ||$presensiTerkunci); ?>

                    <!-- Tombol Hadir Semua -->
                    <button type="button" id="btnHadirSemua" class="px-3.5 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-xl border border-emerald-200 transition-colors disabled:opacity-50 flex items-center gap-1.5 shadow-sm cursor-pointer" <?= $readonly ? 'disabled' : '' ?>>
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Hadir Semua
                    </button>

                    <!-- Tombol Clear Isian -->
                    <button type="button" id="btnClearSemua" class="px-3.5 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold rounded-xl border border-rose-200 transition-colors disabled:opacity-50 flex items-center gap-1.5 shadow-sm cursor-pointer" <?= $readonly ? 'disabled' : '' ?>>
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear Isian
                    </button>

                    <!-- Search Box -->
                    <div class="relative w-full sm:w-56">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" id="searchSiswa" placeholder="Cari nama siswa..." class="w-full pl-10 pr-4 py-2 bg-gray-50 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 shadow-sm transition-all">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto p-6 pt-2">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4 w-16">No</th>
                            <th class="py-3 px-4 w-28">NIS</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4 w-48">Status Kehadiran</th>
                            <th class="py-3 px-4 w-40">Jam Datang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        <?php $no = 1;
                        $statusList = ['Hadir', 'Izin', 'Sakit', 'Terlambat', 'Alfa'];
                        foreach ($siswa as$row):
                            $current =$absensi[$row['id']]['status'] ?? '';$selectStyle = "border-gray-200 text-gray-800 bg-white";
                            if ($current === 'Hadir')$selectStyle = "border-emerald-300 bg-emerald-50 text-emerald-700 font-bold";
                            elseif ($current === 'Izin')$selectStyle = "border-blue-300 bg-blue-50 text-blue-700 font-bold";
                            elseif ($current === 'Sakit')$selectStyle = "border-amber-300 bg-amber-50 text-amber-700 font-bold";
                            elseif ($current === 'Terlambat')$selectStyle = "border-purple-300 bg-purple-50 text-purple-700 font-bold";
                            elseif ($current === 'Alfa')$selectStyle = "border-red-300 bg-red-50 text-red-700 font-bold";
                        ?>
                            <tr class="student-row hover:bg-slate-50/60 transition-colors" data-siswa="<?= $row['id'] ?>">
                                <td class="py-4 px-4 font-semibold text-gray-500"><?= $no++ ?></td>
                                <td class="py-4 px-4 font-medium text-gray-600"><?= htmlspecialchars($row['nis']) ?></td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                <td class="py-4 px-4">
                                    <select class="form-control status-kehadiran w-full px-3 py-2 border rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition-all shadow-sm cursor-pointer <?= $selectStyle ?>" <?= $readonly ? 'disabled' : '' ?> data-siswa="<?= $row['id'] ?>">
                                        <option value="" class="bg-white text-gray-700 font-normal">Pilih Status</option>
                                        <?php foreach ($statusList as$status): ?>
                                            <option value="<?= $status ?>" class="bg-white text-gray-800 font-medium" <?= $current == $status ? 'selected' : '' ?>><?= $status ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="py-4 px-4">
                                    <input type="time" class="form-control jam-datang w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:outline-none transition-all shadow-sm" data-siswa="<?= $row['id'] ?>" value="<?= $absensi[$row['id']]['jam_datang'] ?? '' ?>" style="<?= $current == 'Terlambat' ? '' : 'display:none;' ?>" <?= ($current != 'Terlambat' || $readonly) ? 'disabled' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                <button id="btnSubmit" data-kelas="<?= $kelasId ?>" class="px-8 py-3.5 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700 shadow-lg disabled:opacity-50 cursor-pointer" <?= $readonly ? 'disabled' : '' ?>>
                    <?= $presensiTerkunci ? 'Sudah Dikirim' : 'Kirim Presensi' ?>
                </button>
            </div>
        </section>
    </div>
</div>

<!-- Script Tambahan Khusus Halaman Presensi -->
<script>
    const jamMulaiConfig = "<?= $mulaiInput ?>"; // Format "HH:MM:SS"
    const jamTutupConfig = "<?= $batasInput ?>"; // Format "HH:MM:SS"
    const isLiburWeekend = <?= $isLiburWeekend ? 'true' : 'false' ?>;
    const testMode = <?= $TEST_MODE ? 'true' : 'false' ?>;
    const presensiTerkunci = <?= $presensiTerkunci ? 'true' : 'false' ?>;

    function getJamSekarangStr() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');
        return `${hh}:${mm}:${ss}`;
    }

    function cekStatusWaktu() {
        if (testMode || isLiburWeekend || presensiTerkunci) {
            if (isLiburWeekend) return { status: 'libur', pesan: 'Hari ini adalah akhir pekan (Sabtu/Minggu), pengisian presensi diliburkan.' };
            if (presensiTerkunci) return { status: 'terkunci', pesan: 'Data presensi hari ini sudah dikirim dan terkunci.' };
            return { status: 'normal', pesan: '' };
        }

        const currentTime = getJamSekarangStr();
        if (currentTime < jamMulaiConfig) {
            return { status: 'belum_mulai', pesan: `Waktu pengisian presensi belum dimulai (Pukul ${jamMulaiConfig.substr(0, 5)} WIB).` };
        }
        if (currentTime >= jamTutupConfig) {
            return { status: 'lewat', pesan: `Batas waktu pengisian presensi hari ini (Pukul ${jamTutupConfig.substr(0, 5)} WIB) telah berakhir.` };
        }
        return { status: 'normal', pesan: '' };
    }

    $(document).ready(function() {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') {
            // Nonaktifkan semua input & tombol jika di luar jam
            $('.status-kehadiran, .jam-datang').prop('disabled', true);$('#btnHadirSemua, #btnClearSemua, #btnSubmit').prop('disabled', true);

            let judulNotif = 'Akses Dibatasi';
            if (cek.status === 'libur') judulNotif = 'Hari Libur Akhir Pekan';
            else if (cek.status === 'belum_mulai') judulNotif = 'Presensi Belum Dibuka';
            else if (cek.status === 'lewat') judulNotif = 'Waktu Presensi Telah Lewat';
            else if (cek.status === 'terkunci') judulNotif = 'Presensi Terkunci';

            Swal.fire({
                icon: 'warning',
                title: judulNotif,
                text: cek.pesan,
                confirmButtonColor: '#2563eb'
            });
        }
    });

    // Live Search
    $('#searchSiswa').on('keyup', function() {
        let keyword = $(this).val().toLowerCase();$('.student-row').each(function() {
            let text = $(this).text().toLowerCase();$(this).toggle(text.includes(keyword));
        });
    });

    // Realtime Clock
    setInterval(() => {
        const now = new Date();
        document.getElementById('jamRealtime').innerText = String(now.getHours()).padStart(2, '0') + ":" + String(now.getMinutes()).padStart(2, '0') + ":" + String(now.getSeconds()).padStart(2, '0') + " WIB";
        
        // Pengecekan real-time dinamis: jika pas jam melewati batas saat halaman sedang dibuka, langsung kunci form & popup
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal' && !$('.status-kehadiran').first().prop('disabled')) {
            $('.status-kehadiran, .jam-datang').prop('disabled', true);$('#btnHadirSemua, #btnClearSemua, #btnSubmit').prop('disabled', true);
            Swal.fire({
                icon: 'warning',
                title: 'Waktu Telah Berakhir',
                text: cek.pesan,
                confirmButtonColor: '#2563eb'
            });
        }
    }, 1000);

    // Mencegah dan memberikan pop-up instan jika diklik di luar jam
    $(document).on('mousedown click focus', '.status-kehadiran, .jam-datang', function(e) {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') {
            e.preventDefault();
            this.blur();
            Swal.fire({
                icon: 'warning',
                title: 'Akses Dibatasi',
                text: cek.pesan,
                confirmButtonColor: '#2563eb'
            });
        }
    });

    // Autosave & Toggle Styling/Time Input on Change
    $(document).on('change', '.status-kehadiran', function() {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') {
            Swal.fire('Akses Dibatasi', cek.pesan, 'warning');
            return;
        }

        let select = $(this);
        let row = select.closest('tr');
        let jam = row.find('.jam-datang');
        let val = select.val();

        select.removeClass('border-gray-200 bg-white text-gray-800 border-emerald-300 bg-emerald-50 text-emerald-700 border-blue-300 bg-blue-50 text-blue-700 border-amber-300 bg-amber-50 text-amber-700 border-purple-300 bg-purple-50 text-purple-700 border-red-300 bg-red-50 text-red-700 font-bold font-normal');

        if (val === 'Hadir') select.addClass('border-emerald-300 bg-emerald-50 text-emerald-700 font-bold');
        else if (val === 'Izin') select.addClass('border-blue-300 bg-blue-50 text-blue-700 font-bold');
        else if (val === 'Sakit') select.addClass('border-amber-300 bg-amber-50 text-amber-700 font-bold');
        else if (val === 'Terlambat') select.addClass('border-purple-300 bg-purple-50 text-purple-700 font-bold');
        else if (val === 'Alfa') select.addClass('border-red-300 bg-red-50 text-red-700 font-bold');
        else select.addClass('border-gray-200 bg-white text-gray-800');

        if (val === 'Terlambat') {
            jam.show().prop('disabled', false);
            if (!jam.val()) {
                let now = new Date();
                jam.val(String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0'));
            }
        } else {
            jam.hide().prop('disabled', true).val('');
        }
        autoSave(row);
    });

    $(document).on('change', '.jam-datang', function() {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') return;
        autoSave($(this).closest('tr'));
    });

    function autoSave(row) {
        $.ajax({
            url: 'index.php?route=api/presensi/simpan',
            type: 'POST',
            data: {
                siswa_id: row.find('.status-kehadiran').data('siswa'),
                status: row.find('.status-kehadiran').val(),
                jam_datang: row.find('.jam-datang').val()
            },
            error: function(xhr) {
                if (xhr.status !== 404) {
                    Swal.fire('Gagal Menyimpan', xhr.responseText || 'Terjadi kesalahan sistem.', 'error');
                }
            }
        });
    }

    function hitungBelumDiisi() {
        let belumDiisi = 0;
        $('.status-kehadiran').each(function() {
            let val = $(this).val();
            if (!val || val === '') {
                belumDiisi++;
            }
        });
        return belumDiisi;
    }

    $('#btnHadirSemua').on('click', function() {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') {
            Swal.fire('Akses Dibatasi', cek.pesan, 'warning');
            return;
        }

        Swal.fire({
            title: 'Set Hadir Semua?',
            text: 'Tindakan ini akan mengubah status seluruh siswa yang belum diabsen menjadi Hadir.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hadirkan Semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.status-kehadiran').each(function() {
                    if (!$(this).val()) {$(this).val('Hadir').trigger('change');
                    }
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

    $('#btnClearSemua').on('click', function() {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') {
            Swal.fire('Akses Dibatasi', cek.pesan, 'warning');
            return;
        }

        Swal.fire({
            title: 'Hapus Semua Isian?',
            text: 'Tindakan ini akan mengosongkan kembali pilihan status kehadiran seluruh siswa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Kosongkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.status-kehadiran').val('').trigger('change');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil dikosongkan',
                    text: 'Semua isian presensi telah direset.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });

    $('#btnSubmit').on('click', function() {
        const cek = cekStatusWaktu();
        if (cek.status !== 'normal') {
            Swal.fire('Akses Dibatasi', cek.pesan, 'warning');
            return;
        }

        let kelasId = $(this).data('kelas');
        let belumDiisi = hitungBelumDiisi();

        if (belumDiisi > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Lengkap!',
                text: `Masih ada ${belumDiisi} siswa yang status kehadirannya belum dipilih. Harap lengkapi seluruh status siswa sebelum mengirim presensi.`,
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        Swal.fire({
            title: 'Kirim Presensi Kelas?',
            text: 'Pastikan data kehadiran sudah benar. Setelah dikirim, laporan akan diteruskan ke Wali Kelas.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Periksa Kembali'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?route=api/presensi/kirim',
                    type: 'POST',
                    data: {
                        kelas_id: kelasId
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Dikirim!',
                            text: 'Data presensi berhasil dikirim ke sistem.',
                            confirmButtonColor: '#2563eb'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal Mengirim', xhr.responseText || 'Terjadi kesalahan pada server.', 'error');
                    }
                });
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>