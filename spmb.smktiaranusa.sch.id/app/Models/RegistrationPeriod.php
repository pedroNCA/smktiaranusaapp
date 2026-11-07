<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationPeriod extends Model
{
    use HasFactory;

    protected $table = 'registration_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    /**
     * Gelombang yang aktif berdasarkan tanggal hari ini.
     * Tanggal mulai dan tanggal selesai dihitung inklusif.
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date', 'desc');
    }
}