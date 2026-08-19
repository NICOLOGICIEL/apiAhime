<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metier extends Model
{
    protected $table = 'metier';
    protected $primaryKey = 'IDmetier';
    public $timestamps = false;
    protected $guarded = [];
}
