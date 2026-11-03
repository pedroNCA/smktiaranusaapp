<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

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

/*
|--------------------------------------------------------------------------
| Tambah Kelas
|--------------------------------------------------------------------------
*/

if (isset($_POST['tambah'])) {

    $nama = trim($_POST['nama_kelas']);
    $wali = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : NULL;

    $stmt = $conn->prepare("
        SELECT id
        FROM kelas
        WHERE nama_kelas=?
    ");

    $stmt->bind_param("s", $nama);
    $stmt->execute();

    if ($stmt->get_result()->num_rows) {

        $pesan = swal(
            'Gagal',
            'Nama kelas sudah ada.',
            'error'
        );
    } else {

        if ($wali) {

            $cek = $conn->prepare("
                SELECT id
                FROM kelas
                WHERE wali_kelas_id=?
            ");

            $cek->bind_param("i", $wali);
            $cek->execute();

            if ($cek->get_result()->num_rows) {

                $pesan = swal(
                    'Gagal',
                    'Guru sudah menjadi wali kelas.',
                    'error'
                );
            } else {

                $stmt = $conn->prepare("
                    INSERT INTO kelas
                    (
                        nama_kelas,
                        wali_kelas_id
                    )
                    VALUES
                    (?,?)
                ");

                $stmt->bind_param(
                    "si",
                    $nama,
                    $wali
                );

                $stmt->execute();

                $pesan = swal(
                    'Berhasil',
                    'Kelas berhasil ditambahkan.',
                    'success'
                );
            }
        } else {

            $stmt = $conn->prepare("
                INSERT INTO kelas
                (
                    nama_kelas,
                    wali_kelas_id
                )
                VALUES
                (?,NULL)
            ");

            $stmt->bind_param(
                "s",
                $nama
            );

            $stmt->execute();

            $pesan = swal(
                'Berhasil',
                'Kelas berhasil ditambahkan.',
                'success'
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Edit Kelas
|--------------------------------------------------------------------------
*/

if (isset($_POST['edit'])) {

    $id = (int)$_POST['id'];
    $nama = trim($_POST['nama_kelas']);
    $wali = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : NULL;

    $stmt = $conn->prepare("
        SELECT id
        FROM kelas
        WHERE nama_kelas=?
        AND id<>?
    ");

    $stmt->bind_param(
        "si",
        $nama,
        $id
    );

    $stmt->execute();

    if ($stmt->get_result()->num_rows) {

        $pesan = swal(
            'Gagal',
            'Nama kelas sudah dipakai.',
            'error'
        );
    } else {

        if ($wali) {

            $cek = $conn->prepare("
                SELECT id
                FROM kelas
                WHERE wali_kelas_id=?
                AND id<>?
            ");

            $cek->bind_param(
                "ii",
                $wali,
                $id
            );

            $cek->execute();

            if ($cek->get_result()->num_rows) {

                $pesan = swal(
                    'Gagal',
                    'Guru sudah menjadi wali kelas.',
                    'error'
                );
            } else {

                $stmt = $conn->prepare("
                    UPDATE kelas
                    SET
                    nama_kelas=?,
                    wali_kelas_id=?
                    WHERE id=?
                ");

                $stmt->bind_param(
                    "sii",
                    $nama,
                    $wali,
                    $id
                );

                $stmt->execute();

                $pesan = swal(
                    'Berhasil',
                    'Data kelas diperbarui.',
                    'success'
                );
            }
        } else {

            $stmt = $conn->prepare("
                UPDATE kelas
                SET
                nama_kelas=?,
                wali_kelas_id=NULL
                WHERE id=?
            ");

            $stmt->bind_param(
                "si",
                $nama,
                $id
            );

            $stmt->execute();

            $pesan = swal(
                'Berhasil',
                'Data kelas diperbarui.',
                'success'
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Hapus Kelas
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    $stmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM siswa
        WHERE kelas_id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $total = $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    if ($total > 0) {

        $pesan = swal(
            'Gagal',
            'Kelas masih memiliki siswa.',
            'error'
        );
    } else {

        $stmt = $conn->prepare("
            DELETE
            FROM kelas
            WHERE id=?
        ");

        $stmt->bind_param(
            "i",
            $id
        );

        $stmt->execute();

        header("Location: index.php?route=admin/kelas");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Ambil Data
|--------------------------------------------------------------------------
*/

$listGuru = [];

$q = $conn->query("
SELECT
id,
nama_lengkap
FROM users
WHERE role='guru'
ORDER BY nama_lengkap
");

while ($r = $q->fetch_assoc()) {

    $listGuru[] = $r;
}

$q_kelas = $conn->query("
SELECT
k.id,
k.nama_kelas,
k.wali_kelas_id,
u.nama_lengkap nama_wali,
(
SELECT COUNT(*)
FROM siswa s
WHERE s.kelas_id=k.id
) jumlah_siswa
FROM kelas k
LEFT JOIN users u
ON u.id=k.wali_kelas_id
ORDER BY k.nama_kelas
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelas | PRESTA</title>
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
                            <h2 class="text-xl font-bold text-gray-900">Manajemen Kelas</h2>
                            <p class="text-xs text-gray-500 mt-1">Kelola data kelas dan wali kelas</p>
                        </div>
                        <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" onclick="openTambah()">
                            <i class="fas fa-plus"></i> Tambah Kelas
                        </button>
                    </div>

                    <!-- Tabel Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table id="tabelKelas" class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                    <th class="py-3 px-4" width="60">No</th>
                                    <th class="py-3 px-4">Nama Kelas</th>
                                    <th class="py-3 px-4">Wali Kelas</th>
                                    <th class="py-3 px-4" width="130">Jumlah Siswa</th>
                                    <th class="py-3 px-4 text-center" width="200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                <?php
                                $no = 1;
                                while ($row = $q_kelas->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                        <td class="py-4 px-4 text-gray-900 font-bold">
                                            <?= htmlspecialchars($row['nama_kelas']) ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php if ($row['nama_wali']): ?>
                                                <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                                    <?= htmlspecialchars($row['nama_wali']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-block px-3 py-1 rounded-full bg-red-100 text-red-600 text-xs font-bold">
                                                    Belum Ada
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-gray-600">
                                            <?= $row['jumlah_siswa'] ?> Siswa
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <div class="inline-flex items-center gap-1.5 justify-center">
                                                <button class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                                                    onclick="editKelas(
                                                        <?= $row['id'] ?>,
                                                        '<?= htmlspecialchars($row['nama_kelas'], ENT_QUOTES) ?>',
                                                        '<?= $row['wali_kelas_id'] ?>'
                                                    )">
                                                    Edit
                                                </button>
                                                <button class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                                                    onclick="hapusKelas(
                                                        <?= $row['id'] ?>,
                                                        '<?= htmlspecialchars($row['nama_kelas'], ENT_QUOTES) ?>'
                                                    )">
                                                    Hapus
                                                </button>
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

    <!-- MODAL TAMBAH -->
    <div id="modalTambah" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Tambah Kelas</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalTambah')">&times;</span>
            </div>

            <form method="POST">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Contoh: XII IPA 1" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Wali Kelas</label>
                        <select name="wali_kelas_id" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($listGuru as $guru): ?>
                                <option value="<?= $guru['id'] ?>">
                                    <?= htmlspecialchars($guru['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalTambah')">
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="modalEdit" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Edit Kelas</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalEdit')">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Kelas</label>
                        <input type="text" name="nama_kelas" id="edit_nama" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Wali Kelas</label>
                        <select name="wali_kelas_id" id="edit_wali" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($listGuru as $guru): ?>
                                <option value="<?= $guru['id'] ?>">
                                    <?= htmlspecialchars($guru['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalEdit')">
                        Batal
                    </button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?= $pesan ?>

    <!-- Scripts -->
    <script>
        $(document).ready(function() {
            $('#tabelKelas').DataTable({
                responsive: true,
                pageLength: 10,
                autoWidth: false,
                language: {
                    search: "",
                    searchPlaceholder: "Cari Kelas...",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    zeroRecords: "Data tidak ditemukan",
                    infoEmpty: "Tidak ada data",
                    paginate: {
                        previous: "←",
                        next: "→"
                    }
                }
            });
        });

        function openTambah() {
            document.getElementById('modalTambah').style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function editKelas(id, nama, wali) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_wali').value = wali;
            document.getElementById('modalEdit').style.display = 'block';
        }

        function hapusKelas(id, nama) {
            Swal.fire({
                title: 'Hapus Kelas?',
                html: 'Kelas <b>' + nama + '</b> akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.href = 'index.php?route=admin/kelas&hapus=' + id;
                }
            });
        }

        window.onclick = function(e) {
            document.querySelectorAll('.modal').forEach(function(modal) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>

</body>

</html>