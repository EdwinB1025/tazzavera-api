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
            'roast_lot' => $this->roast_lot,
            'production_date' => $this->production_date,
            'coffee' => new CoffeeResource($this->whenLoaded('coffee')),
            'roastery' => new RoasteryResource($this->whenLoaded('roastery')),
        ];
    }
}
