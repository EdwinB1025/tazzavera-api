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

    public function queryParameters(): array
    {
        return [
            'coffeeName'    => ['description' => 'Filter by coffee name (max 150 chars).', 'example' => 'Geisha'],
            'originCountry' => ['description' => 'Filter by country of origin (max 60 chars).', 'example' => 'Colombia'],
            'originRegion'  => ['description' => 'Filter by region of origin (max 90 chars).', 'example' => 'Huila'],
            'process'       => ['description' => 'Filter by processing method (max 60 chars).', 'example' => 'Washed'],
            'producer'      => ['description' => 'Filter by producer name (max 150 chars).', 'example' => 'Finca La Esperanza'],
            'city'          => ['description' => 'Filter by city (max 90 chars).', 'example' => 'Barcelona'],
        ];
    }
}
