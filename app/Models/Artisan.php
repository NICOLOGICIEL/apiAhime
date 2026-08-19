<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artisan extends Model
{
    protected $table = 'artisant';
    protected $primaryKey = 'IDARTISANT';
    public $timestamps = false;
    protected $guarded = [];

    public function metier()
    {
        return $this->belongsTo(Metier::class, 'IDmetier', 'IDmetier');
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieMetier::class, 'IDcategoriemetier', 'IDcategoriemetier');
    }

    public function notations()
    {
        return $this->hasMany(Notation::class, 'IDARTISANT', 'IDARTISANT')->orderByDesc('dateNote');
    }
}
