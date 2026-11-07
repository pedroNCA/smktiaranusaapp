<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function address()
    {
        return $this->hasOne(StudentAddress::class, 'student_id');
    }

    public function parents()
    {
        return $this->hasMany(StudentParent::class, 'student_id');
    }

    // Mengambil pendaftaran milik siswa spesifik ini
    public function registrations()
    {
        return $this->hasMany(Registration::class, 'student_id');
    }
}
