<?php

namespace App\Http\Requests;

use App\Enums\RoastLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
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
            // --- Headers ---
            'offeringId'        => ['required', 'string', 'exists:offerings,ulid'],
            'status'            => ['nullable', 'string'],
            'extractionMethod'  => ['nullable', 'string', 'max:60'],

            // --- descriptive ---
            'descriptive'                  => ['required', 'array'],
            'descriptive.roast_level'      => ['required', Rule::enum(RoastLevel::class)],
            'descriptive.fragrance'        => ['nullable', 'array'],
            'descriptive.fragrance.score'  => ['nullable', 'integer', 'between:0,15'],
            'descriptive.fragrance.note'   => ['nullable', 'string'],
            'descriptive.fragrance.cata'   => ['array'],
            'descriptive.fragrance.cata.*' => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],
            'descriptive.aroma'            => ['nullable', 'array'],
            'descriptive.aroma.score'      => ['nullable', 'integer', 'between:0,15'],
            'descriptive.aroma.note'       => ['nullable', 'string'],
            'descriptive.aroma.cata'       => ['array'],
            'descriptive.aroma.cata.*'     => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],
            'descriptive.flavor'           => ['nullable', 'array'],
            'descriptive.flavor.score'     => ['nullable', 'integer', 'between:0,15'],
            'descriptive.flavor.note'      => ['nullable', 'string'],
            'descriptive.flavor.cata'      => ['array'],
            'descriptive.flavor.cata.*'    => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],
            'descriptive.acidity'          => ['nullable', 'array'],
            'descriptive.acidity.score'    => ['nullable', 'integer', 'between:0,15'],
            'descriptive.acidity.note'     => ['nullable', 'string'],
            'descriptive.sweetness'        => ['nullable', 'array'],
            'descriptive.sweetness.score'  => ['nullable', 'integer', 'between:0,15'],
            'descriptive.sweetness.note'   => ['nullable', 'string'],
            'descriptive.mouthfeel'        => ['nullable', 'array'],
            'descriptive.mouthfeel.score'  => ['nullable', 'integer', 'between:0,15'],
            'descriptive.mouthfeel.note'   => ['nullable', 'string'],
            'descriptive.mouthfeel.cata'   => ['array'],
            'descriptive.mouthfeel.cata.*' => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],
            'descriptive.aftertaste'       => ['nullable', 'array'],
            'descriptive.aftertaste.score' => ['nullable', 'integer', 'between:0,15'],
            'descriptive.aftertaste.note'  => ['nullable', 'string'],
            'descriptive.aftertaste.cata'   => ['array'],
            'descriptive.aftertaste.cata.*' => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],
            'descriptive.mainTastes'      => ['nullable', 'array'],
            'descriptive.mainTastes.*'    => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],

            // --- affective ---
            'affective'                   => ['required', 'array'],
            'affective.overall.score'     => ['nullable', 'integer', 'between:1,9'],
            'affective.overall.note'      => ['nullable', 'string'],
            'affective.fragrance.score'   => ['nullable', 'integer', 'between:1,9'],
            'affective.fragrance.note'    => ['nullable', 'string'],
            'affective.aroma.score'       => ['nullable', 'integer', 'between:1,9'],
            'affective.aroma.note'        => ['nullable', 'string'],
            'affective.flavor.score'      => ['nullable', 'integer', 'between:1,9'],
            'affective.flavor.note'       => ['nullable', 'string'],
            'affective.aftertaste.score'  => ['nullable', 'integer', 'between:1,9'],
            'affective.aftertaste.note'   => ['nullable', 'string'],
            'affective.acidity.score'     => ['nullable', 'integer', 'between:1,9'],
            'affective.acidity.note'      => ['nullable', 'string'],
            'affective.sweetness.score'   => ['nullable', 'integer', 'between:1,9'],
            'affective.sweetness.note'    => ['nullable', 'string'],
            'affective.mouthfeel.score'   => ['nullable', 'integer', 'between:1,9'],
            'affective.mouthfeel.note'    => ['nullable', 'string'],
            'affective.defects'           => ['array'],
            'affective.defects.*'         => ['string', 'distinct', 'exists:olfactory_taxonomies,ulid'],

            // --- extrinsics ---
            'extrinsics'                     => ['nullable', 'array'],
            'extrinsics.farming'             => ['nullable', 'string'],
            'extrinsics.processing'          => ['nullable', 'string'],
            'extrinsics.trading'             => ['nullable', 'string'],
            'extrinsics.certifications'      => ['nullable', 'string'],
            'extrinsics.generalObservation' => ['nullable', 'string'],
        ];
    }
}
