<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoffeeResource extends JsonResource
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
            'name' => $this->name,
            'roast_level' => $this->roast_level,
            'process' => $this->process,
            'variety' => $this->variety,
            'producer' => $this->producer,
            'country' => $this->country,
            'region' => $this->region,
            'altitude' => $this->altitude,
            'lot' => $this->lot,
            'certifications' => CertificationTypeResource::collection($this->whenLoaded('certificationTypes')),
        ];
    }
}
