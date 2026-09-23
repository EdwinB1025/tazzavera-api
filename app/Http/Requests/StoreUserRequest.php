<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'surname' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['sometimes', Rule::enum(Roles::class)],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'User first name.', 'example' => 'Augusto'],
            'surname' => ['description' => 'User last name.', 'example' => 'Restrepo'],
            'email' => ['description' => 'Email address. Must be unique.', 'example' => 'augusto@example.com'],
            'password' => ['description' => 'Password. Must meet the default strength rules and be confirmed via password_confirmation.', 'example' => 'Str0ngP@ss!'],
            'role' => ['description' => 'Optional. Role to assign at registration. One of: coffeeshop, specialist, user.', 'example' => 'coffeeshop'],
        ];
    }
}
