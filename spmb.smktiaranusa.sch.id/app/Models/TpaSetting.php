<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TpaSetting extends Model {
    protected $table = 'tpa_settings';
    protected $guarded = ['id'];
    public $timestamps = false;
}