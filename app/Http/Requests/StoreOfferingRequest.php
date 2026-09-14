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

    public function coffeeInventory(): CoffeeInventory
    {
        $coffeeInventoryUlid = $this->validated()['coffeeInventoryId'];
        return CoffeeInventory::where('ulid', $coffeeInventoryUlid)->firstOrFail();
    }

    public function locations(): Collection
    {
        $locationsIds = $this->validated()['locations'];
        return Location::whereIn('ulid', $locationsIds)->get();
    }
}
