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
            'descriptive.roastLevel'      => ['required', Rule::enum(RoastLevel::class)],
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
            'affective.defects'           => ['nullable', 'array'],
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
    public function bodyParameters(): array
    {
        return [
            'offeringId' => ['description' => 'ULID of the offering being evaluated.', 'example' => '01M35F5RX4ADGC3CSDXXYB08DA'],
            'extractionMethod' => ['description' => 'Brewing/extraction method used (max 60 chars).', 'example' => 'v60'],

            'descriptive' => ['description' => 'Objective assessment block.', 'example' => null],
            'descriptive.roastLevel' => ['description' => 'Roast level. One of: light, medium_light, medium, medium_dark, dark.', 'example' => 'medium'],

            'descriptive.fragrance.score' => ['description' => 'Fragrance intensity (0–15).', 'example' => 11],
            'descriptive.fragrance.cata' => ['description' => 'Fragrance olfactory taxonomy ULIDs.', 'example' => ['01M35F5JEZMS845TNPAEM1V8PF', '01M35F5JDFXM13R1CA06FFWTNG']],
            'descriptive.fragrance.note' => ['description' => 'Optional free-text note.', 'example' => null],

            'descriptive.aroma.score' => ['description' => 'Aroma intensity (0–15).', 'example' => 5],
            'descriptive.aroma.cata' => ['description' => 'Aroma taxonomy ULIDs.', 'example' => ['01M35F5JF0SDAPSEG7S4RNVN5B']],
            'descriptive.aroma.note' => ['description' => 'Optional note.', 'example' => null],

            'descriptive.flavor.score' => ['description' => 'Flavor intensity (0–15).', 'example' => 12],
            'descriptive.flavor.cata' => ['description' => 'Flavor taxonomy ULIDs.', 'example' => ['01M35F5JF6C4CT8M2EQHGF71WC']],
            'descriptive.flavor.note' => ['description' => 'Optional note.', 'example' => null],

            'descriptive.aftertaste.score' => ['description' => 'Aftertaste intensity (0–15).', 'example' => 5],
            'descriptive.aftertaste.cata' => ['description' => 'Aftertaste taxonomy ULIDs.', 'example' => ['01M35F5JDCQF4VADVGDWV1WD93']],
            'descriptive.aftertaste.note' => ['description' => 'Optional note.', 'example' => null],

            'descriptive.acidity.score' => ['description' => 'Acidity intensity (0–15).', 'example' => 12],
            'descriptive.acidity.note' => ['description' => 'Optional note. No cata for acidity.', 'example' => null],

            'descriptive.sweetness.score' => ['description' => 'Sweetness intensity (0–15).', 'example' => 6],
            'descriptive.sweetness.note' => ['description' => 'Optional note. No cata for sweetness.', 'example' => null],

            'descriptive.mouthfeel.score' => ['description' => 'Mouthfeel intensity (0–15).', 'example' => 11],
            'descriptive.mouthfeel.cata' => ['description' => 'Mouthfeel taxonomy ULIDs.', 'example' => ['01M35F5JFJFCT0W4Y3VZ5047S1']],
            'descriptive.mouthfeel.note' => ['description' => 'Optional note.', 'example' => null],

            'descriptive.mainTastes' => ['description' => 'Main taste taxonomy ULIDs.', 'example' => ['01M35F5JFEBVZNFTS64GCRGSGX']],

            'affective' => ['description' => 'Preference assessment block. Scores 1–9.', 'example' => null],
            'affective.overall.score' => ['description' => 'Overall preference (1–9).', 'example' => 8],
            'affective.overall.note' => ['description' => 'Optional note.', 'example' => 'Balanced, clean finish.'],
            'affective.fragrance.score' => ['description' => 'Fragrance preference (1–9).', 'example' => 7],
            'affective.fragrance.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.aroma.score' => ['description' => 'Aroma preference (1–9).', 'example' => 7],
            'affective.aroma.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.flavor.score' => ['description' => 'Flavor preference (1–9).', 'example' => 9],
            'affective.flavor.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.aftertaste.score' => ['description' => 'Aftertaste preference (1–9).', 'example' => 9],
            'affective.aftertaste.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.acidity.score' => ['description' => 'Acidity preference (1–9).', 'example' => 9],
            'affective.acidity.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.sweetness.score' => ['description' => 'Sweetness preference (1–9).', 'example' => 9],
            'affective.sweetness.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.mouthfeel.score' => ['description' => 'Mouthfeel preference (1–9).', 'example' => 8],
            'affective.mouthfeel.note' => ['description' => 'Optional note.', 'example' => null],
            'affective.defects' => ['description' => 'Defect taxonomy ULIDs. Empty if none.', 'example' => []],

            'extrinsics.farming' => ['description' => 'Optional farming context.', 'example' => null],
            'extrinsics.processing' => ['description' => 'Optional processing context.', 'example' => null],
            'extrinsics.trading' => ['description' => 'Optional trading context.', 'example' => null],
            'extrinsics.certifications' => ['description' => 'Optional certifications.', 'example' => null],
            'extrinsics.generalObservation' => ['description' => 'Optional general observation.', 'example' => null],
        ];
    }
}
