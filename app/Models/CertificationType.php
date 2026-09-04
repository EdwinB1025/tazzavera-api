<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('code', 'description')]
class CertificationType extends Model
{

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
