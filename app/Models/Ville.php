<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    protected $table = 'ville';
    protected $primaryKey = 'IDville';
    public $timestamps = false;
    protected $guarded = [];
}
