<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResource;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * List my locations
     *
     * Returns the locations owned by the authenticated user, each with its
     * contact details. Used by coffeeshops to retrieve their locations when
     * creating an offering.
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:read`
     * or `profile:write` scope.
     *
     * @group Locations
     *
     * @authenticated
     *
     * @apiResourceCollection App\Http\Resources\LocationResource
     * @apiResourceModel App\Models\Location
     */
    public function index(Request $request)
    {
        $locations = $request->user()->locations()->with('contacts')->get();
        return LocationResource::collection($locations);
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
