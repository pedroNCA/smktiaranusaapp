<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {

    $prefix = 'App\\';

    $base = __DIR__ . '/../app/';

    if (str_starts_with($class, $prefix)) {

        $relative = substr($class, strlen($prefix));

        $file = $base . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
});
