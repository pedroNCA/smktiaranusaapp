<?php

/**
 * Halaman Kelola User & Akses (Kepala Sekolah)
 * Termasuk CRUD, Enkripsi Password, Validasi Duplikasi, Export/Import XLSX, Search, Filter, Pagination, & Bulk Delete
 */
require_once '../../includes/kepsek_header.php';
require_once '../../includes/kepsek_sidebar.php';
require_once '../../includes/kepsek_navbar.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** @var PDO $pdo */

$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// 1. PROSES EXPORT EXCEL (.XLSX)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        $stmt = $pdo->query("SELECT nama_lengkap, username, email, role, bidang FROM tb_users ORDER BY role, nama_lengkap");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pengguna');

        $headers = ['Nama Lengkap', 'Username', 'Email', 'Role / Peran', 'Bidang Struktural'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        $rowNum = 2;
        foreach ($users as $u) {
            $sheet->setCellValue('A' . $rowNum, $u['nama_lengkap']);
            $sheet->setCellValue('B' . $rowNum, $u['username']);
            $sheet->setCellValue('C' . $rowNum, $u['email'] ?? '-');
            $sheet->setCellValue('D' . $rowNum, $u['role']);
            $sheet->setCellValue('E' . $rowNum, $u['bidang']);
            $rowNum++;
        }

        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="data_pengguna_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        $pesan_error = "Gagal melakukan export: " . $e->getMessage();
    }
}

