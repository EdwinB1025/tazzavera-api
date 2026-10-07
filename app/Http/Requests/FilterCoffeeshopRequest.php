<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterCoffeeshopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The directory is sorted by name unless another field is given.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['orderBy' => 'name']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'string', 'max:150'],
            'city'           => ['sometimes', 'string', 'max:90'],
            'postalCode'     => ['sometimes', 'string', 'max:12'],
            'verified'       => ['sometimes', 'boolean'],
            'orderBy'        => ['sometimes', 'in:name'],
            'orderDirection' => ['sometimes', 'in:asc,desc'],
            'page'         => ['sometimes', 'integer', 'min:1'],
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
            'name'           => ['description' => 'Filter by the coffee shop (business) name. Partial, case-insensitive match.', 'example' => 'nomad'],
            'city'           => ['description' => 'Filter coffee shops with at least one location whose primary contact is in this city.', 'example' => 'Barcelona'],
            'postalCode'     => ['description' => 'Filter coffee shops with at least one location whose primary contact has this postal code.', 'example' => '08001'],
            'verified'       => ['description' => 'Filter by verified offerings: 1 returns only coffee shops with at least one verified offering in any of their locations, 0 only those without any. Omit it to return both.', 'example' => 1],
            'orderBy'        => ['description' => 'Sort field. One of: name. Default: name (applied when omitted, with orderDirection asc unless given).', 'example' => 'name'],
            'orderDirection' => ['description' => 'Sort direction. One of: asc, desc. Default: asc.', 'example' => 'asc'],
            'page'           => ['description' => 'Page number. Default: 1.', 'example' => 1],
        ];
    }
}
