<?php

namespace App\Http\Requests;

use App\Models\Offering;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class MassDeleteOfferingRequest extends FormRequest
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
            'offerings' => ['required', 'array', 'max:50'],
            'offerings.*' => ['required', 'string', 'exists:offerings,ulid'],
        ];
    }

    public function offerings(): array
    {
        $offeringsId = $this->validated()['offerings'];
        return $offeringsId;
    }
}
