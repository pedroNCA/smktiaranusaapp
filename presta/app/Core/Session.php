<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key]=$value;
    }

    public static function get(string $key,mixed $default=null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION=[];

        if(session_status()===PHP_SESSION_ACTIVE){
            session_destroy();
        }
    }
}