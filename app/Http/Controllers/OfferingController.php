<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterOfferingRequest;
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
     * List offerings
     *
     * Returns a paginated list of coffee offerings, each including its taste
     * profile (olfactory taxonomy), coffee inventory (with roastery and coffee
     * details), and location. Supports extensive filtering and sorting via query
     * parameters.
     *
     * @group Offerings
     *
     * @unauthenticated
     */
    public function index(FilterOfferingRequest $request)
    {
        $offerings = Offering::query()
            ->filter($request->validated())
            ->with('offeringTastes.taxonomy', 'coffeeInventory.roastery', 'coffeeInventory.coffee', 'location')
            ->paginate();

        return OfferingResource::collection($offerings);
    }

    /**
     * Create offerings
     *
     * Creates one offering per location for a given coffee inventory item. A single
     * request may target multiple locations; one offering is created for each,
     * all within a single database transaction (all-or-nothing).
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:write`
     * scope. The authenticated user must own every location referenced
     * (`owns.location`).
     *
     * @group Offerings
     *
     * @authenticated
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
     * Get an offering
     *
     * Returns a single offering with its full taste profile. Taste notes are
     * returned as a nested tree (up to three levels of olfactory taxonomy),
     * alongside location, coffee, and roastery details.
     *
     * @group Offerings
     *
     * @unauthenticated
     *
     * @urlParam offering string required The ULID of the offering. Example: 01J8ZKQH3M7...
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
     * Delete an offering
     *
     * Permanently deletes a single offering. This action cannot be undone.
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:write`
     * scope. The authenticated user must own the offering (`owns.offering`).
     *
     * @group Offerings
     *
     * @authenticated
     *
     * @urlParam offering string required The ULID of the offering. Example: 01J8ZKQH3M7...
     *
     * @response 200 scenario="Deleted" {"message": "Offering deleted."}
     */
    public function destroy(Offering $offering)
    {
        $offering->delete();

        return response()->json(['message' => __('offerings.deleted')], 200);
    }

    /**
     * Delete multiple offerings
     *
     * Permanently deletes several offerings in one request, identified by their
     * ULIDs (maximum 50). This action cannot be undone.
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:write`
     * scope. The authenticated user must own all referenced offerings
     * (`owns.offering`).
     *
     * @group Offerings
     *
     * @authenticated
     *
     * @response 200 scenario="Deleted" {"message": "Offering deleted."}
     */
    public function massDestroy(MassDeleteOfferingRequest $request)
    {
        Offering::whereIn('ulid', $request->offerings())
            //->get() EDB 09/16/26: deleting from collection to enable the event generation for future notifications if needed.
            ->delete();

        return response()->json(['message' => __('offerings.deleted')], 200);
    }
}
