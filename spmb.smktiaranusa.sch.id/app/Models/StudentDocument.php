<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    protected $fillable = ['user_id', 'jenis_berkas', 'file_path', 'status']; // status: pending, valid, invalid

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}