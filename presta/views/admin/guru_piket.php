<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Keamanan: Pastikan yang akses adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// 1. LOGIKA HAPUS MASSAL (BULK DELETE)
if (isset($_POST['bulk_delete']) && !empty($_POST['guru_ids'])) {
    $ids = $_POST['guru_ids'];
    $safe_ids = array_map(function ($id) use ($conn) {
        return $conn->real_escape_string($id);
    }, $ids);
    $id_list = "'" . implode("','", $safe_ids) . "'";

    $conn->begin_transaction();

    try {
        $conn->query("
        DELETE FROM absensi_verifikasi
        WHERE verified_by IN ($id_list)
    ");

        $conn->query("
        DELETE FROM users
        WHERE id IN ($id_list)
        AND role='piket'
    ");

        $conn->commit();

        $pesan = "<script>
        Swal.fire(
            'Berhasil!',
            'Akun Guru Piket terpilih berhasil dihapus.',
            'success'
        );
    </script>";
    } catch (Exception $e) {
        $conn->rollback();

        $pesan = "<script>
        Swal.fire(
            'Gagal!',
            'Masih ada data yang berhubungan dengan Guru Piket tersebut.',
            'error'
        );
    </script>";
    }
}

// ==========================================
// 1. LOGIKA EXPORT CSV
// ==========================================
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Guru_Piket_' . date('Ymd') . '.csv');
    $output = fopen("php://output", "w");

    fputcsv($output, array('Nama Lengkap', 'Username'));

    $query = "
    SELECT
        nama_lengkap,
        username
    FROM users
    WHERE role='piket'
    ORDER BY nama_lengkap ASC
    ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// ==========================================
// 2. LOGIKA IMPORT
// ==========================================
if (isset($_POST['import'])) {
    if (!isset($_FILES['file_excel'])) {
        $pesan = swal('Gagal', 'Pilih file Excel.', 'error');
    } else {
        $ext = strtolower(
            pathinfo(
                $_FILES['file_excel']['name'],
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $pesan = swal('Gagal', 'Format harus XLS atau XLSX.', 'error');
        } else {
            $spreadsheet = IOFactory::load(
                $_FILES['file_excel']['tmp_name']
            );

            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $berhasil = 0;

            foreach ($rows as $index => $row) {
                if ($index == 0) continue;

                $nama = trim($row[0]);
                $username = trim($row[1]);
                $password = trim($row[2]);

                if ($nama == '' || $username == '' || $password == '') {
                    continue;
                }

                $cek = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE username=?
                ");
                $cek->bind_param("s", $username);
                $cek->execute();

                if ($cek->get_result()->num_rows) {
                    continue;
                }

                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (
                        nama_lengkap,
                        username,
                        password,
                        role
                    )
                    VALUES
                    (?, ?, ?, 'piket')
                ");
                $stmt->bind_param("sss", $nama, $username, $hash);

                if ($stmt->execute()) {
                    $berhasil++;
                }
            }

            $pesan = swal(
                'Berhasil',
                $berhasil . ' Guru Piket berhasil diimport.',
                'success'
            );
        }
    }
}

// ==========================================
// 3. LOGIKA TAMBAH DATA Guru Piket
// ==========================================
if (isset($_POST['tambah'])) {
    $nama = $conn->real_escape_string($_POST['nama_lengkap']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $cek = $conn->query("SELECT id FROM users WHERE username='$username'");
    if ($cek->num_rows > 0) {
        $pesan = "<script>Swal.fire('Gagal!', 'Username sudah digunakan oleh akun lain!', 'error');</script>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("
            INSERT INTO users
            (
                nama_lengkap,
                username,
                password,
                role
            )
            VALUES
            (
                '$nama',
                '$username',
                '$hashed_password',
                'piket'
            )
        ");
        $pesan = "<script>Swal.fire('Berhasil!', 'Akun Guru Piket ditambahkan.', 'success');</script>";
    }
}

// ==========================================
// 4. LOGIKA EDIT DATA GURU
// ==========================================
if (isset($_POST['edit'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $nama = $conn->real_escape_string($_POST['nama_lengkap']);
    $username = $conn->real_escape_string($_POST['username']);
    $password_baru = $_POST['password'];

    $cek = $conn->query("SELECT id FROM users WHERE username='$username' AND id != '$id'");
    if ($cek->num_rows > 0) {
        $pesan = "<script>Swal.fire('Gagal!', 'Username sudah dipakai oleh pengguna lain!', 'error');</script>";
    } else {
        if (!empty($password_baru)) {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET nama_lengkap='$nama', username='$username', password='$hashed_password' WHERE id='$id'");
        } else {
            $conn->query("UPDATE users SET nama_lengkap='$nama', username='$username' WHERE id='$id'");
        }
        $pesan = "<script>Swal.fire('Berhasil!', 'Data dan akun Guru Piket diperbarui.', 'success');</script>";
    }
}

// ==========================================
// 5. LOGIKA HAPUS DATA Guru Piket
// ==========================================
if (isset($_GET['hapus'])) {
    $id = $conn->real_escape_string($_GET['hapus']);

    $conn->begin_transaction();

    try {
        $conn->query("
        DELETE FROM absensi_verifikasi
        WHERE verified_by = '$id'
    ");

        $conn->query("
        DELETE FROM users
        WHERE id = '$id'
    ");

        $conn->commit();

        header("Location: index.php?route=admin/guru_piket&pesan=hapus_sukses");
        exit;
    } catch (Exception $e) {
        $conn->rollback();

        $pesan = swal(
            'Gagal',
            'Guru Piket tidak dapat dihapus karena masih digunakan oleh data lain.',
            'error'
        );
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus_sukses') {
    $pesan = "<script>Swal.fire('Dihapus!', 'Akun Guru Piket telah dihapus.', 'success');</script>";
}

// Ambil Data Seluruh Guru Piket untuk Tabel
$q_guru = $conn->query("
    SELECT
        id,
        nama_lengkap,
        username
    FROM users
    WHERE role='piket'
    ORDER BY nama_lengkap ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru Piket - PRESTA Admin</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
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
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Manajemen Data Guru Piket</h2>
                            <p class="text-xs text-gray-500 mt-1">Kelola akun dan hak akses Guru Piket</p>
                        </div>

                        <!-- Tombol Aksi Header -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <button onclick="openModal('modalTambah')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fas fa-plus"></i> Tambah Guru Piket
                            </button>
                            <a href="index.php?route=admin/includes/template_guru" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                            <button onclick="openModal('modalImport')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fas fa-file-excel"></i> Import Data
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">

                        <!-- Tombol Hapus Massal -->
                        <div>
                            <button type="button" id="btnHapusMassal" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" style="display:none;" onclick="konfirmasiHapusMassal()">
                                <i class="fas fa-trash-alt mr-1"></i> Hapus Data Terpilih
                            </button>
                        </div>

                        <form id="formBulkDelete" method="POST" action="">
                            <input type="hidden" name="bulk_delete" value="1">
                            <div class="overflow-x-auto">
                                <table id="tabelGuru" class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                            <th class="py-3 px-4 text-center" width="5%"><input type="checkbox" id="checkAll" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></th>
                                            <th class="py-3 px-4" width="7%">No</th>
                                            <th class="py-3 px-4" width="38%">Nama Lengkap</th>
                                            <th class="py-3 px-4" width="30%">Username</th>
                                            <th class="py-3 px-4 text-center" width="20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                        <?php $no = 1;
                                        $q_guru = $conn->query("SELECT * FROM users WHERE role='piket'");
                                        while ($row = $q_guru->fetch_assoc()): ?>
                                            <tr class="hover:bg-slate-50/60 transition-colors">
                                                <td class="py-4 px-4 text-center">
                                                    <input type="checkbox" name="guru_ids[]" value="<?= $row['id'] ?>" class="checkItem rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                </td>
                                                <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                                <td class="py-4 px-4 text-gray-900 font-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                                <td class="py-4 px-4"><code class="bg-slate-100 px-2 py-1 rounded text-xs text-gray-600"><?= htmlspecialchars($row['username']) ?></code></td>
                                                <td class="py-4 px-4 text-center">
                                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                                        <button type="button" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="editGuru('<?= $row['id'] ?>', '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>', '<?= addslashes(htmlspecialchars($row['username'])) ?>')">Edit</button>
                                                        <button type="button" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="konfirmasiHapus('<?= $row['id'] ?>', '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>')">Hapus</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ================= MODAL TAMBAH ================= -->
    <div id="modalTambah" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Tambah Akun Guru Piket</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalTambah')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Lengkap Guru Piket</label>
                        <input type="text" name="nama_lengkap" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Username</label>
                        <input type="text" name="username" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Password</label>
                        <input type="password" name="password" id="passTambah" class="w-full h-11 border border-gray-200 rounded-xl pl-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Minimal 6 karakter" required>
                        <span class="absolute right-4 top-9 cursor-pointer text-gray-400 hover:text-brand-600 select-none" onclick="togglePassword('passTambah')"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="tambah" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= MODAL EDIT ================= -->
    <div id="modalEdit" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Edit Akun Guru Piket</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalEdit')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Lengkap Guru Piket</label>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Username</label>
                        <input type="text" name="username" id="edit_username" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Ganti Password <span class="text-red-500 font-normal lowercase">(opsional)</span></label>
                        <input type="password" name="password" id="passEdit" class="w-full h-11 border border-gray-200 rounded-xl pl-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Kosongkan jika tidak ingin ganti">
                        <span class="absolute right-4 top-9 cursor-pointer text-gray-400 hover:text-brand-600 select-none" onclick="togglePassword('passEdit')"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= MODAL IMPORT ================= -->
    <div id="modalImport" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-20 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Import Data Guru Piket</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalImport')">&times;</span>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">File Excel (.xlsx / .xls)</label>
                        <input type="file" name="file_excel" class="w-full border border-gray-200 rounded-xl p-2 text-sm bg-slate-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" accept=".xlsx,.xls" required>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-gray-100 text-xs text-gray-500 space-y-1">
                        <p class="font-bold text-gray-700 mb-1">Ketentuan Import:</p>
                        <p>1. Jangan mengubah nama kolom pada baris pertama.</p>
                        <p>2. Pastikan username bernilai unik (belum terdaftar).</p>
                        <p>3. Password minimal 6 karakter.</p>
                        <p>4. Jangan menghapus baris header.</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalImport')">Batal</button>
                    <button type="submit" name="import" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cetak Notifikasi -->
    <?= $pesan ?>

    <!-- Scripts -->
    <script>
        $(document).ready(function() {
            $('#tabelGuru').DataTable({
                responsive: true,
                pageLength: 10,
                autoWidth: false,
                language: {
                    search: "",
                    searchPlaceholder: "Cari Guru Piket...",
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

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editGuru(id, nama, username) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_username').value = username;
            openModal('modalEdit');
        }

        function konfirmasiHapus(id, nama) {
            Swal.fire({
                title: 'Yakin hapus Guru Piket?',
                text: "Akun " + nama + " akan dihapus secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?route=admin/guru_piket&hapus=' + id;
                }
            })
        }

        function togglePassword(id) {
            var x = document.getElementById(id);
            x.type = (x.type === "password") ? "text" : "password";
        }

        // Check All
        $('#checkAll').click(function() {
            $('.checkItem').prop('checked', this.checked);
            toggleBtnHapusMassal();
        });

        $(document).on('click', '.checkItem', function() {
            toggleBtnHapusMassal();
        });

        function toggleBtnHapusMassal() {
            if ($('.checkItem:checked').length > 0) {
                $('#btnHapusMassal').show();
            } else {
                $('#btnHapusMassal').hide();
            }
        }

        function konfirmasiHapusMassal() {
            Swal.fire({
                title: 'Hapus Massal?',
                text: "Data yang dipilih akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#formBulkDelete').submit();
                }
            })
        }
    </script>
</body>

</html>