<?php

class DateHelper
{
    /**
     * Nama bulan Indonesia
     */
    private static array $bulan = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    /**
     * Nama bulan singkat
     */
    private static array $bulanSingkat = [
        1  => 'JAN',
        2  => 'FEB',
        3  => 'MAR',
        4  => 'APR',
        5  => 'MEI',
        6  => 'JUN',
        7  => 'JUL',
        8  => 'AGU',
        9  => 'SEP',
        10 => 'OKT',
        11 => 'NOV',
        12 => 'DES'
    ];

    /**
     * Nama hari Indonesia
     */
    private static array $hari = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];

    /**
     * Format:
     * 14 Juli 2026
     */
    public static function tanggal(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        $time = strtotime($tanggal);

        return date('d', $time) . ' ' .
            self::$bulan[(int)date('n', $time)] . ' ' .
            date('Y', $time);
    }

    /**
     * Format:
     * JUL
     */
    public static function bulanSingkat(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        $time = strtotime($tanggal);

        return self::$bulanSingkat[(int)date('n', $time)];
    }

    /**
     * Format:
     * Juli
     */
    public static function bulan(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        $time = strtotime($tanggal);

        return self::$bulan[(int)date('n', $time)];
    }

    /**
     * Format:
     * Selasa
     */
    public static function hari(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        $time = strtotime($tanggal);

        return self::$hari[date('l', $time)];
    }

    /**
     * Format:
     * 14
     */
    public static function hariAngka(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        return date('d', strtotime($tanggal));
    }

    /**
     * Format:
     * 2026
     */
    public static function tahun(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        return date('Y', strtotime($tanggal));
    }

    /**
     * Format:
     * 14 Juli 2026 • Selasa
     */
    public static function lengkap(?string $tanggal = null): string
    {
        return self::tanggal($tanggal) . ' • ' . self::hari($tanggal);
    }

    /**
     * Format:
     * 14/07/2026
     */
    public static function indonesia(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        return date('d/m/Y', strtotime($tanggal));
    }

    /**
     * Format:
     * 2026-07-14
     */
    public static function mysql(): string
    {
        return date('Y-m-d');
    }
}