// ==========================================
// 2. PROSES CRUD, BULK DELETE, & IMPORT EXCEL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    // BULK DELETE (HAPUS BERDASARKAN CHECKLIST)
    if ($aksi === 'hapus_banyak') {
        $ids = $_POST['selected_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            try {
                // Cegah penghapusan akun Kepala Sekolah pada level query
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM tb_users WHERE id_user IN ($placeholders) AND role != 'Kepala Sekolah'");
                $stmt->execute($ids);
                $deletedCount = $stmt->rowCount();

                if ($deletedCount > 0) {
                    $pesan_sukses = "$deletedCount akun pengguna berhasil dihapus. (Akun Kepala Sekolah diabaikan).";
                } else {
                    $pesan_error = "Tidak ada akun yang dihapus. (Akun Kepala Sekolah tidak dapat dihapus).";
                }
            } catch (Exception $e) {
                // Tangkap error constraint foreign key (1451)
                if (strpos($e->getMessage(), '1451') !== false || strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                    $pesan_error = "<strong>Gagal menghapus:</strong> Beberapa akun yang Anda pilih tidak dapat dihapus karena masih terikat sebagai pembuat/PIC pada data laporan. Silakan hapus laporan mereka terlebih dahulu.";
                } else {
                    $pesan_error = "Gagal menghapus data terpilih: " . $e->getMessage();
                }
            }
        } else {
            $pesan_error = "Tidak ada akun yang dipilih untuk dihapus.";
        }
    }
    // TAMBAH USER SATUAN
    elseif ($aksi === 'tambah') {
        $username     = trim($_POST['username']);
        $email        = trim($_POST['email']);
        $password     = $_POST['password'];
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $role         = $_POST['role'];
        $bidang       = trim($_POST['bidang']);

        if (strlen($password) < 6) {
            $pesan_error = "Password minimal 6 karakter.";
        } else {
            try {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_users WHERE username = :username OR email = :email");
                $stmtCheck->execute([':username' => $username, ':email' => empty($email) ? '-' : $email]);
                if ($stmtCheck->fetchColumn() > 0) {
                    throw new Exception("Peringatan: Username '$username' atau Email '$email' sudah terdaftar di sistem!");
                }

                $password_hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO tb_users (username, email, password, nama_lengkap, role, bidang) VALUES (:username, :email, :password, :nama, :role, :bidang)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => empty($email) ? null : $email,
                    ':password' => $password_hashed,
                    ':nama' => $nama_lengkap,
                    ':role' => $role,
                    ':bidang' => $bidang
                ]);
                $pesan_sukses = "Akun pengguna baru berhasil ditambahkan.";
            } catch (Exception $e) {
                $pesan_error = $e->getMessage();
            }
        }
    }
    // EDIT USER
    elseif ($aksi === 'edit') {
        $id_user       = $_POST['id_user'];
        $username      = trim($_POST['username']);
        $email         = trim($_POST['email']);
        $nama_lengkap  = trim($_POST['nama_lengkap']);
        $role          = $_POST['role'];
        $bidang        = trim($_POST['bidang']);
        $password_baru = $_POST['password_baru'];

        try {
            if (!empty($email)) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_users WHERE (username = :username OR email = :email) AND id_user != :id");
                $stmtCheck->execute([':username' => $username, ':email' => $email, ':id' => $id_user]);
            } else {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_users WHERE username = :username AND id_user != :id");
                $stmtCheck->execute([':username' => $username, ':id' => $id_user]);
            }

            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Peringatan: Username '$username' atau Email '$email' sudah digunakan oleh akun lain!");
            }

            if (!empty($password_baru)) {
                $password_hashed = password_hash($password_baru, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE tb_users SET username = :username, email = :email, password = :password, nama_lengkap = :nama, role = :role, bidang = :bidang WHERE id_user = :id");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => empty($email) ? null : $email,
                    ':password' => $password_hashed,
                    ':nama' => $nama_lengkap,
                    ':role' => $role,
                    ':bidang' => $bidang,
                    ':id' => $id_user
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE tb_users SET username = :username, email = :email, nama_lengkap = :nama, role = :role, bidang = :bidang WHERE id_user = :id");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => empty($email) ? null : $email,
                    ':nama' => $nama_lengkap,
                    ':role' => $role,
                    ':bidang' => $bidang,
                    ':id' => $id_user
                ]);
            }
            $pesan_sukses = "Data akun berhasil diperbarui.";
        } catch (Exception $e) {
            $pesan_error = $e->getMessage();
        }
    }
    // HAPUS SATUAN
    elseif ($aksi === 'hapus') {
        $id_user = $_POST['id_user'];

        try {
            // Cek terlebih dahulu role user yang akan dihapus
            $stmtCekRole = $pdo->prepare("SELECT role FROM tb_users WHERE id_user = :id");
            $stmtCekRole->execute([':id' => $id_user]);
            $role_to_delete = $stmtCekRole->fetchColumn();

            if ($role_to_delete === 'Kepala Sekolah') {
                $pesan_error = "Aksi ditolak: Akun dengan Role Kepala Sekolah tidak dapat dihapus, hanya bisa diedit.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM tb_users WHERE id_user = :id");
                $stmt->execute([':id' => $id_user]);
                $pesan_sukses = "Akun berhasil dihapus permanen.";
            }
        } catch (Exception $e) {
            // Tangkap error constraint foreign key (1451)
            if (strpos($e->getMessage(), '1451') !== false || strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $pesan_error = "<strong>Gagal menghapus:</strong> Akun ini tidak dapat dihapus karena masih memiliki data laporan yang terikat. Silakan hapus laporannya terlebih dahulu.";
            } else {
                $pesan_error = "Gagal menghapus akun: " . $e->getMessage();
            }
        }
    }
    // IMPORT EXCEL (.XLSX)
    elseif ($aksi === 'import') {
        if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_excel']['tmp_name'];
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                $berhasil = 0;
                $gagal = 0;

                for ($i = 2; $i <= count($sheetData); $i++) {
                    $row = $sheetData[$i];
                    $nama_lengkap = trim($row['A'] ?? '');
                    $username     = trim($row['B'] ?? '');
                    $email        = trim($row['C'] ?? '');
                    $password     = trim($row['D'] ?? '');
                    $role         = trim($row['E'] ?? '');
                    $bidang       = trim($row['F'] ?? 'Semua');

                    // Jika barisnya benar-benar kosong (kosong semua), lewati saja tanpa dihitung gagal
                    if (empty($username) && empty($nama_lengkap) && empty($password)) {
                        continue;
                    }

                    // Jika ada yang diisi tapi tidak lengkap, baru dihitung gagal
                    if (empty($username) || empty($nama_lengkap) || empty($password)) {
                        $gagal++;
                        continue;
                    }

                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_users WHERE username = :username OR email = :email");
                    $stmtCheck->execute([':username' => $username, ':email' => empty($email) ? '-' : $email]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        $gagal++;
                        continue;
                    }

                    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO tb_users (username, email, password, nama_lengkap, role, bidang) VALUES (:username, :email, :password, :nama, :role, :bidang)");
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => empty($email) ? null : $email,
                        ':password' => $password_hashed,
                        ':nama' => $nama_lengkap,
                        ':role' => $role,
                        ':bidang' => $bidang
                    ]);
                    $berhasil++;
                }

                $pesan_sukses = "Import selesai! Berhasil: $berhasil akun, Gagal : $gagal akun";
            } catch (Exception $e) {
                $pesan_error = "Gagal membaca file Excel: " . $e->getMessage();
            }
        } else {
            $pesan_error = "Pilih file Excel yang valid terlebih dahulu";
        }
    }
}

