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
            'ulid' => $this->ulid,
            'level' => $this->level,
            'nameEn' => $this->name_en,
            'nameEs' => $this->name_es,
            'nameCa' => $this->name_ca,
            'descriptionEn' => $this->description_en,
            'descriptionEs' => $this->description_es,
            'descriptionCa' => $this->description_ca,
            'color' => $this->color,
            'categories' => $this->categories ?? [],
            'children' => TaxonomyResource::collection($this->whenLoaded('children')),
        ];
    }
}
