<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationSchedule extends Model
{
    protected $table = 'registration_schedules';
    protected $fillable = ['title', 'start_date', 'end_date', 'status'];
}