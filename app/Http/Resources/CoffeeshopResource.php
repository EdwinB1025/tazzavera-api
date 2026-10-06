<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** EDB 10/06/26: public coffee shop (the business, a coffeeshop user). Business data only: never the owner's email, surname, personal contacts or account data */
class CoffeeshopResource extends JsonResource
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
            'locationsCount' => (int) $this->locations_count,
            'offeringsCount' => (int) $this->offerings_count,
            'verifiedOfferingsCount' => (int) $this->verified_offerings_count,
            'locations' => $this->whenLoaded('locations', fn() => $this->locations->map(fn($location) => [
                'ulid' => $location->ulid,
                'name' => $location->name,
                'description' => $location->description,
                'latitud' => $location->latitud,
                'longitud' => $location->longitud,
                'address' => $location->primaryContact?->address,
                'city' => $location->primaryContact?->city,
                'postalCode' => $location->primaryContact?->postal_code,
                'country' => $location->primaryContact?->country,
                'phone' => $location->primaryContact?->phone,
                'web' => $location->primaryContact?->web,
                'social' => $location->primaryContact?->social,
            ])),
        ];
    }
}
