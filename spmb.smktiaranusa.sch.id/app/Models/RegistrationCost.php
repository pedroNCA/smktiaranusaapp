<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationCost extends Model
{
    protected $table = 'registration_costs';
    protected $fillable = ['name', 'amount', 'description'];
}