<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Helpers/DateHelper.php';

Auth::guru();

$pageTitle = 'Rekap Kehadiran Hari Ini';
$userId = $_SESSION['user_id'] ?? 0;
$tanggalHariIni = date('Y-m-d');

// Cek apakah guru ini adalah wali kelas
$stmt = $conn->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$kelasData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelasData) {
    echo "<script>alert('Anda bukan wali kelas!'); window.location='index.php';</script>";
    exit;
}

$kelasId = $kelasData['id'];
$namaKelas = $kelasData['nama_kelas'];

// --- Memanggil Layout Header Global (Mencakup head.php & sidebar.php) ---
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<style>
    .dataTables_wrapper {
        width: 100% !important;
    }

    .dataTables_wrapper table.dataTable {
        width: 100% !important;
        margin: 0 !important;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 1rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        outline: none;
        background-color: #fff;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #2563eb !important;
        color: white !important;
        border: 1px solid #2563eb !important;
        border-radius: 0.5rem !important;
    }
</style>

<!-- Content Area -->
<div class="content-area flex-1 overflow-y-auto p-4 pb-24 sm:p-6 sm:pb-12 lg:p-8 tailwind-scope" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- HERO SECTION -->
        <section class="bg-brand-600 rounded-2xl p-6 lg:p-8 text-white shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 transform transition-all duration-700 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-2 text-white">
                    Rekap Kehadiran Hari Ini
                </h1>
                <p class="text-brand-100 text-sm md:text-base font-medium">
                    <?php
                    $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    echo date('d') . ' ' . $namaBulan[(int)date('n')] . ' ' . date('Y');
                    ?>
                </p>
            </div>

            <!-- Tombol Export -->
            <div class="flex items-center gap-3 flex-wrap">
                <a href="../../export/export_harian_xlsx.php" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export XLSX
                </a>
                <a href="../../export/export_harian_pdf.php" target="printFrame" onclick="setTimeout(() => { window.frames['printFrame'].focus(); window.frames['printFrame'].print(); }, 500);" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export PDF
                </a>
            </div>
        </section>

        <!-- STATISTIK GRID -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 transform transition-all duration-700 delay-150 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Siswa</span>
                <div id="totalSiswa" class="text-xl font-extrabold text-gray-900">0</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Hadir</span>
                <div id="totalHadir" class="text-xl font-extrabold text-emerald-600">0</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Izin</span>
                <div id="totalIzin" class="text-xl font-extrabold text-amber-600">0</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Sakit</span>
                <div id="totalSakit" class="text-xl font-extrabold text-blue-600">0</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Alfa</span>
                <div id="totalAlfa" class="text-xl font-extrabold text-red-600">0</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Terlambat</span>
                <div id="totalTerlambat" class="text-xl font-extrabold text-orange-600">0</div>
            </div>

        </section>

        <!-- LIST TIDAK MASUK & RINGKASAN -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 transform transition-all duration-700 delay-200 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- List Siswa Tidak Masuk -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col h-[320px]">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Siswa Tidak Masuk / Berhalangan</h3>
                    </div>
                    <span id="badgeJumlahTidakMasuk" class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-md text-xs font-bold">0 Siswa</span>
                </div>

                <!-- Kontainer list dengan scroll kustom -->
                <div id="listTidakMasukContainer" class="flex-1 overflow-y-auto pr-1 space-y-2.5">
                    <div class="h-full flex items-center justify-center text-xs text-gray-400 font-medium">
                        Memuat data...
                    </div>
                </div>
            </div>

            <!-- Ringkasan Laporan -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col h-[320px]">
                <div class="flex items-center gap-3 mb-6 pb-2 border-b border-gray-50">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Ringkasan Hari Ini</h3>
                </div>
                <div id="reportSummary" class="space-y-3 flex-1 flex flex-col justify-center">
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Tanggal</span>
                        <strong class="text-gray-900"><?= date('d-m-Y') ?></strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Kelas</span>
                        <strong class="text-gray-900"><?= htmlspecialchars($namaKelas) ?></strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50">
                        <span class="text-gray-500 font-medium">Persentase Hadir</span>
                        <strong id="summaryPersen" class="text-brand-600">0%</strong>
                    </div>
                    <div class="flex items-center justify-between text-sm py-2">
                        <span class="text-gray-500 font-medium">Status</span>
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200">
                            Real-time
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- PREVIEW REKAP ABSENSI -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transform transition-all duration-700 delay-300 ease-out"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="p-6 border-b border-gray-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Preview Kehadiran Siswa</h3>
            </div>

            <div class="p-6 w-full">
                <div id="previewContainer" class="text-sm font-medium w-full">
                    <div class="py-16 text-center text-gray-500 font-medium">
                        <svg class="w-6 h-6 animate-spin mx-auto mb-3 text-brand-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memuat data...
                    </div>
                </div>
            </div>

        </section>

    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    function loadDataHarian() {
        $.ajax({
            url: 'index.php?route=api/laporan', // Sesuaikan dengan route endpoint Anda
            type: 'POST',
            data: {
                sumber_modul: 'rekap_harian_guru'
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    alert(res.message);
                    return;
                }

                $('#previewContainer').html(res.preview);

                // Inisialisasi DataTables
                if ($('#previewContainer table').length > 0) {
                    $('#previewContainer table').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [10, 25, 50, 100],
                        "scrollX": true,
                        "autoWidth": false,
                        "language": {
                            "search": "Cari:",
                            "lengthMenu": "Tampilkan _MENU_ data per halaman",
                            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                            "infoEmpty": "Tidak ada data tersedia",
                            "zeroRecords": "Tidak ditemukan data yang sesuai",
                            "paginate": {
                                "first": "Pertama",
                                "last": "Terakhir",
                                "next": "Selanjutnya",
                                "previous": "Sebelumnya"
                            }
                        }
                    });
                }

                // Isi statistik atas
                $('#totalSiswa').text(res.stats.total_siswa);
                $('#totalHadir').text(res.stats.hadir);
                $('#totalIzin').text(res.stats.izin);
                $('#totalSakit').text(res.stats.sakit);
                $('#totalAlfa').text(res.stats.alfa);
                $('#totalTerlambat').text(res.stats.terlambat);
                $('#summaryPersen').text(res.stats.persentase + '%');

                // Render List Siswa Tidak Masuk
                renderTidakMasukList(res.tidak_masuk_list);
            },
            error: function() {
                alert('Terjadi kesalahan saat memuat data.');
            }
        });
    }

    function renderTidakMasukList(list) {
        const container = $('#listTidakMasukContainer');
        container.empty();

        $('#badgeJumlahTidakMasuk').text(list.length + ' Siswa');

        if (list.length === 0) {
            container.html(`
                <div class="h-full flex flex-col items-center justify-center text-center text-gray-400 py-6">
                    <svg class="w-8 h-8 text-emerald-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs font-semibold text-gray-600">Luar biasa! Semua siswa hadir hari ini.</p>
                </div>
            `);
            return;
        }

        let html = '';
        list.forEach(item => {
            let badgeBg = 'bg-gray-100 text-gray-700';
            let statusLower = item.status.toLowerCase();

            if (statusLower.includes('izin')) badgeBg = 'bg-amber-50 text-amber-700 border border-amber-200';
            else if (statusLower.includes('sakit')) badgeBg = 'bg-blue-50 text-blue-700 border border-blue-200';
            else if (statusLower.includes('alfa')) badgeBg = 'bg-red-50 text-red-700 border border-red-200';
            else if (statusLower.includes('terlambat')) badgeBg = 'bg-orange-50 text-orange-700 border border-orange-200';
            else badgeBg = 'bg-slate-100 text-slate-600 border border-slate-200';

            html += `
                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50/70 border border-gray-100 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-700 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                            ${item.nama.charAt(0).toUpperCase()}
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-gray-900 truncate">${item.nama}</h4>
                            <p class="text-[11px] text-gray-400 font-mono">${item.nis} ${item.keterangan ? '• ' + item.keterangan : ''}</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold shrink-0 ${badgeBg}">
                        ${item.status}
                    </span>
                </div>
            `;
        });

        container.html(html);
    }

    $(function() {
        loadDataHarian();
    });
</script>

<!-- Iframe tersembunyi untuk proses cetak langsung -->
<iframe id="printFrame" name="printFrame" style="display: none;"></iframe>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>