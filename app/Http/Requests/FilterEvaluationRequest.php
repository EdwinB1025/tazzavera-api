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
    public function queryParameters(): array
    {
        return [
            'evaluatorId'    => ['description' => 'Filter by the evaluating specialist (user) ULID.', 'example' => '01M35F5RX4ADGC3CSDXXYB08DA'],
            'coffeeId'       => ['description' => 'Filter by coffee ULID.', 'example' => '01M35F5JEZMS845TNPAEM1V8PF'],
            'locationId'     => ['description' => 'Filter by location ULID.', 'example' => '01M35F5JDFXM13R1CA06FFWTNG'],
            'city'           => ['description' => 'Filter by the city of the evaluation location.', 'example' => 'Barcelona'],
            'process'        => ['description' => 'Filter by coffee processing method.', 'example' => 'Washed'],
            'status'         => ['description' => 'Filter by evaluation status. One of: open, closed.', 'example' => 'closed'],
            'scoreMin'       => ['description' => 'Minimum cupping score (0–100).', 'example' => 80],
            'scoreMax'       => ['description' => 'Maximum cupping score (0–100). Must be ≥ scoreMin.', 'example' => 95],
            'orderBy'        => ['description' => 'Sort field. One of: cupping_score, created_at, status.', 'example' => 'cupping_score'],
            'orderDirection' => ['description' => 'Sort direction. One of: asc, desc.', 'example' => 'desc'],
        ];
    }
}
