<?php

namespace App\Models;

use App\Models\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(
    'is_primary',
    'phone',
    'email',
    'web',
    'social',
    'address',
    'country',
    'city',
    'postal_code',
)]
class Contact extends Model
{
    use HasPublicUlid;
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function contactable()
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected static function booted(): void
    {
        static::creating(function ($contact) {
            $contact->ulid ??= (string) Str::ulid();
        });
    }
}
