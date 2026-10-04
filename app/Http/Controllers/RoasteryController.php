<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRoasteryRequest;
use App\Http\Resources\RoasteryResource;
use App\Models\Roastery;
use Illuminate\Http\Request;

class RoasteryController extends Controller
{
    /**
     * List roasteries
     *
     * Returns the roastery catalog. Supports filtering by name via query
     * parameters.
     *
     * **Authorization:** requires the `profile:read` or `profile:write` scope.
     * Available to any authenticated user, regardless of role.
     *
     * @group Roasteries
     *
     * @authenticated
     *
     * @responseFile storage/scribe/responses/roasteries.index.json
     */
    public function index(FilterRoasteryRequest $request)
    {

        $validated = $request->validated();


        $Roasteries = Roastery::query()->filter($validated)
            ->get();

        return RoasteryResource::collection($Roasteries);
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
