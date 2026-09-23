<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterCoffeeInventoryRequest;
use App\Http\Resources\CoffeeInventoryResource;
use App\Models\CoffeeInventory;
use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;

class CoffeeInventoryController extends Controller
{
    /**
     * List coffee inventory
     *
     * Returns coffee inventory items, each with coffee details (including
     * currently valid certifications) and roastery. Used by coffeeshops to
     * retrieve available coffees when creating an offering. Supports filtering
     * via query parameters.
     *
     * Only non-expired certifications are included (those with no expiry, or an
     * expiry date in the future).
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:read`
     * or `profile:write` scope.
     *
     * @group Coffee Inventory
     *
     * @authenticated
     *
     * @apiResourceCollection App\Http\Resources\CoffeeInventoryResource
     * @apiResourceModel App\Models\CoffeeInventory with=coffee,roastery
     */
    public function index(FilterCoffeeInventoryRequest $request)
    {

        $validated = $request->validated();


        $CoffeeInventory = CoffeeInventory::query()->filter($validated)
            ->with([
                'coffee.certificationTypes' => function ($query) {
                    $query->wherePivot('expires_at', '>', now())
                        ->orWherePivotNull('expires_at');
                },
                'roastery'
            ])
            ->get();

        return CoffeeInventoryResource::collection($CoffeeInventory);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
