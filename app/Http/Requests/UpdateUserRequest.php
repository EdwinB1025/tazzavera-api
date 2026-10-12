<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use App\Traits\HasBusinessName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use HasBusinessName;

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
        $userId = $this->route('user')?->id;
        return [
            'name'    => ['sometimes', 'string'],
            'surname' => ['sometimes', 'string'],
            'email'   => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            //EDB 10/12/26: a coffee shop changes its business name but cannot empty it; other roles cannot send it
            'businessName' => $this->route('user')?->hasRole(Roles::COFFEESHOP->value)
                ? ['sometimes', 'required', 'string', 'max:150']
                : ['prohibited'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'User first name.', 'example' => 'Augusto'],
            'surname' => ['description' => 'User last name.', 'example' => 'Restrepo'],
            'email' => ['description' => 'Email address. Must be unique (excluding the current user).', 'example' => 'augusto@example.com'],
            'businessName' => ['description' => 'Coffee shop only. The business name, its public name in the coffee shop directory (max 150 characters). It cannot be emptied. Any other role sending it receives 422.', 'example' => 'Nomad Coffee'],
        ];
    }
}
