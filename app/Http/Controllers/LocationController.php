<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * @responseFile storage/scribe/responses/locations.index.json
     */
    public function index(Request $request)
    {
        $locations = $request->user()->locations()->with('contacts')->get();
        return LocationResource::collection($locations);
    }

    /**
     * List a user's locations
     *
     * Returns the locations owned by the given user, each with its contact
     * details. Used by the coffeeshop's 'Manage locations' page.
     *
     * **Authorization:** requires the `profile:read` or `profile:write` scope
     * and the user must be the authenticated user (policy). Another user
     * receives `403 Forbidden`.
     *
     * @group Locations
     *
     * @authenticated
     *
     * @urlParam user_ulid string required The ULID of the authenticated user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile storage/scribe/responses/users.locations.json
     * @responseFile 401 storage/scribe/responses/errors.401.json
     * @responseFile 403 storage/scribe/responses/errors.403.json
     */
    public function indexByUser(User $user)
    {
        return LocationResource::collection($user->locations()->with('contacts')->get());
    }

    /**
     * Create a location
     *
     * Creates a location of the authenticated coffeeshop together with its
     * primary contact, in a single database transaction (all-or-nothing). The
     * owner is always the authenticated user, never a value from the body.
     * The coordinates are optional and computed by the client; when one is
     * sent, the other is required.
     *
     * **Authorization:** requires the `coffeeshop` role and the `profile:write`
     * scope.
     *
     * @group Locations
     *
     * @authenticated
     *
     * @responseFile 201 storage/scribe/responses/locations.store.json
     * @responseFile 401 storage/scribe/responses/errors.401.json
     * @responseFile 403 storage/scribe/responses/errors.403.json
     * @responseFile 422 storage/scribe/responses/locations.store.422.json
     */
    public function store(StoreLocationRequest $request)
    {
        $location = DB::transaction(
            function () use ($request) {
                $location = $request->user()->locations()->create($request->location());
                $location->contacts()->create([...$request->contact(), 'is_primary' => true]);

                return $location;
            }
        );

        $location->load('contacts');

        return (new LocationResource($location))
            ->additional(['message' => __('locations.created')])
            ->response()
            ->setStatusCode(201);
    }
}
