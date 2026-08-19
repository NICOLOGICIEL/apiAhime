<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escale extends Model
{
    protected $table = 'escale';
    protected $primaryKey = 'IDESCALE';
    public $timestamps = false;
    protected $guarded = [];
}
