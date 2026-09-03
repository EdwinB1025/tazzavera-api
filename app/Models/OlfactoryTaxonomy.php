<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(
    'parent_id',
    'level',
    'name_en',
    'name_es',
    'description_en',
    'description_es',
    'color_base',
    'color',
    'categories',
)]

class OlfactoryTaxonomy extends Model
{
    use HasPublicUlid, HasFactory;

    //**Inner relationships with childreen */

    public function parent()
    {
        return $this->belongsTo(OlfactoryTaxonomy::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OlfactoryTaxonomy::class, 'parent_id');
    }
}
