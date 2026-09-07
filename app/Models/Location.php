<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('user_id', 'name', 'description', 'latitud', 'longitud')]
class Location extends Model
{
    use HasPublicUlid, HasFactory;

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    /** Relationships */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }
}
