<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $guarded = ['id'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function period()
    {
        return $this->belongsTo(RegistrationPeriod::class, 'period_id');
    }

    public function documents()
    {
        return $this->hasMany('App\\Models\\Document');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
