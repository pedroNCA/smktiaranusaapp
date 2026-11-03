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
| TAMBAH SEKRETARIS
|--------------------------------------------------------------------------
*/

if (isset($_POST['tambah'])) {

    $kelas_id = (int)$_POST['kelas_id'];
    $siswa_id = (int)$_POST['siswa_id'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (
        $kelas_id == 0 ||
        $siswa_id == 0 ||
        $username == '' ||
        $password == ''
    ) {

        $pesan = swal(
            'Gagal',
            'Semua data wajib diisi.',
            'error'
        );
    } else {

        $cek = $conn->prepare("
            SELECT id
            FROM users
            WHERE username=?
        ");

        $cek->bind_param("s", $username);
        $cek->execute();

        if ($cek->get_result()->num_rows) {

            $pesan = swal(
                'Gagal',
                'Username sudah digunakan.',
                'error'
            );
        } else {

            $cek = $conn->prepare("
                SELECT id
                FROM users
                WHERE siswa_id=?
            ");

            $cek->bind_param(
                "i",
                $siswa_id
            );

            $cek->execute();

            if ($cek->get_result()->num_rows) {

                $pesan = swal(
                    'Gagal',
                    'Siswa sudah memiliki akun.',
                    'error'
                );
            } else {

                $cek = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE role='sekretaris'
                    AND kelas_id=?
                ");

                $cek->bind_param(
                    "i",
                    $kelas_id
                );

                $cek->execute();

                if ($cek->get_result()->num_rows) {

                    $pesan = swal(
                        'Gagal',
                        'Kelas sudah memiliki sekretaris.',
                        'error'
                    );
                } else {

                    $q = $conn->prepare("
                        SELECT
                        nama_siswa
                        FROM siswa
                        WHERE id=?
                    ");

                    $q->bind_param(
                        "i",
                        $siswa_id
                    );

                    $q->execute();

                    $nama = $q
                        ->get_result()
                        ->fetch_assoc()['nama_siswa'];

                    $hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare("
                        INSERT INTO users
                        (
                            username,
                            password,
                            nama_lengkap,
                            role,
                            kelas_id,
                            siswa_id
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'sekretaris',
                            ?,
                            ?
                        )
                    ");

                    $stmt->bind_param(
                        "sssii",
                        $username,
                        $hash,
                        $nama,
                        $kelas_id,
                        $siswa_id
                    );

                    $stmt->execute();

                    $userId = $conn->insert_id;

                    $update = $conn->prepare("
                        UPDATE kelas
                        SET sekretaris_id = ?
                        WHERE id = ?
                    ");

                    $update->bind_param(
                        "ii",
                        $userId,
                        $kelas_id
                    );

                    $update->execute();

                    $pesan = swal(
                        'Berhasil',
                        'Akun sekretaris berhasil dibuat.',
                        'success'
                    );
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| EDIT SEKRETARIS
|--------------------------------------------------------------------------
*/

if (isset($_POST['edit'])) {

    $id = (int)$_POST['id'];
    $kelas_id = (int)$_POST['kelas_id'];
    $siswa_id = (int)$_POST['siswa_id'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $cek = $conn->prepare("
        SELECT id
        FROM users
        WHERE username=?
        AND id<>?
    ");

    $cek->bind_param(
        "si",
        $username,
        $id
    );

    $cek->execute();

    if ($cek->get_result()->num_rows) {

        $pesan = swal(
            'Gagal',
            'Username sudah digunakan.',
            'error'
        );
    } else {

        $cek = $conn->prepare("
            SELECT id
            FROM users
            WHERE siswa_id=?
            AND id<>?
        ");

        $cek->bind_param(
            "ii",
            $siswa_id,
            $id
        );

        $cek->execute();

        if ($cek->get_result()->num_rows) {

            $pesan = swal(
                'Gagal',
                'Siswa sudah mempunyai akun.',
                'error'
            );
        } else {

            $cek = $conn->prepare("
                SELECT id
                FROM users
                WHERE role='sekretaris'
                AND kelas_id=?
                AND id<>?
            ");

            $cek->bind_param(
                "ii",
                $kelas_id,
                $id
            );

            $cek->execute();

            if ($cek->get_result()->num_rows) {

                $pesan = swal(
                    'Gagal',
                    'Kelas sudah mempunyai sekretaris.',
                    'error'
                );
            } else {

                $q = $conn->prepare("
                    SELECT nama_siswa
                    FROM siswa
                    WHERE id=?
                ");

                $q->bind_param(
                    "i",
                    $siswa_id
                );

                $q->execute();

                $nama = $q
                    ->get_result()
                    ->fetch_assoc()['nama_siswa'];

                if ($password != '') {

                    $hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET
                        username=?,
                        password=?,
                        nama_lengkap=?,
                        kelas_id=?,
                        siswa_id=?
                        WHERE id=?
                    ");

                    $stmt->bind_param(
                        "sssiii",
                        $username,
                        $hash,
                        $nama,
                        $kelas_id,
                        $siswa_id,
                        $id
                    );
                } else {

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET
                        username=?,
                        nama_lengkap=?,
                        kelas_id=?,
                        siswa_id=?
                        WHERE id=?
                    ");

                    $stmt->bind_param(
                        "ssiii",
                        $username,
                        $nama,
                        $kelas_id,
                        $siswa_id,
                        $id
                    );
                }

                $conn->query("
                    UPDATE kelas
                    SET sekretaris_id = NULL
                    WHERE sekretaris_id = {$id}
                ");

                $stmt->execute();

                $update = $conn->prepare("
                    UPDATE kelas
                    SET sekretaris_id = ?
                    WHERE id = ?
                ");

                $update->bind_param(
                    "ii",
                    $id,
                    $kelas_id
                );

                $update->execute();

                $pesan = swal(
                    'Berhasil',
                    'Data sekretaris berhasil diperbarui.',
                    'success'
                );
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS SEKRETARIS
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    $conn->query("
        UPDATE kelas
        SET sekretaris_id = NULL
        WHERE sekretaris_id = {$id}
    ");

    $stmt = $conn->prepare("
        DELETE
        FROM users
        WHERE id=?
        AND role='sekretaris'
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: index.php?route=admin/sekretaris");
    exit;
}

/*
|--------------------------------------------------------------------------
| AJAX AMBIL SISWA BERDASARKAN KELAS
|--------------------------------------------------------------------------
*/

if (isset($_GET['ajax']) && $_GET['ajax'] == 'siswa') {

    header('Content-Type: application/json');

    $kelas = (int)($_GET['kelas_id'] ?? 0);
    $exclude = (int)($_GET['exclude'] ?? 0);
    $siswa = (int)($_GET['siswa_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT
        s.id,
        s.nis,
        s.nama_siswa
        FROM siswa s
        WHERE
        s.kelas_id=?
        AND
        (
            s.id=?
            OR
            NOT EXISTS(
                SELECT 1
                FROM users u
                WHERE
                u.siswa_id=s.id
                AND u.role='sekretaris'
                AND u.id<>?
            )
        )
        ORDER BY s.nama_siswa
    ");

    $stmt->bind_param(
        "iii",
        $kelas,
        $siswa,
        $exclude
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {

        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA KELAS
|--------------------------------------------------------------------------
*/

$listKelas = [];

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

/*
|--------------------------------------------------------------------------
| DATA SEKRETARIS
|--------------------------------------------------------------------------
*/

$qSekretaris = $conn->query("
    SELECT
    u.id,
    u.username,
    u.nama_lengkap,
    u.kelas_id,
    u.siswa_id,
    k.nama_kelas,
    s.nis,
    s.nama_siswa
    FROM users u
    LEFT JOIN kelas k
    ON k.id=u.kelas_id
    LEFT JOIN siswa s
    ON s.id=u.siswa_id
    WHERE
    u.role='sekretaris'
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
    <title>Manajemen Sekretaris | PRESTA</title>
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
                            <h2 class="text-xl font-bold text-gray-900">Manajemen Sekretaris</h2>
                            <p class="text-xs text-gray-500 mt-1">Kelola akun login sekretaris berdasarkan data kelas dan siswa</p>
                        </div>
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm" onclick="openModal('modalTambah')">
                            <i class="fas fa-plus"></i> Tambah Sekretaris
                        </button>
                    </div>

                    <!-- Tabel Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table id="tabelSekretaris" class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                    <th class="py-3 px-4" width="60">No</th>
                                    <th class="py-3 px-4" width="160">Kelas</th>
                                    <th class="py-3 px-4" width="150">NIS</th>
                                    <th class="py-3 px-4">Nama Siswa</th>
                                    <th class="py-3 px-4" width="180">Username</th>
                                    <th class="py-3 px-4 text-center" width="180">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm font-medium">
                                <?php
                                $no = 1;
                                while ($row = $qSekretaris->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-4 px-4 text-gray-500"><?= $no++ ?></td>
                                        <td class="py-4 px-4">
                                            <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-bold">
                                                <?= htmlspecialchars($row['nama_kelas']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-gray-900 font-bold"><?= htmlspecialchars($row['nis']) ?></td>
                                        <td class="py-4 px-4 text-gray-900"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td class="py-4 px-4">
                                            <span class="inline-block px-3 py-1 rounded-full bg-teal-50 text-teal-700 font-mono text-xs font-bold">
                                                <?= htmlspecialchars($row['username']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <div class="inline-flex items-center gap-1.5 justify-center">
                                                <button type="button" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="editSekretaris(
                                                    <?= $row['id'] ?>,
                                                    <?= $row['kelas_id'] ?>,
                                                    <?= $row['siswa_id'] ?>,
                                                    '<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>'
                                                )">
                                                    Edit
                                                </button>
                                                <button type="button" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="hapusSekretaris(
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
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ================= MODAL TAMBAH ================= -->
    <div id="modalTambah" class="modal">
        <div class="max-w-md w-[95%] mx-auto mt-16 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Tambah Akun Sekretaris</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalTambah')">&times;</span>
            </div>
            <form method="POST">
                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kelas</label>
                        <select name="kelas_id" id="kelasTambah" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($listKelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Siswa</label>
                        <select name="siswa_id" id="siswaTambah" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white" required>
                            <option value="">Pilih kelas terlebih dahulu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Username</label>
                        <input type="text" id="usernameTambah" name="username" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Password</label>
                        <div class="relative">
                            <input type="password" id="passwordTambah" name="password" class="w-full h-11 border border-gray-200 rounded-xl px-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Masukkan password" required>
                            <i class="fas fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-600 cursor-pointer text-base" onclick="togglePassword('passwordTambah', this)"></i>
                        </div>
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
        <div class="max-w-md w-[95%] mx-auto mt-16 bg-white rounded-2xl shadow-xl overflow-hidden animate-[showModal_0.2s_ease-out]">
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-base">Edit Akun Sekretaris</h3>
                <span class="text-xl text-gray-400 hover:text-red-500 cursor-pointer transition-colors" onclick="closeModal('modalEdit')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kelas</label>
                        <select name="kelas_id" id="edit_kelas" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($listKelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Siswa</label>
                        <select name="siswa_id" id="edit_siswa" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all bg-white" required>
                            <option value="">Pilih kelas terlebih dahulu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Username</label>
                        <input type="text" id="edit_username" name="username" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Password Baru</label>
                        <div class="relative">
                            <input type="password" id="passwordEdit" name="password" class="w-full h-11 border border-gray-200 rounded-xl px-4 pr-12 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all" placeholder="Kosongkan jika tidak ingin mengganti">
                            <i class="fas fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-600 cursor-pointer text-base" onclick="togglePassword('passwordEdit', this)"></i>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Password lama tetap digunakan jika dikosongkan.</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">Update</button>
                </div>
            </form>
        </div>
    </div>

    <?= $pesan ?>

    <script>
        let tabelSekretaris;

        $(document).ready(function() {
            tabelSekretaris = $('#tabelSekretaris').DataTable({
                responsive: true,
                pageLength: 10,
                autoWidth: false,
                language: {
                    search: "Cari :",
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

        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            $('#' + id).hide();
            if (id == 'modalTambah') {
                $('#kelasTambah').val('');
                $('#siswaTambah').html('<option value="">Pilih kelas terlebih dahulu</option>');
                $('#usernameTambah').val('');
            }
            if (id == 'modalEdit') {
                $('#edit_id').val('');
                $('#edit_kelas').val('');
                $('#edit_siswa').html('<option value="">Pilih kelas terlebih dahulu</option>');
                $('#edit_username').val('');
            }
        }

        window.onclick = function(e) {
            document.querySelectorAll('.modal').forEach(function(modal) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };

        $('#kelasTambah').on('change', function() {
            let kelas = $(this).val();
            $('#siswaTambah').html('<option>Memuat data...</option>');
            $.getJSON('index.php?route=admin/sekretaris', {
                ajax: 'siswa',
                kelas_id: kelas
            }, function(data) {
                let html = '<option value="">-- Pilih Siswa --</option>';
                data.forEach(function(item) {
                    html += '<option value="' + item.id + '" data-nis="' + item.nis + '">' + item.nama_siswa + ' (' + item.nis + ')</option>';
                });
                $('#siswaTambah').html(html);
                $('#usernameTambah').val('');
            });
        });

        $('#siswaTambah').on('change', function() {
            let nis = $(this).find(':selected').data('nis');
            if (nis == undefined) {
                nis = '';
            }
            $('#usernameTambah').val(nis);
        });

        $('#edit_kelas').on('change', function() {
            let kelas = $(this).val();
            $('#edit_siswa').html('<option>Memuat data...</option>');
            $.getJSON('index.php?route=admin/sekretaris', {
                ajax: 'siswa',
                kelas_id: kelas,
                exclude: $('#edit_id').val()
            }, function(data) {
                let html = '<option value="">-- Pilih Siswa --</option>';
                data.forEach(function(item) {
                    html += '<option value="' + item.id + '" data-nis="' + item.nis + '">' + item.nama_siswa + ' (' + item.nis + ')</option>';
                });
                $('#edit_siswa').html(html);
                $('#edit_siswa').off('change').on('change', function() {
                    let nis = $(this).find(':selected').data('nis');
                    $('#edit_username').val(nis);
                });
            });
        });

        $('#edit_siswa').on('change', function() {
            let nis = $(this).find(':selected').data('nis');
            if (nis == undefined) {
                nis = '';
            }
            $('#edit_username').val(nis);
        });

        function editSekretaris(id, kelas, siswa, username) {
            $('#edit_id').val(id);
            $('#edit_kelas').val(kelas);
            $('#edit_username').val(username);
            $('#edit_siswa').html('<option>Memuat data...</option>');

            $.getJSON('index.php?route=admin/sekretaris', {
                ajax: 'siswa',
                kelas_id: kelas,
                exclude: id
            }, function(data) {
                let html = '<option value="">-- Pilih Siswa --</option>';
                data.forEach(function(item) {
                    let selected = '';
                    if (item.id == siswa) {
                        selected = 'selected';
                    }
                    html += '<option value="' + item.id + '" data-nis="' + item.nis + '" ' + selected + '>' + item.nama_siswa + ' (' + item.nis + ')</option>';
                });
                $('#edit_siswa').html(html);
            });

            openModal('modalEdit');
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function hapusSekretaris(id, nama) {
            Swal.fire({
                title: 'Hapus akun sekretaris?',
                html: 'Akun <b>' + nama + '</b> akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?route=admin/sekretaris&hapus=' + id;
                }
            });
        }
    </script>

</body>

</html>