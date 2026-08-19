<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommoditeTransport extends Model
{
    protected $table = 'commoditetransport';
    protected $primaryKey = 'IDCOMMODITETRANSPORT';
    public $timestamps = false;
    protected $guarded = [];
}
