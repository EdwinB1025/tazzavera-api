<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterCoffeeshopRequest;
use App\Http\Resources\CoffeeshopResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $coffeeshops = User::query()
            ->coffeeshops()
            ->withCoffeeshopCounts()
            ->filter($request->validated())
            ->with('locations.primaryContact')
            ->paginate();

        return CoffeeshopResource::collection($coffeeshops);
    }

    /**
     * Get a coffee shop
     *
     * Returns one coffee shop (business) by its ULID, with its counters and
     * locations. The detail page shows two tabs: **Offerings**, read with
     * `GET /offerings?coffeeshopUlid={ulid}`, and **Locations**, the
     * `locations` array of this response. A ULID that does not exist, or that
     * is not a coffee shop with at least one location, returns `404 Not Found`.
     *
     * @group Coffee shops
     *
     * @unauthenticated
     *
     * @urlParam user_ulid string required The ULID of the coffee shop (its user ULID). Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile storage/scribe/responses/coffeeshops.show.json
     * @responseFile 404 storage/scribe/responses/coffeeshops.show.404.json
     */
    public function show(User $user)
    {
        //EDB 10/07/26: a user outside the directory is a 404 with the same response as a missing ULID (route model binding).
        throw_unless($user->isListedCoffeeshop(), (new ModelNotFoundException)->setModel(User::class, [$user->ulid]));

        $user->loadCoffeeshopCounts()->load('locations.primaryContact');

        return new CoffeeshopResource($user);
    }
}
