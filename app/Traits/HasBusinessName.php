<?php

namespace App\Traits;

use Illuminate\Support\Arr;

/**
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait HasBusinessName
{
    /** EDB 10/12/26: the validated user data, with the body's businessName as the business_name column */
    public function userData(): array
    {
        $validated = $this->validated();

        if (! array_key_exists('businessName', $validated)) {
            return $validated;
        }

        return [...Arr::except($validated, 'businessName'), 'business_name' => $validated['businessName']];
    }
}
