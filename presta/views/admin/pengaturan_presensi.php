<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';

Auth::admin();

$pesan = '';

// Proses Simpan Pengaturan Per Hari
if (isset($_POST['simpan_pengaturan'])) {
    $jadwal = $_POST['jadwal'] ?? [];
    $sukses = true;

    foreach ($jadwal as $hari => $data) {
        $jam_mulai = $conn->real_escape_string($data['jam_mulai']);
        $jam_tutup = $conn->real_escape_string($data['jam_tutup']);
        
        $query = "UPDATE pengaturan_jam_presensi SET jam_mulai = '$jam_mulai', jam_tutup = '$jam_tutup' WHERE hari = '$hari'";
        if (!$conn->query($query)) {
            $sukses = false;
        }
    }

    if ($sukses) {
        $pesan = "<script>
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengaturan batas waktu presensi harian berhasil diperbarui.',
                icon: 'success',
                confirmButtonColor: '#2563eb'
            });
        </script>";
    } else {
        $pesan = "<script>
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan pengaturan.',
                icon: 'error',
                confirmButtonColor: '#2563eb'
            });
        </script>";
    }
}

// Ambil data pengaturan harian
$result = $conn->query("SELECT * FROM pengaturan_jam_presensi ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')");
$pengaturanHari = [];
while ($row = $result->fetch_assoc()) {
    $pengaturanHari[$row['hari']] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Batas Presensi Harian - PRESTA Admin</title>
    <?php include __DIR__ . '/includes/admin_head.php'; ?>
</head>

<body class="bg-slate-50 antialiased text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-4xl mx-auto space-y-6">

                    <!-- Header -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900">Pengaturan Batas Presensi Per Hari (Senin - Jumat)</h2>
                        <p class="text-xs text-gray-500 mt-1">Atur jam mulai dan jam tutup pengisian presensi sekretaris yang berbeda-beda untuk tiap harinya.</p>
                    </div>

                    <!-- Form Pengaturan -->
                    <form method="POST" action="" class="space-y-6">
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                            <?php 
                            $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                            foreach ($daftarHari as $h): 
                                $mulai = isset($pengaturanHari[$h]) ? substr($pengaturanHari[$h]['jam_mulai'], 0, 5) : '06:00';
                                $tutup = isset($pengaturanHari[$h]) ? substr($pengaturanHari[$h]['jam_tutup'], 0, 5) : '07:10';
                            ?>
                                <div class="p-4 rounded-xl border border-gray-100 bg-slate-50/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="w-32">
                                        <span class="text-sm font-bold text-gray-900 uppercase tracking-wide"><?= $h ?></span>
                                    </div>
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Jam Mulai</label>
                                            <input type="time" name="jadwal[<?= $h ?>][jam_mulai]" value="<?= $mulai ?>" class="w-full h-10 border border-gray-200 rounded-xl px-3 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none bg-white" required>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Jam Tutup</label>
                                            <input type="time" name="jadwal[<?= $h ?>][jam_tutup]" value="<?= $tutup ?>" class="w-full h-10 border border-gray-200 rounded-xl px-3 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none bg-white" required>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-800 space-y-1">
                                <p class="font-bold">Informasi Sistem:</p>
                                <p>• Hari Sabtu dan Minggu otomatis dianggap libur total oleh sistem, sehingga form input bagi sekretaris terkunci secara otomatis.</p>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" name="simpan_pengaturan" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all inline-flex items-center gap-2">
                                    <i class="fas fa-save"></i> Simpan Seluruh Jadwal
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </main>
        </div>
    </div>

    <?= $pesan ?>
</body>
</html>