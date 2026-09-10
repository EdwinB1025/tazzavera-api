<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterCoffeeInventoryRequest extends FormRequest
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
            'coffeeName'    => ['nullable', 'string', 'max:150'],
            'originCountry' => ['nullable', 'string', 'max:60'],
            'originRegion'  => ['nullable', 'string', 'max:90'],
            'process'       => ['nullable', 'string', 'max:60'],
            'producer'      => ['nullable', 'string', 'max:150'],
            'city'          => ['nullable', 'string', 'max:90'],
        ];
    }
}
