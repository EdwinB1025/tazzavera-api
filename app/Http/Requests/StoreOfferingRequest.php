<?php

namespace App\Http\Requests;

use App\Models\CoffeeInventory;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfferingRequest extends FormRequest
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
            'coffeeInventoryId' => ['required', 'string', 'exists:coffee_inventory,ulid'],
            'locations' => ['required', 'array', 'min:1'],
            'locations.*' => ['string', 'distinct', 'exists:locations,ulid'],
        ];
    }

    public function coffeeInventoryUlid(): string
    {
        return $this->validated()['coffeeInventoryId'];
    }

    public function locations(): array
    {
        return $this->validated()['locations'];
    }

    /**
     * Body parameters documented for Scribe.
     *
     * @return array<string, array>
     */
    public function bodyParameters(): array
    {
        return [
            'coffeeInventoryId' => [
                'description' => 'The ULID of the coffee inventory item the offerings are based on. Despite the name, this expects a ULID, not a numeric ID.',
                'example' => '01J8ZK...',
            ],
            'locations' => [
                'description' => 'Array of location ULIDs. One offering is created per location. At least one required; values must be distinct.',
                'example' => ['01J8ZK...', '01J8ZM...'],
            ],
        ];
    }
}
