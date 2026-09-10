<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterCoffeeInventoryRequest;
use App\Http\Resources\CoffeeInventoryResource;
use App\Models\CoffeeInventory;
use Illuminate\Http\Request;

class CoffeeInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
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
