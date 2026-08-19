<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notation extends Model
{
    protected $table = 'notation';
    protected $primaryKey = 'IDNOTATION';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'dateNote' => 'datetime',
    ];
}
