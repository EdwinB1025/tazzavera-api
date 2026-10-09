<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterOfferingRequest;
use App\Http\Requests\MassDeleteOfferingRequest;
use App\Http\Requests\StoreOfferingRequest;
use App\Http\Resources\OfferingResource;
use App\Models\CoffeeInventory;
use App\Models\Evaluation;
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
     * @responseFile storage/scribe/responses/offerings.index.json
     * @responseFile 422 scenario="Invalid filters" storage/scribe/responses/offerings.index.422.json
     */
    public function index(FilterOfferingRequest $request)
    {
        $offerings = Offering::query()
            ->filter($request->validated())
            ->with([
                'location',
                'coffeeInventory.coffee',
                'coffeeInventory.roastery',
                'sensoryTastes.taxonomy',
                'sensoryTastes.children.taxonomy',
                'sensoryTastes.children.children.taxonomy',
                'cataTastes.taxonomy',
                'cataTastes.children.taxonomy',
                'cataTastes.children.children.taxonomy',
            ])
            ->paginate($request->perPage());

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
     * @responseFile 201 storage/scribe/responses/offerings.store.json
     * @responseFile 422 scenario="Validation error" storage/scribe/responses/offerings.store.422.json
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
     * @urlParam offering_ulid string required The ULID of the offering. Example: 01K6A3M8Q2V7XH4T9B5N1RCW0D
     * @responseFile storage/scribe/responses/offerings.show.json
     * @responseFile 404 scenario="Not found" storage/scribe/responses/errors.404.json
     */
    public function show(Offering $offering)
    {
        $offering->load([
            'location',
            'coffeeInventory.coffee',
            'coffeeInventory.roastery',
            'sensoryTastes.taxonomy',
            'sensoryTastes.children.taxonomy',
            'sensoryTastes.children.children.taxonomy',
            'cataTastes.taxonomy',
            'cataTastes.children.taxonomy',
            'cataTastes.children.children.taxonomy',
        ]);

        return new OfferingResource($offering);
    }

    /**
     * Delete an offering
     *
     * Permanently deletes a single offering. This action cannot be undone.
     * The offering's baseline (provisional) evaluation is deleted with it;
     * specialists' evaluations are kept without an offering.
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:write`
     * scope. The authenticated user must own the offering (`owns.offering`).
     *
     * @group Offerings
     *
     * @authenticated
     * @urlParam offering_ulid string required The ULID of the offering. Example: 01K6A3M8Q2V7XH4T9B5N1RCW0D
     * 
     * @response 200 scenario="Deleted" {"message": "Offering(s) deleted."}
     * @responseFile 404 scenario="Not found" storage/scribe/responses/errors.404.json
     */
    public function destroy(Offering $offering)
    {
        DB::transaction(
            function () use ($offering) {
                //EDB 10/07/26: the baseline belongs to the offering; specialists' evaluations keep a null offering.
                $offering->evaluations()->where('evaluation_type', 'baseline')->delete();
                $offering->delete();
            }
        );

        return response()->json(['message' => __('offerings.deleted')], 200);
    }

    /**
     * Delete multiple offerings
     *
     * Permanently deletes several offerings in one request, identified by their
     * ULIDs (maximum 50). This action cannot be undone. Each offering's
     * baseline (provisional) evaluation is deleted with it; specialists'
     * evaluations are kept without an offering.
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:write`
     * scope. The authenticated user must own all referenced offerings
     * (`owns.offering`).
     *
     * @group Offerings
     *
     * @authenticated
     * 
     * @response 200 scenario="Deleted" {"message": "Offering(s) deleted."}
     * @responseFile 422 scenario="Validation error" storage/scribe/responses/offerings.massdestroy.422.json
     */
    public function massDestroy(MassDeleteOfferingRequest $request)
    {
        DB::transaction(
            function () use ($request) {
                $offeringIds = Offering::whereIn('ulid', $request->offerings())->pluck('id');

                //EDB 10/07/26: the baselines belong to the offerings; specialists' evaluations keep a null offering.
                Evaluation::whereIn('offering_id', $offeringIds)
                    ->where('evaluation_type', 'baseline')
                    ->delete();

                Offering::whereIn('id', $offeringIds)
                    //->get() EDB 09/16/26: deleting from collection to enable the event generation for future notifications if needed.
                    ->delete();
            }
        );

        return response()->json(['message' => __('offerings.deleted')], 200);
    }
}
