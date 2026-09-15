<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'isPrimary' => $this->is_primary,
            'phone' => $this->phone,
            'email' => $this->email,
            'web' => $this->web,
            'social' => $this->social,
            'address' => $this->address,
            'country' => $this->country,
            'city' => $this->city,
            'postalCode' => $this->postal_code,
        ];
    }
}
