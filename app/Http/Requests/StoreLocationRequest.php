<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
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
     * EDB 10/06/26: user_id is never taken from the body, the controller uses the authenticated user.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'latitud'     => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitud'],
            'longitud'    => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitud'],
            'contact'     => ['required', 'array'],
            ...StoreContactRequest::contactRules('contact.'),
        ];
    }

    /**
     * Body parameters documented for Scribe.
     *
     * @return array<string, array>
     */
    public function bodyParameters(): array
    {
        return [
            'name'        => ['description' => 'Location name (max 150 characters).', 'example' => 'Cafeteria Gràcia'],
            'description' => ['description' => 'Optional. Short description (max 255 characters).', 'example' => 'Barra de especialidad en Gràcia.'],
            'latitud'     => ['description' => 'Optional. Latitude (-90 to 90), computed by the client. Required when longitud is sent.', 'example' => 41.4029],
            'longitud'    => ['description' => 'Optional. Longitude (-180 to 180), computed by the client. Required when latitud is sent.', 'example' => 2.1565],
            'contact'     => ['description' => 'Primary contact of the location. address and city are required.'],
            ...StoreContactRequest::contactParameters('contact.'),
        ];
    }

    /** Location attributes, without the contact */
    public function location(): array
    {
        return collect($this->validated())->except('contact')->all();
    }

    /** Primary contact attributes */
    public function contact(): array
    {
        return $this->validated('contact');
    }
}
