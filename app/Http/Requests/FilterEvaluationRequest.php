<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterEvaluationRequest extends FormRequest
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
            'evaluatorId'    => ['sometimes', 'string', 'exists:users,ulid'],
            'coffeeId'       => ['sometimes', 'string', 'exists:coffees,ulid'],
            'locationId'     => ['sometimes', 'string', 'exists:locations,ulid'],
            'city'           => ['sometimes', 'string', 'max:90'],
            'process'        => ['sometimes', 'string', 'max:90'],
            'status'         => ['sometimes', 'in:open,closed'],
            'scoreMin'       => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'scoreMax'       => ['sometimes', 'numeric', 'min:0', 'max:100', 'gte:scoreMin'],
            'orderBy'        => ['sometimes', 'in:cupping_score,created_at,status'],
            'orderDirection' => ['sometimes', 'in:asc,desc'],
        ];
    }
}
