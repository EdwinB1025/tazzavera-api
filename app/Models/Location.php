<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable('user_id', 'name', 'description', 'latitud', 'longitud')]
class Location extends Model
{
    use HasPublicUlid, HasFactory;

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    /** Relationships */

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
