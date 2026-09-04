<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('name', 'description')]
class Roastery extends Model
{
    use HasPublicUlid, HasFactory;

    /** Relationships */



    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }
}
