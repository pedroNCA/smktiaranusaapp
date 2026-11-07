<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolContact extends Model
{
    protected $fillable = [
        'address', 'phone', 'email', 'instagram', 'facebook', 'youtube', 'gmaps_link'
    ];
}