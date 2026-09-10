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
            'name_en' => $this->name_en,
            'name_es' => $this->name_es,
            'description_en' => $this->description_en,
            'description_es' => $this->description_es,
            'color' => $this->color,
            'categories' => $this->categories,
            'children' => TaxonomyResource::collection($this->whenLoaded('children')),
        ];
    }
}
