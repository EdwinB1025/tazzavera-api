<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
            'currentPassword' => ['required', 'current_password:api'],
            'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            'passwordConfirmation' => ['required'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'currentPassword' => ['description' => 'The user\'s current password. Required for verification.', 'example' => 'OldP@ss123'],
            'password' => ['description' => 'The new password. Must meet default strength rules and match passwordConfirmation.', 'example' => 'NewStr0ng!'],
            'passwordConfirmation' => ['description' => 'Confirmation of the new password. Must match the password field. Note: camelCase, not the Laravel-default password_confirmation.', 'example' => 'NewStr0ng!'],
        ];
    }
}
