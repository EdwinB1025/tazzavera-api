<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonomyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'level' => $this->level,
            'nameEn' => $this->name_en,
            'nameEs' => $this->name_es,
            'descriptionEn' => $this->description_en,
            'descriptionEs' => $this->description_es,
            'color' => $this->color,
            'categories' => $this->categories,
            'children' => TaxonomyResource::collection($this->whenLoaded('children')),
        ];
    }
}
