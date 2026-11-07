<?php
require 'config.php';
require 'includes/auth.php';
if (isOsis() || isKepalaSekolah()) {
    redirect('siswa.php', 'Akses ditolak: Anda tidak memiliki izin memodifikasi data master siswa.', 'danger');
}

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? '';

if ($aksi === 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    $kelas_id = (int)($_GET['kelas_id'] ?? 0);
    if (!$kelas_id && $id) {
        $qK = mysqli_query($koneksi, "SELECT kelas_id FROM siswa WHERE id=$id");
        if ($rowK = mysqli_fetch_assoc($qK)) {
            $kelas_id = (int)$rowK['kelas_id'];
        }
    }
    mysqli_query($koneksi, "DELETE FROM siswa WHERE id=$id");
    $redir = $kelas_id ? "siswa.php?kelas=$kelas_id" : "siswa.php";
    redirect($redir, 'Data siswa berhasil dihapus.');
}

if ($aksi === 'tambah' || $aksi === 'edit') {
    $nisn = clean($koneksi, $_POST['nisn']);
    $nis = clean($koneksi, $_POST['nis']);
    $nama = clean($koneksi, $_POST['nama']);
    $kelas_id = (int)$_POST['kelas_id'];
    $jk = clean($koneksi, $_POST['jenis_kelamin']);
    $ortu = clean($koneksi, $_POST['nama_ortu']);
    $hp = clean($koneksi, $_POST['no_hp_ortu']);
    $redir = $kelas_id ? "siswa.php?kelas=$kelas_id" : "siswa.php";

    // Jika NIS belum diisi, buat default '000000' karena belum ada data NIS lokal
    if ($nis === '') {
        $nis = '000000';
    }

    if ($nisn === '' || $nama === '') {
        redirect($redir, 'NISN dan Nama Lengkap wajib diisi.', 'danger');
    }

    $id = (int)($_POST['id'] ?? 0);

    // Cek duplikasi NISN (prioritas utama)
    if ($nisn !== '' && $nisn !== '0000000000' && $nisn !== '000000') {
        $cekNisnSql = "SELECT id FROM siswa WHERE nisn='$nisn'" . ($aksi === 'edit' ? " AND id != $id" : "");
        if (mysqli_num_rows(mysqli_query($koneksi, $cekNisnSql)) > 0) {
            redirect($redir, "NISN '$nisn' sudah terdaftar untuk siswa lain.", 'danger');
        }
    }

    // Cek duplikasi NIS (hanya jika diisi NIS spesifik, bukan placeholder 000000)
    if ($nis !== '' && $nis !== '000000' && $nis !== '0000000000') {
        $cekNisSql = "SELECT id FROM siswa WHERE nis='$nis'" . ($aksi === 'edit' ? " AND id != $id" : "");
        if (mysqli_num_rows(mysqli_query($koneksi, $cekNisSql)) > 0) {
            redirect($redir, "NIS '$nis' sudah terdaftar untuk siswa lain.", 'danger');
        }
    }

    if ($aksi === 'tambah') {
        $sql = "INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin, nama_ortu, no_hp_ortu)
                VALUES ('$nis','$nisn','$nama',$kelas_id,'$jk','$ortu','$hp')";
        mysqli_query($koneksi, $sql) or die(mysqli_error($koneksi));
        $new_siswa_id = mysqli_insert_id($koneksi);

        redirect($redir, 'Siswa baru berhasil ditambahkan.');
    } else {
        $sql = "UPDATE siswa SET nis='$nis', nisn='$nisn', nama='$nama', kelas_id=$kelas_id,
                jenis_kelamin='$jk', nama_ortu='$ortu', no_hp_ortu='$hp' WHERE id=$id";
        mysqli_query($koneksi, $sql) or die(mysqli_error($koneksi));

        redirect($redir, 'Data siswa berhasil diperbarui.');
    }
}

redirect('siswa.php');
