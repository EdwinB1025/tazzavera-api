<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable('code', 'description')]
class CertificationType extends Model
{

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**Relationships */

    public function coffees(): BelongsToMany
    {
        return $this->belongsToMany(Coffee::class, 'certifications');
    }
}
