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
            'locations' => CoffeeshopLocationResource::collection($this->whenLoaded('locations')),
        ];
    }
}
