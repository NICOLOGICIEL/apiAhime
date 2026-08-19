<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommoditeHotel extends Model
{
    protected $table = 'commoditehotel';
    protected $primaryKey = 'IDCOMMODITEHOTEL';
    public $timestamps = false;
    protected $guarded = [];
}
