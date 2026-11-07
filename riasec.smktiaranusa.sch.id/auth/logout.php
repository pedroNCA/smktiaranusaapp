<?php
// auth/logout.php

// 1. Panggil konfigurasi sesi
require_once __DIR__ . '/../config/session.php';

// 2. Kosongkan semua data sesi (termasuk admin_logged_in, csrf_token, dll)
session_unset();

// 3. Hancurkan sesi sepenuhnya dari memori server
session_destroy();

// 4. Redirect ke halaman login menggunakan URL samaran dari .htaccess
// Menggunakan parameter msg=logout agar notifikasi berhasil keluar muncul di layar
header("Location: /gerbang-admin-z12?msg=logout");
exit();
