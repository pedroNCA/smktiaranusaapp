<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public $timestamps = false; // Mematikan timestamps karena tabel menggunakan uploaded_at

    protected $fillable = [
        'registration_id',
        'type',
        'drive_file_id',
        'file_name',
        'mime_type',
        'size',
        'status',
        'notes',
        'uploaded_at'
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class, 'registration_id');
    }
}
