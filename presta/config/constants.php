<?php

declare(strict_types=1);

defined('APP_START') || define('APP_START', microtime(true));

define('APP_NAME', 'PRESTA');
define('APP_TAGLINE', 'Cepat, Tepat, Terintegrasi');
define('APP_VERSION', '2.1.0');

define('TIMEZONE', 'Asia/Jakarta');

define('ROLE_ADMIN', 'admin');
define('ROLE_GURU', 'guru');
define('ROLE_SEKRETARIS', 'sekretaris');
define('ROLE_PIKET', 'piket');

define('STATUS_BELUM', 'belum');
define('STATUS_HADIR', 'hadir');
define('STATUS_IZIN', 'izin');
define('STATUS_SAKIT', 'sakit');
define('STATUS_ALFA', 'alfa');
define('STATUS_TERLAMBAT', 'terlambat');

define('PRESENSI_BATAS', '07:10:00');
