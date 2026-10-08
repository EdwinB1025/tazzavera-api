<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\User;

/**EDB 10/06/26: Contacts of the authenticated user (personal data, only for the owner) */
class ContactController extends Controller
{
    /**
     * List my contacts
     *
     * Returns the contacts of the authenticated user (personal contact data,
     * not the contacts of their locations).
     *
     * **Authorization:** requires the `profile:read` or `profile:write` scope
     * and the user must be the authenticated user (policy). Another user
     * receives `403 Forbidden`.
     *
     * @group Users
     *
     * @authenticated
     *
     * @urlParam user_ulid string required The ULID of the authenticated user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile storage/scribe/responses/contacts.index.json
     * @responseFile 401 storage/scribe/responses/errors.401.json
     * @responseFile 403 storage/scribe/responses/errors.403.json
     */
    public function index(User $user)
    {
        return ContactResource::collection($user->contacts()->get());
    }

    /**
     * Create my primary contact
     *
     * Creates the primary contact of the authenticated user. A user has a
     * single primary contact: if it already exists the request fails with
     * `409 Conflict`. `isPrimary` is always set by the API, never taken from
     * the body.
     *
     * **Authorization:** requires the `profile:read` or `profile:write` scope
     * (no step-up re-authentication) and permission to update this user
     * (policy). Another user receives `403 Forbidden`.
     *
     * @group Users
     *
     * @authenticated
     *
     * @urlParam user_ulid string required The ULID of the authenticated user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile 201 storage/scribe/responses/contacts.store.json
     * @responseFile 401 storage/scribe/responses/errors.401.json
     * @responseFile 403 storage/scribe/responses/errors.403.json
     * @responseFile 409 storage/scribe/responses/contacts.store.409.json
     * @responseFile 422 storage/scribe/responses/contacts.store.422.json
     */
    public function store(StoreContactRequest $request, User $user)
    {
        abort_if($user->contacts()->where('is_primary', true)->exists(), 409, __('contacts.primary_exists'));

        $contact = $user->contacts()->create([...$request->validated(), 'is_primary' => true]);

        return (new ContactResource($contact))
            ->additional(['message' => __('contacts.created')])
            ->response()
            ->setStatusCode(201);
    }
}
