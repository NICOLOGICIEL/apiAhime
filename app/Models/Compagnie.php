<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compagnie extends Model
{
    protected $table = 'compagnie';
    protected $primaryKey = 'IDCOMPAGNIE';
    public $timestamps = false;
    protected $guarded = [];

    public function lignes()
    {
        return $this->hasMany(LigneTransport::class, 'IDCOMPAGNIE', 'IDCOMPAGNIE');
    }

    public function notations()
    {
        return $this->hasMany(Notation::class, 'IDCOMPAGNIE', 'IDCOMPAGNIE')->orderByDesc('dateNote');
    }
}
