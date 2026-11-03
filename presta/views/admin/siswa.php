<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php?route=auth/login");
    exit;
}

$pesan = '';

function swal($title, $text, $icon)
{
    return "<script>
    Swal.fire({
        title:'{$title}',
        text:'{$text}',
        icon:'{$icon}',
        confirmButtonColor:'#2563eb'
    });
    </script>";
}

if (isset($_POST['tambah'])) {

    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama_siswa']);
    $kelas = (int)$_POST['kelas_id'];

    if ($nis == '' || $nama == '' || $kelas == 0) {

        $pesan = swal(
            'Gagal',
            'Semua data wajib diisi.',
            'error'
        );
    } else {

        $cek = $conn->prepare("
            SELECT id
            FROM siswa
            WHERE nis=?
        ");

        $cek->bind_param("s", $nis);
        $cek->execute();

        if ($cek->get_result()->num_rows) {

            $pesan = swal(
                'Gagal',
                'NIS sudah digunakan.',
                'error'
            );
        } else {

            $stmt = $conn->prepare("
                INSERT INTO siswa
                (
                    nis,
                    nama_siswa,
                    kelas_id
                )
                VALUES
                (?,?,?)
            ");

            $stmt->bind_param(
                "ssi",
                $nis,
                $nama,
                $kelas
            );

            $stmt->execute();

            $pesan = swal(
                'Berhasil',
                'Data siswa berhasil ditambahkan.',
                'success'
            );
        }
    }
}

if (isset($_POST['edit'])) {

    $id = (int)$_POST['id'];
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama_siswa']);
    $kelas = (int)$_POST['kelas_id'];

    $cek = $conn->prepare("
        SELECT id
        FROM siswa
        WHERE nis=?
        AND id<>?
    ");

    $cek->bind_param(
        "si",
        $nis,
        $id
    );

    $cek->execute();

    if ($cek->get_result()->num_rows) {

        $pesan = swal(
            'Gagal',
            'NIS sudah digunakan.',
            'error'
        );
    } else {

        $stmt = $conn->prepare("
            UPDATE siswa
            SET
            nis=?,
            nama_siswa=?,
            kelas_id=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "ssii",
            $nis,
            $nama,
            $kelas,
            $id
        );

        $stmt->execute();

        $pesan = swal(
            'Berhasil',
            'Data siswa berhasil diperbarui.',
            'success'
        );
    }
}

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    $stmt = $conn->prepare("
        DELETE
        FROM absensi
        WHERE siswa_id=?
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $stmt = $conn->prepare("
        DELETE
        FROM siswa
        WHERE id=?
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    header("Location: index.php?route=admin/siswa");
    exit;
}

if (isset($_POST['bulk_delete'])) {

    if (!empty($_POST['siswa_ids'])) {

        foreach ($_POST['siswa_ids'] as $id) {

            $id = (int)$id;

            $stmt = $conn->prepare("
                DELETE
                FROM absensi
                WHERE siswa_id=?
            ");

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $stmt = $conn->prepare("
                DELETE
                FROM siswa
                WHERE id=?
            ");

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();
        }

        $pesan = swal(
            'Berhasil',
            'Data berhasil dihapus.',
            'success'
        );
    }
}

if (isset($_POST['import'])) {

    if (isset($_FILES['file_excel'])) {

        $ext = strtolower(
            pathinfo(
                $_FILES['file_excel']['name'],
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($ext, ['xlsx', 'xls'])) {

            $pesan = swal(
                'Gagal',
                'Format harus XLS/XLSX.',
                'error'
            );
        } else {

            $excel = IOFactory::load(
                $_FILES['file_excel']['tmp_name']
            );

            $rows = $excel
                ->getActiveSheet()
                ->toArray();

            $berhasil = 0;

            foreach ($rows as $i => $row) {

                if ($i == 0) continue;

                $nis = trim($row[0]);
                $nama = trim($row[1]);
                $kelasNama = trim($row[2]);

                if (
                    $nis == '' ||
                    $nama == '' ||
                    $kelasNama == ''
                ) {
                    continue;
                }

                $cekKelas = $conn->prepare("
                    SELECT id
                    FROM kelas
                    WHERE nama_kelas=?
                ");

                $cekKelas->bind_param(
                    "s",
                    $kelasNama
                );

                $cekKelas->execute();

                $kelas = $cekKelas
                    ->get_result()
                    ->fetch_assoc();

                if (!$kelas) {

                    continue;
                }

                $cek = $conn->prepare("
                    SELECT id
                    FROM siswa
                    WHERE nis=?
                ");

                $cek->bind_param(
                    "s",
                    $nis
                );

                $cek->execute();

                if ($cek->get_result()->num_rows) {

                    continue;
                }

                $stmt = $conn->prepare("
                    INSERT INTO siswa
                    (
                        nis,
                        nama_siswa,
                        kelas_id
                    )
                    VALUES
                    (?,?,?)
                ");

                $stmt->bind_param(
                    "ssi",
                    $nis,
                    $nama,
                    $kelas['id']
                );

                if ($stmt->execute()) {

                    $berhasil++;
                }
            }

            $pesan = swal(
                'Berhasil',
                $berhasil . ' siswa berhasil diimport.',
                'success'
            );
        }
    }
}

$listKelas = [];

$qSummary = $conn->query("
SELECT
k.id,
k.nama_kelas,
COUNT(s.id) total
FROM kelas k
LEFT JOIN siswa s
ON s.kelas_id=k.id
GROUP BY
k.id,
k.nama_kelas
ORDER BY
k.nama_kelas
");

$q = $conn->query("
SELECT
id,
nama_kelas
FROM kelas
ORDER BY nama_kelas
");

while ($r = $q->fetch_assoc()) {

    $listKelas[] = $r;
}

$q_siswa = $conn->query("
SELECT
s.*,
k.nama_kelas
FROM siswa s
LEFT JOIN kelas k
ON k.id=s.kelas_id
ORDER BY
k.nama_kelas,
s.nama_siswa
");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Siswa | PRESTA</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>

    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 9999;
        }

        @keyframes showModal {
            from {
                transform: translateY(-15px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-slate-50 antialiased text-gray-800">

    <!-- Wrapper Utama Layout Admin -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <!-- Konten Kanan (Topbar + Isi Halaman) -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Topbar -->
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <!-- Area Konten Utama -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Header Halaman -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Manajemen Data Siswa</h2>
                            <p class="text-xs text-gray-500 mt-1">Kelola data siswa serta import Excel</p>
                        </div>
                    </div>

                    <!-- Filter Dropdown Kelas Modern & Tombol Aksi -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">

                        <!-- Dropdown Filter Kelas -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                <i class="fas fa-filter text-sm"></i>
                            </div>
                            <div class="w-full sm:w-72">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Filter Berdasarkan Kelas</label>
                                <select id="filterKelas" class="w-full h-11 border border-gray-200 rounded-xl px-3 text-sm font-semibold text-gray-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white cursor-pointer">
                                    <option value="">Semua Kelas (<?= $q_siswa->num_rows ?> Siswa)</option>
                                    <?php
                                    $qSummary->data_seek(0);
                                    while ($k = $qSummary->fetch_assoc()):
                                    ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?> (<?= $k['total'] ?> Siswa)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" onclick="openModal('modalTambah')">
                                <i class="fas fa-plus"></i> Tambah Siswa
                            </button>
                            <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" onclick="openModal('modalImport')">
                                <i class="fas fa-file-excel"></i> Import Excel
                            </button>
                            <a href="index.php?route=admin/includes/template_siswa" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fas fa-download"></i> Template
                            </a>
                            <button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" onclick="hapusMassal()">
                                <i class="fas fa-trash-alt"></i> Hapus Terpilih
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
                        <form id="formBulkDelete" method="POST">
                            <input type="hidden" name="bulk_delete" value="1">
                            <table id="tabelSiswa" class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-4 text-center" width="40"><input type="checkbox" id="checkAll" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></th>
                                        <th class="py-3 px-4" width="60">No</th>
                                        <th class="py-3 px-4" width="180">NIS</th>
                                        <th class="py-3 px-4">Nama Siswa</th>
                                        <th class="py-3 px-4" width="180">Kelas</th>
                                        <th style="display:none">kelas_id</th>
                                        <th class="py-3 px-4 text-center" width="180">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                    <?php
                                    $no = 1;
                                    $q_siswa->data_seek(0);
                                    while ($row = $q_siswa->fetch_assoc()):
                                    ?>
                                        <tr class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-4 px-4 text-center">
                                                <input type="checkbox" class="checkItem rounded border-gray-300 text-brand-600 focus:ring-brand-500" name="siswa_ids[]" value="<?= $row['id'] ?>">
                                            </td>
                                            <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                            <td class="py-4 px-4 text-gray-900 font-bold"><?= htmlspecialchars($row['nis']) ?></td>
                                            <td class="py-4 px-4 text-gray-900"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                            <td class="py-4 px-4">
                                                <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-bold">
                                                    <?= htmlspecialchars($row['nama_kelas']) ?>
                                                </span>
                                            </td>
                                            <td style="display:none"><?= $row['kelas_id'] ?></td>
                                            <td class="py-4 px-4 text-center">
                                                <div class="inline-flex items-center gap-1.5 justify-center">
                                                    <button type="button" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="editSiswa(
                                                        <?= $row['id'] ?>,
                                                        '<?= htmlspecialchars($row['nis'], ENT_QUOTES) ?>',
                                                        '<?= htmlspecialchars($row['nama_siswa'], ENT_QUOTES) ?>',
                                                        <?= $row['kelas_id'] ?>
                                                    )">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="hapusSiswa(
                                                        <?= $row['id'] ?>,
                                                        '<?= htmlspecialchars($row['nama_siswa'], ENT_QUOTES) ?>'
                                                    )">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div id="modalTambah" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Tambah Siswa</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalTambah')">&times;</span>
            </div>
            <form method="POST">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">NIS</label>
                        <input type="text" name="nis" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" maxlength="50" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_siswa" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kelas</label>
                        <select name="kelas_id" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($listKelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="tambah" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="modalEdit" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Edit Siswa</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalEdit')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">NIS</label>
                        <input type="text" name="nis" id="edit_nis" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" maxlength="50" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_siswa" id="edit_nama" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kelas</label>
                        <select name="kelas_id" id="edit_kelas" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white">
                            <?php foreach ($listKelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL IMPORT -->
    <div id="modalImport" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Import Excel</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalImport')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">File Excel (.xlsx / .xls)</label>
                        <input type="file" name="file_excel" class="w-full border border-gray-200 rounded-xl p-2 text-sm bg-slate-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" accept=".xlsx,.xls" required>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-gray-100 text-xs text-gray-500 space-y-1">
                        <p class="font-bold text-gray-700 mb-1">Format Excel:</p>
                        <p>Kolom A = NIS</p>
                        <p>Kolom B = Nama Siswa</p>
                        <p>Kolom C = Nama Kelas</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalImport')">Batal</button>
                    <button type="submit" name="import" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <?= $pesan ?>

    <!-- Scripts -->
    <script>
        $(document).ready(function() {
            const table = $('#tabelSiswa').DataTable({
                responsive: true,
                pageLength: 10,
                autoWidth: false,
                language: {
                    search: "",
                    searchPlaceholder: "Cari Nama atau NIS...",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    zeroRecords: "Data tidak ditemukan",
                    infoEmpty: "Tidak ada data",
                    paginate: {
                        previous: "←",
                        next: "→"
                    }
                },
                columnDefs: [{
                        orderable: false,
                        targets: [0, 6]
                    },
                    {
                        targets: [5],
                        visible: false,
                        searchable: true
                    }
                ]
            });

            $('#filterKelas').on('change', function() {
                let kelas = $(this).val();
                if (kelas === "") {
                    table.column(5).search('').draw();
                } else {
                    table.column(5).search('^' + kelas + '$', true, false).draw();
                }
            });

            $('#checkAll').on('click', function() {
                $('.checkItem').prop('checked', this.checked);
            });
        });

        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(e) {
            document.querySelectorAll('.modal').forEach(function(m) {
                if (e.target === m) {
                    m.style.display = 'none';
                }
            });
        }

        function editSiswa(id, nis, nama, kelas) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nis').value = nis;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kelas').value = kelas;
            openModal('modalEdit');
        }

        function hapusSiswa(id, nama) {
            Swal.fire({
                title: 'Hapus Data?',
                html: 'Data <b>' + nama + '</b><br>akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal'
            }).then((r) => {
                if (r.isConfirmed) {
                    location.href = 'index.php?route=admin/siswa&hapus=' + id;
                }
            });
        }

        function hapusMassal() {
            let data = [];
            document.querySelectorAll('.checkItem:checked').forEach(function(item) {
                data.push(item.value);
            });

            if (data.length === 0) {
                Swal.fire('Info', 'Pilih minimal satu siswa.', 'info');
                return;
            }

            Swal.fire({
                title: 'Hapus Data?',
                text: data.length + ' siswa akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal'
            }).then((r) => {
                if (r.isConfirmed) {
                    document.getElementById('formBulkDelete').submit();
                }
            });
        }
    </script>
</body>

</html>