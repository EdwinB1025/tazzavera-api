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
}
