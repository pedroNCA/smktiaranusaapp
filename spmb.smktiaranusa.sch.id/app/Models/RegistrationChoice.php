<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationChoice extends Model
{
    // Mematikan timestamps karena tabel tidak memiliki created_at dan updated_at
    public $timestamps = false;

    protected $fillable = [
        'registration_id',
        'major_id',
        'choice_order',
    ];

    /**
     * Relasi kembali ke data Pendaftaran
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Relasi ke data Jurusan (Majors)
     */
    public function major()
    {
        return $this->belongsTo(Major::class);
    }
}
