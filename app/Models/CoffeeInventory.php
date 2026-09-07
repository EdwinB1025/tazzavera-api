<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(
    'roastery_id',
    'coffee_id',
    'roast_lot',
    'production_date',
)]
#[Table('coffee_inventory')]
class CoffeeInventory extends Pivot
{
    use HasPublicUlid, HasFactory;
    //
}
