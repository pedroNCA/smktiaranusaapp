<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TpaResult extends Model
{
    protected $table = 'tpa_results';
    protected $guarded = ['id'];

    public function registration()
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
