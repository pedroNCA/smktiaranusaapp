<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php?route=auth/login');
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

function redirect($url)
{
    header("Location: {$url}");
    exit;
}

function formatTanggalIndonesia($tanggal)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $time = strtotime($tanggal);

    return date('d', $time)
        . ' '
        . $bulan[(int) date('n', $time)]
        . ' '
        . date('Y', $time);
}

/*
|--------------------------------------------------------------------------
| Tambah Tahun Pelajaran
|--------------------------------------------------------------------------
*/

if (isset($_POST['tambah'])) {

    $nama_tahun = trim($_POST['nama_tahun']);

    if ($nama_tahun == '') {
        $pesan = swal('Gagal', 'Nama tahun pelajaran wajib diisi.', 'error');
    } else {

        $stmt = $conn->prepare("
            SELECT id
            FROM tahun_ajaran
            WHERE nama_tahun=?
            LIMIT 1
        ");

        $stmt->bind_param("s", $nama_tahun);
        $stmt->execute();

        if ($stmt->get_result()->num_rows) {

            $pesan = swal(
                'Gagal',
                'Tahun pelajaran sudah tersedia.',
                'error'
            );
        } else {

            $tanggal_mulai   = $_POST['tanggal_mulai'];
            $tanggal_selesai = $_POST['tanggal_selesai'];
            $status = 'Tidak Aktif';

            if (strtotime($tanggal_mulai) >= strtotime($tanggal_selesai)) {

                $pesan = swal(
                    'Gagal',
                    'Tanggal selesai harus lebih besar dari tanggal mulai.',
                    'error'
                );
            } else {

                $stmt = $conn->prepare("
        INSERT INTO tahun_ajaran
        (
            nama_tahun,
            tanggal_mulai,
            tanggal_selesai,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

                $stmt->bind_param(
                    "ssss",
                    $nama_tahun,
                    $tanggal_mulai,
                    $tanggal_selesai,
                    $status
                );

                if ($stmt->execute()) {

                    $pesan = swal(
                        'Berhasil',
                        'Data berhasil ditambahkan.',
                        'success'
                    );
                } else {

                    $pesan = swal(
                        'Gagal',
                        'Terjadi kesalahan database.',
                        'error'
                    );
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Edit Tahun Pelajaran
|--------------------------------------------------------------------------
*/

if (isset($_POST['edit'])) {

    $id = (int)$_POST['id'];
    $nama_tahun = trim($_POST['nama_tahun']);

    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];

    if (strtotime($tanggal_mulai) >= strtotime($tanggal_selesai)) {

        $pesan = swal(
            'Gagal',
            'Tanggal selesai harus lebih besar dari tanggal mulai.',
            'error'
        );
    } else {

        $stmt = $conn->prepare("
        SELECT id
        FROM tahun_ajaran
        WHERE nama_tahun=?
        AND id<>?
        LIMIT 1
    ");

        $stmt->bind_param(
            "si",
            $nama_tahun,
            $id
        );

        $stmt->execute();

        if ($stmt->get_result()->num_rows) {

            $pesan = swal(
                'Gagal',
                'Nama tahun pelajaran sudah digunakan.',
                'error'
            );
        } else {

            $stmt = $conn->prepare("
            UPDATE tahun_ajaran
SET
    nama_tahun=?,
    tanggal_mulai=?,
    tanggal_selesai=?
WHERE id=?
        ");

            $stmt->bind_param(
                "sssi",
                $nama_tahun,
                $tanggal_mulai,
                $tanggal_selesai,
                $id
            );

            if ($stmt->execute()) {

                $pesan = swal(
                    'Berhasil',
                    'Data berhasil diperbarui.',
                    'success'
                );
            } else {

                $pesan = swal(
                    'Gagal',
                    'Data gagal diperbarui.',
                    'error'
                );
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Aktifkan Tahun Pelajaran
|--------------------------------------------------------------------------
*/

if (isset($_GET['set_aktif'])) {

    $id = (int)$_GET['set_aktif'];

    try {

        $conn->begin_transaction();

        $conn->query("
            UPDATE tahun_ajaran
            SET status='Tidak Aktif'
        ");

        $stmt = $conn->prepare("
            UPDATE tahun_ajaran
            SET status='Aktif'
            WHERE id=?
        ");

        $stmt->bind_param(
            "i",
            $id
        );

        $stmt->execute();

        $conn->commit();

        redirect(
            'index.php?route=admin/tahun_ajaran&pesan=aktif'
        );
    } catch (Exception $e) {

        $conn->rollback();

        $pesan = swal(
            'Gagal',
            $e->getMessage(),
            'error'
        );
    }
}

if (isset($_GET['set_nonaktif'])) {

    $id = (int)$_GET['set_nonaktif'];

    $stmt = $conn->prepare("
        UPDATE tahun_ajaran
        SET status='Tidak Aktif'
        WHERE id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: index.php?route=admin/tahun_ajaran");
    exit;
}

/*
|--------------------------------------------------------------------------
| Hapus Tahun Pelajaran
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    $stmt = $conn->prepare("
        SELECT status
        FROM tahun_ajaran
        WHERE id=?
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $status = $stmt
        ->get_result()
        ->fetch_assoc();

    if (!$status) {

        $pesan = swal(
            'Gagal',
            'Data tidak ditemukan.',
            'error'
        );
    } elseif ($status['status'] == 'Aktif') {

        $pesan = swal(
            'Gagal',
            'Tahun pelajaran aktif tidak boleh dihapus.',
            'error'
        );
    } else {

        $stmt = $conn->prepare("
            DELETE
            FROM tahun_ajaran
            WHERE id=?
        ");

        $stmt->bind_param(
            "i",
            $id
        );

        if ($stmt->execute()) {

            redirect(
                'index.php?route=admin/tahun_ajaran&pesan=hapus'
            );
        } else {

            $pesan = swal(
                'Gagal',
                'Data gagal dihapus.',
                'error'
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| SweetAlert Redirect
|--------------------------------------------------------------------------
*/

if (isset($_GET['pesan'])) {

    switch ($_GET['pesan']) {

        case 'aktif':
            $pesan = swal(
                'Berhasil',
                'Tahun pelajaran berhasil diaktifkan.',
                'success'
            );
            break;

        case 'hapus':
            $pesan = swal(
                'Berhasil',
                'Data berhasil dihapus.',
                'success'
            );
            break;
    }
}

/*
|--------------------------------------------------------------------------
| Load Data
|--------------------------------------------------------------------------
*/

$q_tahun = $conn->query("
    SELECT
        id,
        nama_tahun,
        tanggal_mulai,
        tanggal_selesai,
        status
    FROM tahun_ajaran
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Tahun Pelajaran | PRESTA</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>

    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 99;
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

    <!-- Wrapper Utama Flexbox -->
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

                    <!-- Toolbar / Header Halaman -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Tahun Pelajaran</h2>
                            <p class="text-xs text-gray-500 mt-1">Kelola tahun pelajaran yang digunakan oleh sistem PRESTA</p>
                        </div>
                        <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" onclick="openModal('modalTambah')">
                            <i class="fas fa-plus"></i>
                            Tambah Tahun Pelajaran
                        </button>
                    </div>

                    <!-- Tabel Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table id="tabelTahun" class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                    <th class="py-3 px-4" width="60">No</th>
                                    <th class="py-3 px-4">Tahun Pelajaran</th>
                                    <th class="py-3 px-4" width="240">Periode</th>
                                    <th class="py-3 px-4" width="170">Status</th>
                                    <th class="py-3 px-4 text-center" width="260">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                <?php
                                $no = 1;
                                while ($row = $q_tahun->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors <?= $row['status'] == 'Aktif' ? 'bg-emerald-50/40' : '' ?>">
                                        <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                        <td class="py-4 px-4 text-gray-900 font-bold">
                                            <?= htmlspecialchars($row['nama_tahun']) ?>
                                        </td>
                                        <td class="py-4 px-4 text-gray-600 text-xs leading-relaxed">
                                            <?= formatTanggalIndonesia($row['tanggal_mulai']) ?>
                                            <br>
                                            <span class="text-gray-400">s/d <?= formatTanggalIndonesia($row['tanggal_selesai']) ?></span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php if ($row['status'] == 'Aktif'): ?>
                                                <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                                    Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-block px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">
                                                    Tidak Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <div class="inline-flex items-center gap-1.5 flex-wrap justify-center">
                                                <?php if ($row['status'] == 'Aktif'): ?>
                                                    <button class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                                                        onclick="setNonaktif(<?= $row['id'] ?>,'<?= htmlspecialchars($row['nama_tahun'], ENT_QUOTES) ?>')">
                                                        Nonaktifkan
                                                    </button>
                                                <?php else: ?>
                                                    <button class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                                                        onclick="setAktif(<?= $row['id'] ?>,'<?= htmlspecialchars($row['nama_tahun'], ENT_QUOTES) ?>')">
                                                        Aktifkan
                                                    </button>
                                                <?php endif; ?>

                                                <button class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                                                    onclick="editData(
                                                        <?= $row['id'] ?>,
                                                        '<?= htmlspecialchars($row['nama_tahun'], ENT_QUOTES) ?>',
                                                        '<?= $row['tanggal_mulai'] ?>',
                                                        '<?= $row['tanggal_selesai'] ?>'
                                                    )">
                                                    Edit
                                                </button>

                                                <?php if ($row['status'] == 'Aktif'): ?>
                                                    <button class="px-3 py-1.5 bg-red-400 text-white text-xs font-bold rounded-lg opacity-50 cursor-not-allowed shadow-sm"
                                                        disabled
                                                        title="Tahun pelajaran yang aktif tidak dapat dihapus">
                                                        Hapus
                                                    </button>
                                                <?php else: ?>
                                                    <button class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                                                        onclick="hapusData(
                                                            <?= $row['id'] ?>,
                                                            '<?= htmlspecialchars($row['nama_tahun'], ENT_QUOTES) ?>'
                                                        )">
                                                        Hapus
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal" id="modalTambah">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Tambah Tahun Pelajaran</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalTambah')">&times;</span>
            </div>

            <form method="POST">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Tahun Pelajaran</label>
                        <input type="text" name="nama_tahun" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Contoh : 2026/2027 Ganjil" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalTambah')">
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal" id="modalEdit">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Edit Tahun Pelajaran</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalEdit')">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Tahun Pelajaran</label>
                        <input type="text" id="edit_nama" name="nama_tahun" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Mulai</label>
                        <input type="date" id="edit_mulai" name="tanggal_mulai" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Selesai</label>
                        <input type="date" id="edit_selesai" name="tanggal_selesai" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalEdit')">
                        Batal
                    </button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?= $pesan ?>

    <script>
        $(function() {
            $('#tabelTahun').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: false,
                autoWidth: false,
                language: {
                    search: "",
                    searchPlaceholder: "Cari Tahun Pelajaran...",
                    zeroRecords: "Tidak ada data.",
                    info: "Menampilkan _TOTAL_ data",
                    infoEmpty: "",
                    paginate: {
                        previous: "←",
                        next: "→"
                    }
                }
            });
        });

        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(e) {
            document.querySelectorAll('.modal').forEach(function(modal) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        function editData(id, nama, mulai, selesai) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_mulai').value = mulai;
            document.getElementById('edit_selesai').value = selesai;
            openModal('modalEdit');
        }

        function setAktif(id, nama) {
            Swal.fire({
                title: 'Aktifkan Tahun Pelajaran?',
                html: '<b>' + nama + '</b><br><br>Tahun pelajaran aktif sebelumnya akan dinonaktifkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Aktifkan',
                cancelButtonText: 'Batal'
            }).then((r) => {
                if (r.isConfirmed) {
                    location.href = 'index.php?route=admin/tahun_ajaran&set_aktif=' + id;
                }
            });
        }

        function hapusData(id, nama) {
            Swal.fire({
                title: 'Hapus Tahun Pelajaran?',
                html: '<b>' + nama + '</b><br><br>Data yang sudah dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((r) => {
                if (r.isConfirmed) {
                    location.href = 'index.php?route=admin/tahun_ajaran&hapus=' + id;
                }
            });
        }

        function setNonaktif(id, nama) {
            Swal.fire({
                title: 'Nonaktifkan Tahun Pelajaran?',
                text: 'Tahun ' + nama + ' akan dinonaktifkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#f59e0b'
            }).then((r) => {
                if (r.isConfirmed) {
                    location.href = 'index.php?route=admin/tahun_ajaran&set_nonaktif=' + id;
                }
            });
        }
    </script>
</body>

</html>