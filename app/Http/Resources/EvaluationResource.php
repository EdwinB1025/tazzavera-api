<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid'             => $this->ulid,
            'offeringId'       => $this->offering->ulid,
            'evaluationType'   => $this->evaluation_type,
            'status'           => $this->status,
            'extractionMethod' => $this->extraction_method,
            'cuppingScore'     => $this->cupping_score,
            'isDefective'      => $this->is_defective,
            'descriptive'      => $this->descriptive,
            'affective'        => $this->affective,
            'extrinsics'       => $this->extrinsics,
            'tastes'           => $this->whenLoaded(
                'tastes',
                fn() =>
                $this->tastes->map(fn($t) => [
                    'ref'  => $t->taxonomy->ulid,
                    'type' => $t->type,
                ])
            ),
        ];
    }
}
