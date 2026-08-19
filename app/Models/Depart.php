<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depart extends Model
{
    protected $table = 'depart';
    protected $primaryKey = 'IDDEPART';
    public $timestamps = false;
    protected $guarded = [];

    public function commodite()
    {
        return $this->hasOne(CommoditeTransport::class, 'IDDEPART', 'IDDEPART');
    }

    public function escales()
    {
        return $this->hasMany(Escale::class, 'IDDEPART', 'IDDEPART');
    }
}
