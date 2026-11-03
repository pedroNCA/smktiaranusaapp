<?php
session_start();
// Hapus semua data sesi
$_SESSION = array();
session_destroy();

// UBAH DARI INI:
// header("Location: login.php");

// MENJADI INI:
header("Location: index.php?route=auth/login");
exit;