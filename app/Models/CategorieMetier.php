<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieMetier extends Model
{
    protected $table = 'categoriemetier';
    protected $primaryKey = 'IDcategoriemetier';
    public $timestamps = false;
    protected $guarded = [];
}
