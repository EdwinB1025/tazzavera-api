<?php

namespace App\Models;

use App\Enums\RoastLevel;
use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(
    'name',
    'roast_level',
    'process',
    'variety',
    'country',
    'region',
    'altitude',
    'lot',
)]
class Coffee extends Model
{
    use HasPublicUlid, HasFactory;

    protected $casts = [
        'roast_level' => RoastLevel::class,
    ];

    /**Relationships */

    public function certificationTypes(): BelongsToMany
    {
        return $this->belongsToMany(CertificationType::class, 'certifications');
    }
}
