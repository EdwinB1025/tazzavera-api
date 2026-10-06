<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterCoffeeshopRequest;
use App\Http\Resources\CoffeeshopResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**EDB 10/06/26: Public coffee shop directory. A coffee shop is the business (a user with role coffeeshop) that owns several locations */
class CoffeeshopController extends Controller
{
    /**
     * List coffee shops
     *
     * Returns a paginated list of coffee shops (businesses) that have at least
     * one location, each with its counters and all its locations (the map
     * shows every location of the filtered coffee shops). Only business data
     * is returned: never the owner's email, surname, personal contacts or
     * account data. Sorted by name ascending by default.
     *
     * @group Coffee shops
     *
     * @unauthenticated
     *
     * @responseFile storage/scribe/responses/coffeeshops.index.json
     * @responseFile 422 storage/scribe/responses/coffeeshops.index.422.json
     */
    public function index(FilterCoffeeshopRequest $request)
    {
        $validated = $request->validated();

        $coffeeshops = $this->coffeeshops()
            ->has('locations')
            ->filter($validated)
            ->paginate($validated['perPage'] ?? 15);

        return CoffeeshopResource::collection($coffeeshops);
    }

    /**
     * Get a coffee shop
     *
     * Returns one coffee shop (business) by its ULID, with its counters and
     * locations. The detail page shows two tabs: **Offerings**, read with
     * `GET /offerings?coffeeshopUlid={ulid}`, and **Locations**, the
     * `locations` array of this response. A ULID that does not exist, or that
     * is not a coffee shop, returns `404 Not Found`.
     *
     * @group Coffee shops
     *
     * @unauthenticated
     *
     * @urlParam user string required The ULID of the coffee shop (its user ULID). Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile storage/scribe/responses/coffeeshops.show.json
     * @responseFile 404 storage/scribe/responses/coffeeshops.show.404.json
     */
    public function show(string $user)
    {
        //EDB 10/06/26: resolved by query, not route model binding, so a user that is not a coffee shop is a 404 with the same message as a missing one.
        $coffeeshop = $this->coffeeshops()
            ->where('ulid', $user)
            ->firstOrFail();

        return new CoffeeshopResource($coffeeshop);
    }

    /** Coffee shop users with their counters and locations (business fields only) */
    private function coffeeshops(): Builder
    {
        return User::role('coffeeshop')
            ->select(['users.id', 'users.ulid', 'users.name'])
            ->withCount([
                'locations',
                'offerings',
                'offerings as verified_offerings_count' => fn($q) => $q->where('verification_status', 'verified'),
            ])
            ->with('locations.primaryContact');
    }
}
