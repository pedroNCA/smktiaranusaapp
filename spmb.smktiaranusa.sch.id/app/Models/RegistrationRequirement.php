<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationRequirement extends Model
{
    protected $table = 'registration_requirements';
    protected $fillable = ['title', 'description', 'type'];
}