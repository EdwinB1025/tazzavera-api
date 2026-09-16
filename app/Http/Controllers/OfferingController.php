<?php

namespace App\Http\Controllers;

use App\Http\Requests\MassDeleteOfferingRequest;
use App\Http\Requests\StoreOfferingRequest;
use App\Http\Resources\OfferingResource;
use App\Models\CoffeeInventory;
use App\Models\Location;
use App\Models\Offering;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfferingRequest $request)
    {
        $offerings = DB::transaction(
            function () use ($request) {

                $response = new Collection();

                $coffeeInventory = CoffeeInventory::where('ulid', $request->coffeeInventoryUlid())
                    ->firstOrFail();
                $locations = Location::whereIn('ulid', $request->locations())->get();

                foreach ($locations as $location) {
                    $response->push(Offering::create(
                        [
                            'coffee_inventory_id' => $coffeeInventory->id,
                            'location_id' => $location->id,
                        ]
                    )->refresh());
                }

                return $response;
            }
        );

        $offerings->load(['location', 'coffeeInventory.coffee', 'coffeeInventory.roastery']);

        return OfferingResource::collection($offerings)
            ->additional(['message' => __('offerings.created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Offering $offering)
    {
        $offering->load([
            'location',
            'coffeeInventory.coffee',
            'coffeeInventory.roastery',
            'offeringTastes' => fn($q) => $q->whereNull('parent_id'),
            'offeringTastes.taxonomy',
            'offeringTastes.children.taxonomy',
            'offeringTastes.children.children.taxonomy',
        ]);

        return new OfferingResource($offering);
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
    public function destroy(Offering $offering)
    {
        $offering->delete();

        return response()->json(['message' => __('offerings.deleted')], 200);
    }

    /**
     * Remove the specified collection of resources.
     */
    public function massDestroy(MassDeleteOfferingRequest $request)
    {
        Offering::whereIn('ulid', $request->offerings())
            //->get() EDB 09/16/26: deleting from collection to enable the event generation for future notifications if needed.
            ->delete();

        return response()->json(['message' => __('offerings.deleted')], 200);
    }
}
