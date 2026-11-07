<?php
require 'config.php';
require 'includes/auth.php';

$siswa_id = (int)($_GET['siswa_id'] ?? 0);

// Hanya Admin dan Guru BK yang boleh menghapus riwayat pelanggaran/prestasi
if (!isGuruBK()) {
    redirect("riwayat_siswa.php?id=$siswa_id", 'Akses ditolak: Hanya Admin atau Guru BK yang dapat menghapus catatan.', 'danger');
}

$jenis = $_GET['jenis'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    if ($jenis === 'pelanggaran') {
        mysqli_query($koneksi, "DELETE FROM riwayat_pelanggaran WHERE id=$id");
    } elseif ($jenis === 'prestasi') {
        mysqli_query($koneksi, "DELETE FROM riwayat_prestasi WHERE id=$id");
    }
}

$target_redirect = $_GET['redirect'] ?? "riwayat_siswa.php?id=$siswa_id";
redirect($target_redirect, 'Catatan berhasil dihapus.');
