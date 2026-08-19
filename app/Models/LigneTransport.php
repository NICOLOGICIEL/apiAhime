<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneTransport extends Model
{
    protected $table = 'lignetransport';
    protected $primaryKey = 'IDLIGNETRANSPORT';
    public $timestamps = false;
    protected $guarded = [];

    public function compagnie()
    {
        return $this->belongsTo(Compagnie::class, 'IDCOMPAGNIE', 'IDCOMPAGNIE');
    }

    public function departs()
    {
        return $this->hasMany(Depart::class, 'IDLIGNETRANSPORT', 'IDLIGNETRANSPORT');
    }
}
