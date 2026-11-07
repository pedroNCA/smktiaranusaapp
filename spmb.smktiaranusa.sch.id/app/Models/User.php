<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Registration; // Pastikan namespace ini ada

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'password',
        'role',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Jika User adalah pendaftar, ini mengambil profil siswanya
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    // Mengambil semua riwayat pendaftaran dari akun ini
    public function registrations()
    {
        return $this->hasMany(Registration::class, 'user_id');
    }

    // Jika User adalah Admin, ini mengambil riwayat pembayaran yang dia verifikasi
    public function paymentsVerified()
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }
}
