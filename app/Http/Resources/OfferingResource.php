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
            'evaluationCount' => $this->whenNotNull($this->evaluation_count),
            'defectiveEvaluationCount' => $this->whenNotNull($this->defective_evaluation_count),
            'cuppingAvg' => $this->whenNotNull($this->cupping_avg),
            'fragranceAffectiveAvg' => $this->whenNotNull($this->fragrance_avg),
            'aromaAffectiveAvg' => $this->whenNotNull($this->aroma_avg),
            'flavorAffectiveAvg' => $this->whenNotNull($this->flavor_avg),
            'aftertasteAffectiveAvg' => $this->whenNotNull($this->aftertaste_avg),
            'acidityAffectiveAvg' => $this->whenNotNull($this->acidity_avg),
            'sweetnessAffectiveAvg' => $this->whenNotNull($this->sweetness_avg),
            'mouthfeelAffectiveAvg' => $this->whenNotNull($this->mouthfeel_avg),
            'overallAffectiveAvg' => $this->whenNotNull($this->overall_avg),
            'concordance' => $this->whenNotNull($this->concordance),
            'verificationStatus' => $this->verification_status,
            'location' => new LocationResource($this->whenLoaded('location')),
            'coffeeInventory' => new CoffeeInventoryResource($this->whenLoaded('coffeeInventory')),
            'sensoryTaxonomy' => OfferingTasteResource::collection($this->whenLoaded('offeringTastes')),
        ];
    }
}
