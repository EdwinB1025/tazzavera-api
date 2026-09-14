<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferingResource extends JsonResource
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
            'evaluation_count' => $this->whenNotNull($this->evaluation_count),
            'defective_evaluation_count' => $this->whenNotNull($this->defective_evaluation_count),
            'cupping_avg' => $this->whenNotNull($this->cupping_avg),
            'fragrance_avg' => $this->whenNotNull($this->fragrance_avg),
            'aroma_avg' => $this->whenNotNull($this->aroma_avg),
            'flavor_avg' => $this->whenNotNull($this->flavor_avg),
            'aftertaste_avg' => $this->whenNotNull($this->aftertaste_avg),
            'acidity_avg' => $this->whenNotNull($this->acidity_avg),
            'sweetness_avg' => $this->whenNotNull($this->sweetness_avg),
            'mouthfeel_avg' => $this->whenNotNull($this->mouthfeel_avg),
            'overall_avg' => $this->whenNotNull($this->overall_avg),
            'concordance' => $this->whenNotNull($this->concordance),
            'verification_status' => $this->verification_status,
            'location' => new LocationResource($this->whenLoaded('location')),
            'coffee_inventory' => new CoffeeInventoryResource($this->whenLoaded('coffeeInventory')),
            'tastes' => OfferingTasteResource::collection($this->whenLoaded('offeringTastes')),
        ];
    }
}
