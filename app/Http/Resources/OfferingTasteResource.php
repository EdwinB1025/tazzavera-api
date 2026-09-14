<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferingTasteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'taxonomy' => new TaxonomyResource($this->whenLoaded('taxonomy')),
            'type' => $this->type,
            'level' => $this->level,
            'count' => $this->count,
            'children' => OfferingTasteResource::collection($this->whenLoaded('children')),
        ];
    }
}
