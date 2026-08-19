<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $table = 'hotel';
    protected $primaryKey = 'IDHOTEL';
    public $timestamps = false;
    protected $guarded = [];

    public function commodite()
    {
        return $this->hasOne(CommoditeHotel::class, 'IDHOTEL', 'IDHOTEL');
    }

    public function images()
    {
        return $this->hasMany(ImageHotel::class, 'IDHOTEL', 'IDHOTEL')->orderBy('NumPhoto');
    }
}
