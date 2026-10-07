<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** EDB 10/07/26: a location in the public coffee shop directory, with its primary contact's business fields only */
class CoffeeshopLocationResource extends JsonResource
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
            'description' => $this->description,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'address' => $this->primaryContact?->address,
            'city' => $this->primaryContact?->city,
            'postalCode' => $this->primaryContact?->postal_code,
            'country' => $this->primaryContact?->country,
            'phone' => $this->primaryContact?->phone,
            'web' => $this->primaryContact?->web,
            'social' => $this->primaryContact?->social,
        ];
    }
}
