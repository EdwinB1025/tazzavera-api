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
            'is_primary' => $this->is_primary,
            'phone' => $this->phone,
            'email' => $this->email,
            'web' => $this->web,
            'social' => $this->social,
            'address' => $this->address,
            'country' => $this->country,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
        ];
    }
}
