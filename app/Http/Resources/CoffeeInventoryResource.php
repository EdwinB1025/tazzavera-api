<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoffeeInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'roastLot' => $this->roast_lot,
            'productionDate' => $this->production_date,
            'coffee' => new CoffeeResource($this->whenLoaded('coffee')),
            'roastery' => new RoasteryResource($this->whenLoaded('roastery')),
        ];
    }
}