// ==========================================
// 3. PARAMETER SEARCH, FILTERING, & PAGINATION
// ==========================================
$search = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');
$filter_bidang = trim($_GET['bidang'] ?? '');
// Limit sekarang dinamis berdasarkan input user (default 10)
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Membangun Query Dinamis
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(nama_lengkap LIKE :search OR username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filter_role)) {
    $whereClauses[] = "role = :role";
    $params[':role'] = $filter_role;
}

if (!empty($filter_bidang)) {
    $whereClauses[] = "bidang = :bidang";
    $params[':bidang'] = $filter_bidang;
}

$sqlWhere = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";

try {
    // Hitung total data untuk pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tb_users $sqlWhere");
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // Ambil data user sesuai limit dan offset
    $sqlData = "SELECT id_user, username, email, nama_lengkap, role, bidang FROM tb_users $sqlWhere ORDER BY role, nama_lengkap LIMIT :limit OFFSET :offset";
    $stmtUsers = $pdo->prepare($sqlData);

    foreach ($params as $key => $val) {
        $stmtUsers->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtUsers->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtUsers->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtUsers->execute();
    $listUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Ambil master bidang untuk filter dan form
    $stmtBidang = $pdo->query("SELECT Bidang FROM tb_bidang ORDER BY Bidang ASC");
    $listBidang = $stmtBidang->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $listUsers = [];
    $listBidang = [];
    $totalPages = 1;
}
?>

<div x-data="{ 
    modalOpen: false, 
    modalImportOpen: false,
    modalType: 'tambah', 
    form: { id_user: '', username: '', email: '', nama_lengkap: '', role: 'Guru/PIC', bidang: 'Semua' },
    selectAll: false,
    selected: [],
    toggleAll() {
        if (this.selectAll) {
            this.selected = [<?php
                                foreach ($listUsers as $u) {
                                    if ($u['role'] !== 'Kepala Sekolah') {
                                        echo "'" . $u['id_user'] . "',";
                                    }
                                }
                                ?>];
        } else {
            this.selected = [];
        }
    }
}">

    <!-- Header & Tombol Aksi Lengkap -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-display font-bold text-slate-900">Kelola User & Akses</h2>
            <p class="text-sm text-slate-500 mt-1">Manajemen akun, pencarian, filter, checklist massal, dan import/export Excel</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="../../actions/download_template_user.php" class="inline-flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 text-sm font-semibold transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Template Excel
            </a>
            <button @click="modalImportOpen = true" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-sm font-semibold transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Import Excel
            </button>
            <a href="kelola_user.php?action=export" class="inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 text-sm font-semibold transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Excel
            </a>
            <button @click="modalType = 'tambah'; form = { id_user: '', username: '', email: '', nama_lengkap: '', role: 'Guru/PIC', bidang: 'Semua' }; modalOpen = true" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah User Baru
            </button>
        </div>
    </div>

    <!-- Notifikasi Pop-up -->
    <?php if (!empty($pesan_sukses)): ?>
        <div class="mb-6 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?= $pesan_sukses ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="mb-6 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 flex items-start shadow-sm">
            <svg class="w-5 h-5 mr-2 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div><?= $pesan_error ?></div>
        </div>
    <?php endif; ?>

    <!-- Bagian Search, Filter, Limit & Tombol Hapus Massal -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4 mb-6 flex flex-col xl:flex-row items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3 w-full xl:w-auto flex-1">
            <!-- Menampilkan Data Per Halaman -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-600">Tampilkan:</span>
                <select name="limit" onchange="this.form.submit()" class="rounded-lg border border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary bg-slate-50">
                    <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span class="text-sm font-medium text-slate-600 mr-2">Entri</span>
            </div>

            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, username, email..." class="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-primary focus:border-primary">
            </div>

            <div>
                <select name="role" class="rounded-lg border border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary">
                    <option value="">Semua Peran (Role)</option>
                    <option value="Kepala Sekolah" <?= $filter_role === 'Kepala Sekolah' ? 'selected' : '' ?>>Kepala Sekolah</option>
                    <option value="Wakil Kepala" <?= $filter_role === 'Wakil Kepala' ? 'selected' : '' ?>>Wakil Kepala</option>
                    <option value="Guru/PIC" <?= $filter_role === 'Guru/PIC' ? 'selected' : '' ?>>Guru/PIC</option>
                </select>
            </div>

            <div>
                <select name="bidang" class="rounded-lg border border-slate-300 text-sm py-2 px-3 focus:ring-primary focus:border-primary">
                    <option value="">Semua Bidang</option>
                    <?php foreach ($listBidang as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $filter_bidang === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-900 transition-colors">Filter</button>
                <?php if (!empty($search) || !empty($filter_role) || !empty($filter_bidang) || $limit !== 10): ?>
                    <a href="kelola_user.php" class="bg-slate-100 text-slate-600 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-slate-200">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tombol Aksi Hapus Terpilih (Checklist) -->
        <div x-show="selected.length > 0" style="display: none;" class="w-full md:w-auto">
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh akun yang dicentang secara permanen?');">
                <input type="hidden" name="aksi" value="hapus_banyak">
                <template x-for="id in selected">
                    <input type="hidden" name="selected_ids[]" :value="id">
                </template>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Hapus Terpilih (<span x-text="selected.length"></span>)
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Data User -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-4 w-10 text-center">
                            <input type="checkbox" @change="toggleAll()" x-model="selectAll" class="rounded border-slate-300 text-primary focus:ring-primary">
                        </th>
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Username & Email</th>
                        <th class="px-6 py-4">Role / Peran</th>
                        <th class="px-6 py-4">Bidang</th>
                        <th class="px-6 py-4 text-right w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($listUsers) > 0): ?>
                        <?php foreach ($listUsers as $u): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4 text-center">
                                    <?php if ($u['role'] !== 'Kepala Sekolah'): ?>
                                        <input type="checkbox" value="<?= $u['id_user'] ?>" x-model="selected" class="rounded border-slate-300 text-primary focus:ring-primary">
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400 font-medium bg-slate-100 px-1 py-0.5 rounded cursor-not-allowed border border-slate-200" title="Akun ini tidak dapat dihapus">KS</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-bold text-slate-700"><?= htmlspecialchars($u['username']) ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($u['email'] ?? 'Tidak ada email') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500"><?= htmlspecialchars($u['bidang'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <!-- Tombol Edit -->
                                    <button @click="modalType = 'edit'; form = { id_user: '<?= $u['id_user'] ?>', username: '<?= addslashes($u['username']) ?>', email: '<?= addslashes($u['email'] ?? '') ?>', nama_lengkap: '<?= addslashes($u['nama_lengkap']) ?>', role: '<?= $u['role'] ?>', bidang: '<?= addslashes($u['bidang'] ?? '') ?>' }; modalOpen = true" class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-semibold transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>

                                    <!-- Tombol Hapus disembunyikan jika role adalah Kepala Sekolah -->
                                    <?php if ($u['role'] !== 'Kepala Sekolah'): ?>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini secara permanen?');">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition-colors">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">Tidak ada data pengguna yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between bg-white px-6 py-4 rounded-2xl shadow-sm ring-1 ring-slate-200 mb-8">
            <div class="text-xs text-slate-500">
                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalData ?> data)
            </div>
            <div class="flex items-center space-x-1">
                <?php
                // Mempertahankan $limit saat pindah halaman
                $queryString = "&search=" . urlencode($search) . "&role=" . urlencode($filter_role) . "&bidang=" . urlencode($filter_bidang) . "&limit=" . $limit;
                ?>
                <?php if ($page > 1): ?>
                    <a href="kelola_user.php?page=<?= $page - 1 ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">Sebelumnya</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="kelola_user.php?page=<?= $i ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="kelola_user.php?page=<?= $page + 1 ?><?= $queryString ?>" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">Berikutnya</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL IMPORT EXCEL -->
    <div x-show="modalImportOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalImportOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalImportOpen = false"></div>

            <div x-show="modalImportOpen" x-transition class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-2">Import Data Pengguna (.xlsx)</h3>
                <p class="text-xs text-slate-500 mb-4">Pastikan Anda menggunakan format file dari template yang telah diunduh.</p>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="aksi" value="import">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pilih Berkas Excel</label>
                        <input type="file" name="file_excel" accept=".xlsx, .xls" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1">
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="modalImportOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm">Unggah & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT USER -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="modalOpen = false"></div>

            <div x-show="modalOpen" x-transition class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <h3 class="text-lg font-display font-bold text-slate-900 mb-4" x-text="modalType === 'tambah' ? 'Tambah Pengguna Baru' : 'Edit Pengguna'"></h3>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="aksi" :value="modalType">
                    <input type="hidden" name="id_user" :value="form.id_user">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" x-model="form.nama_lengkap" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Username (Login)</label>
                            <input type="text" name="username" x-model="form.username" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat Email</label>
                            <input type="email" name="email" x-model="form.email" class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="nama@sekolah.sch.id">
                        </div>
                    </div>

                    <div x-show="modalType === 'tambah'">
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Password Baru <span class="text-red-500">*</span></label>
                        <input type="password" name="password" :required="modalType === 'tambah'" class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="Minimal 6 karakter">
                    </div>

                    <div x-show="modalType === 'edit'">
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Ganti Password (Opsional)</label>
                        <input type="password" name="password_baru" class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary" placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Peran Akses (Role)</label>
                            <select name="role" x-model="form.role" required class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                                <option value="Kepala Sekolah">Kepala Sekolah</option>
                                <option value="Wakil Kepala">Wakil Kepala</option>
                                <option value="Guru/PIC">Guru/PIC</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Bidang Struktural</label>
                            <select name="bidang" x-model="form.bidang" class="w-full rounded-lg border-slate-300 text-sm p-2.5 focus:ring-primary focus:border-primary">
                                <!-- Opsi default untuk yang tidak terikat bidang spesifik -->
                                <option value="">Tidak ada bidang (Kosongkan)</option>
                                <?php foreach ($listBidang as $b): ?>
                                    <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-800 shadow-sm" x-text="modalType === 'tambah' ? 'Simpan Akun' : 'Update Akun'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/kepsek_footer.php'; ?>