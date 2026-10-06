<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
        return self::contactRules();
    }

    /**
     * EDB 10/06/26: contact rules shared with StoreLocationRequest (nested under `contact`).
     * is_primary is never taken from the body: the controller sets it.
     *
     * @return array<string, array<mixed>>
     */
    public static function contactRules(string $prefix = ''): array
    {
        return [
            "{$prefix}phone"       => ['nullable', 'string', 'max:25'],
            "{$prefix}email"       => ['nullable', 'email', 'max:255'],
            "{$prefix}web"         => ['nullable', 'url', 'max:255'],
            "{$prefix}social"      => ['nullable', 'string', 'max:255'],
            "{$prefix}address"     => ['required', 'string', 'max:255'],
            "{$prefix}country"     => ['nullable', 'string', 'max:60'],
            "{$prefix}city"        => ['required', 'string', 'max:90'],
            "{$prefix}postal_code" => ['nullable', 'string', 'max:12'],
        ];
    }

    /**
     * Body parameters documented for Scribe.
     *
     * @return array<string, array>
     */
    public function bodyParameters(): array
    {
        return self::contactParameters();
    }

    /**
     * EDB 10/06/26: contact parameters shared with StoreLocationRequest.
     *
     * @return array<string, array>
     */
    public static function contactParameters(string $prefix = ''): array
    {
        return [
            "{$prefix}phone"       => ['description' => 'Optional. Phone number (max 25 characters).', 'example' => '+34 932 123 456'],
            "{$prefix}email"       => ['description' => 'Optional. Contact email address (max 255 characters).', 'example' => 'hola@cafeteria.example'],
            "{$prefix}web"         => ['description' => 'Optional. Website URL (max 255 characters).', 'example' => 'https://cafeteria.example'],
            "{$prefix}social"      => ['description' => 'Optional. Social media handle or URL (max 255 characters).', 'example' => '@cafeteria.bcn'],
            "{$prefix}address"     => ['description' => 'Street address (max 255 characters).', 'example' => 'Carrer de Verdi 12'],
            "{$prefix}country"     => ['description' => 'Optional. Country (max 60 characters).', 'example' => 'España'],
            "{$prefix}city"        => ['description' => 'City (max 90 characters).', 'example' => 'Barcelona'],
            "{$prefix}postal_code" => ['description' => 'Optional. Postal code (max 12 characters).', 'example' => '08012'],
        ];
    }
}
