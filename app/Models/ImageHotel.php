<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageHotel extends Model
{
    protected $table = 'imagehotel';
    protected $primaryKey = 'IDIMAGE';
    public $timestamps = false;
    protected $guarded = [];
}
