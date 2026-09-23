<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coffeeName'    => ['sometimes', 'string', 'max:150'],
            'originCountry' => ['sometimes', 'string', 'max:90'],
            'originRegion'  => ['sometimes', 'string', 'max:90'],
            'process'       => ['sometimes', 'string', 'max:90'],
            'producer'      => ['sometimes', 'string', 'max:150'],
            'variety'       => ['sometimes', 'string', 'max:90'],
            'city'          => ['sometimes', 'string', 'max:90'],
            'coffeeshopUlid' => ['sometimes', 'string', 'exists:users,ulid'],
            'locationUlid'   => ['sometimes', 'string', 'exists:locations,ulid'],

            'cuppingAvgMin' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'cuppingAvgMax' => ['sometimes', 'numeric', 'min:0', 'max:100', 'gte:cuppingAvgMin'],

            'evaluationCountMin' => ['sometimes', 'integer', 'min:0'],
            'evaluationCountMax' => ['sometimes', 'integer', 'min:0', 'gte:evaluationCountMin'],
            'defectiveCountMin'  => ['sometimes', 'integer', 'min:0'],
            'defectiveCountMax'  => ['sometimes', 'integer', 'min:0', 'gte:defectiveCountMin'],

            'fragranceMin'  => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'fragranceMax'  => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:fragranceMin'],
            'aromaMin'      => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'aromaMax'      => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:aromaMin'],
            'flavorMin'     => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'flavorMax'     => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:flavorMin'],
            'aftertasteMin' => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'aftertasteMax' => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:aftertasteMin'],
            'acidityMin'    => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'acidityMax'    => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:acidityMin'],
            'sweetnessMin'  => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'sweetnessMax'  => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:sweetnessMin'],
            'mouthfeelMin'  => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'mouthfeelMax'  => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:mouthfeelMin'],
            'overallMin'    => ['sometimes', 'numeric', 'min:1', 'max:9'],
            'overallMax'    => ['sometimes', 'numeric', 'min:1', 'max:9', 'gte:overallMin'],

            'cataRef'        => ['sometimes', 'array'],
            'cataRef.*'      => ['string', 'exists:olfactory_taxonomies,ulid'],
            'fragranceCata'  => ['sometimes', 'array'],
            'fragranceCata.*' => ['string', 'exists:olfactory_taxonomies,ulid'],
            'aromaCata'      => ['sometimes', 'array'],
            'aromaCata.*'    => ['string', 'exists:olfactory_taxonomies,ulid'],
            'flavorCata'     => ['sometimes', 'array'],
            'flavorCata.*'   => ['string', 'exists:olfactory_taxonomies,ulid'],
            'aftertasteCata' => ['sometimes', 'array'],
            'aftertasteCata.*' => ['string', 'exists:olfactory_taxonomies,ulid'],
            'mouthfeelCata'  => ['sometimes', 'array'],
            'mouthfeelCata.*' => ['string', 'exists:olfactory_taxonomies,ulid'],

            'orderBy'        => ['sometimes', 'in:cupping_avg,evaluation_count,created_at'],
            'orderDirection' => ['sometimes', 'in:asc,desc'],
        ];
    }
    /**
     * Query parameters documented for Scribe.
     *
     * @return array<string, array>
     */
    public function queryParameters(): array
    {
        return [
            'coffeeName'    => ['description' => 'Filter by coffee name (partial match).', 'example' => 'Geisha'],
            'originCountry' => ['description' => 'Filter by country of origin.', 'example' => 'Colombia'],
            'originRegion'  => ['description' => 'Filter by region of origin.', 'example' => 'Huila'],
            'process'       => ['description' => 'Filter by processing method.', 'example' => 'Washed'],
            'producer'      => ['description' => 'Filter by producer name.', 'example' => 'Finca La Esperanza'],
            'variety'       => ['description' => 'Filter by coffee variety.', 'example' => 'Caturra'],
            'city'          => ['description' => 'Filter by the city of the offering location.', 'example' => 'Barcelona'],
            'coffeeshopUlid' => ['description' => 'Filter by coffeeshop (user) ULID.', 'example' => '01J8ZK...'],
            'locationUlid'   => ['description' => 'Filter by location ULID.', 'example' => '01J8ZK...'],

            'cuppingAvgMin' => ['description' => 'Minimum average cupping score (0–100).', 'example' => 80],
            'cuppingAvgMax' => ['description' => 'Maximum average cupping score (0–100). Must be ≥ cuppingAvgMin.', 'example' => 95],

            'evaluationCountMin' => ['description' => 'Minimum number of evaluations.', 'example' => 1],
            'evaluationCountMax' => ['description' => 'Maximum number of evaluations. Must be ≥ evaluationCountMin.', 'example' => 50],
            'defectiveCountMin'  => ['description' => 'Minimum number of defective evaluations.', 'example' => 0],
            'defectiveCountMax'  => ['description' => 'Maximum number of defective evaluations. Must be ≥ defectiveCountMin.', 'example' => 5],

            // Sensory attribute ranges (SCA scale, 1–9). Each has a Min and Max; Max must be ≥ Min.
            'fragranceMin'  => ['description' => 'Minimum fragrance score (1–9).', 'example' => 6],
            'fragranceMax'  => ['description' => 'Maximum fragrance score (1–9).', 'example' => 8],
            'aromaMin'      => ['description' => 'Minimum aroma score (1–9).', 'example' => 6],
            'aromaMax'      => ['description' => 'Maximum aroma score (1–9).', 'example' => 8],
            'flavorMin'     => ['description' => 'Minimum flavor score (1–9).', 'example' => 6],
            'flavorMax'     => ['description' => 'Maximum flavor score (1–9).', 'example' => 8],
            'aftertasteMin' => ['description' => 'Minimum aftertaste score (1–9).', 'example' => 6],
            'aftertasteMax' => ['description' => 'Maximum aftertaste score (1–9).', 'example' => 8],
            'acidityMin'    => ['description' => 'Minimum acidity score (1–9).', 'example' => 6],
            'acidityMax'    => ['description' => 'Maximum acidity score (1–9).', 'example' => 8],
            'sweetnessMin'  => ['description' => 'Minimum sweetness score (1–9).', 'example' => 6],
            'sweetnessMax'  => ['description' => 'Maximum sweetness score (1–9).', 'example' => 8],
            'mouthfeelMin'  => ['description' => 'Minimum mouthfeel score (1–9).', 'example' => 6],
            'mouthfeelMax'  => ['description' => 'Maximum mouthfeel score (1–9).', 'example' => 8],
            'overallMin'    => ['description' => 'Minimum overall score (1–9).', 'example' => 6],
            'overallMax'    => ['description' => 'Maximum overall score (1–9).', 'example' => 8],

            'cataRef' => [
                'description' => 'Filter offerings that have at least one taste note matching any of these olfactory taxonomy ULIDs, regardless of sensory attribute. Array of ULIDs.',
                'example' => ['01J8ZK...'],
            ],
            'fragranceCata' => [
                'description' => 'Filter offerings whose fragrance notes match any of these olfactory taxonomy ULIDs. Array of ULIDs.',
                'example' => ['01J8ZK...'],
            ],
            'aromaCata' => [
                'description' => 'Filter offerings whose aroma notes match any of these olfactory taxonomy ULIDs. Array of ULIDs.',
                'example' => ['01J8ZK...'],
            ],
            'flavorCata' => [
                'description' => 'Filter offerings whose flavor notes match any of these olfactory taxonomy ULIDs. Array of ULIDs.',
                'example' => ['01J8ZK...'],
            ],
            'aftertasteCata' => [
                'description' => 'Filter offerings whose aftertaste notes match any of these olfactory taxonomy ULIDs. Array of ULIDs.',
                'example' => ['01J8ZK...'],
            ],
            'mouthfeelCata' => [
                'description' => 'Filter offerings whose mouthfeel notes match any of these olfactory taxonomy ULIDs. Array of ULIDs.',
                'example' => ['01J8ZK...'],
            ],
            'orderBy'        => ['description' => 'Sort field. One of: cupping_avg, evaluation_count, created_at.', 'example' => 'cupping_avg'],
            'orderDirection' => ['description' => 'Sort direction. One of: asc, desc.', 'example' => 'desc'],
        ];
    }
}
