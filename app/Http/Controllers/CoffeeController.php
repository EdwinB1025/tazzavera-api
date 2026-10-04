<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterCoffeeRequest;
use App\Http\Resources\CoffeeResource;
use App\Models\Coffee;
use Illuminate\Http\Request;

class CoffeeController extends Controller
{
    /**
     * List coffees
     *
     * Returns the coffee catalog, each coffee with its currently valid
     * certifications. Supports filtering by name via query parameters.
     *
     * Only non-expired certifications are included (those with no expiry, or an
     * expiry date in the future).
     *
     * @group Coffees
     *
     * @unauthenticated
     * @responseFile storage/scribe/responses/coffees.index.json
     */
    public function index(FilterCoffeeRequest $request)
    {

        $validated = $request->validated();


        $Coffees = Coffee::query()->filter($validated)
            ->with([
                'certificationTypes' => function ($query) {
                    $query->wherePivot('expires_at', '>', now())
                        ->orWherePivotNull('expires_at');
                },
            ])
            ->get();

        return CoffeeResource::collection($Coffees);
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
