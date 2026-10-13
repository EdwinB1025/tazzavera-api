<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use App\Traits\HasBusinessName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;

class StoreUserRequest extends FormRequest
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
        return [
            'name' => ['required', 'string'],
            'surname' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            'passwordConfirmation' => ['required'],
            'role' => ['sometimes', Rule::enum(Roles::class)],
            //EDB 10/12/26: the coffee shop's public name; other roles cannot send it (422, as offeringId on the evaluation update)
            'businessName' => ['required_if:role,' . Roles::COFFEESHOP->value, 'prohibited_unless:role,' . Roles::COFFEESHOP->value, 'string', 'max:150'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'User first name.', 'example' => 'Augusto'],
            'surname' => ['description' => 'User last name.', 'example' => 'Restrepo'],
            'email' => ['description' => 'Email address. Must be unique.', 'example' => 'augusto@example.com'],
            'password' => ['description' => 'Password. Must meet the default strength rules and be confirmed via passwordConfirmation.', 'example' => 'Str0ngP@ss!'],
            'passwordConfirmation' => ['description' => 'Confirmation of the new password. Must match the password field. Note: camelCase, not the Laravel-default password_confirmation.', 'example' => 'NewStr0ng!'],
            'role' => ['description' => 'Optional. Role to assign at registration. One of: coffeeshop, specialist, user.', 'example' => 'coffeeshop'],
            'businessName' => ['description' => 'The coffee shop\'s business name, its public name in the coffee shop directory (max 150 characters). Required when role is coffeeshop; any other role sending it receives 422. name and surname stay the person\'s own.', 'example' => 'Nomad Coffee'],
        ];
    }
}
