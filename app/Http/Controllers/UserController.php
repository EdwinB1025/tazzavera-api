<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\UpdateUserPassword;
use App\Exceptions\RoleAssignmentExcpetion;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\User as ResourcesUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Token;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    /**
     * Register a user
     *
     * Creates a new user account and assigns the requested role. Public
     * registration endpoint. User creation and role assignment happen in a
     * single transaction; if role assignment fails, the whole operation is
     * rolled back.
     *
     * @group Users
     *
     * @unauthenticated
     *
     * @apiResource App\Http\Resources\User
     * @apiResourceModel App\Models\User
     *
     * @response 201 scenario="Created" {"data": {}, "message": "Profile created."}
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $user = DB::transaction(
            function () use ($data) {
                $user = User::create($data);
                try {
                    $user->assignRole($data['role']);
                } catch (Throwable $e) {
                    throw new RoleAssignmentExcpetion(
                        $user->id,
                        $data['role'],
                        previous: $e
                    );
                }

                return $user;
            }
        );

        return (new ResourcesUser($user))
            ->additional(['message' => __('user.created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get the authenticated user
     *
     * Returns the profile of the currently authenticated user.
     *
     * **Authorization:** requires the `profile:read` or `profile:write` scope.
     *
     * @group Users
     *
     * @authenticated
     *
     * @apiResource App\Http\Resources\User
     * @apiResourceModel App\Models\User
     */
    public function show(Request $request)
    {
        $user = $request->user();
        return new ResourcesUser($user);
    }

    /**
     * Update a user
     *
     * Updates a user's profile data. Returns the updated user with a
     * confirmation message.
     *
     * **Authorization:** requires the `profile:write` scope and permission to
     * update this user (policy). A non-owner receives `403 Forbidden`.
     *
     * @group Users
     *
     * @authenticated
     *
     * @urlParam user string required The ULID of the user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 200 scenario="Updated" {"data": {}, "message": "Profile updated."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return (new ResourcesUser($user))
            ->additional(['message' => __('user.updated')])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update user password
     *
     * Changes a user's password. The current password must be supplied and is
     * verified before the change is applied.
     *
     * **Authorization:** requires the `profile:write` scope and permission to
     * update this user (policy). A non-owner receives `403 Forbidden`.
     *
     * @group Users
     *
     * @authenticated
     *
     * @urlParam user string required The ULID of the user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 200 scenario="Password updated" {"message": "Password updated successfully."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     */
    public function updatePassword(UpdatePasswordRequest $request, User $user)
    {
        $user->password = $request->password;
        $user->save();

        return response()->json(['message' => __('user.password_updated')], 200);
    }

    /**
     * Deactivate a user
     *
     * Soft-deletes (deactivates) a user account; the account can be restored
     * later. Also revokes the requesting user's access tokens.
     *
     * **Authorization:** requires the `profile:write` scope and permission to
     * delete this user (policy). A non-owner receives `403 Forbidden`.
     *
     * @group Users
     *
     * @authenticated
     *
     * @urlParam user string required The ULID of the user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 200 scenario="Deactivated" {"message": "Profile deactivated."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     */
    public function destroy(Request $request, User $user)
    {
        $request->user()->tokens->each->revoke();
        $user->delete();

        return response()->json(['message' => __('user.deactivated')], 200);
    }

    /**
     * Permanently delete a user
     *
     * Permanently deletes a user account (hard delete). This cannot be undone.
     * Also revokes the requesting user's access tokens.
     *
     * **Authorization:** requires the `profile:write` scope and permission to
     * delete this user (policy). A non-owner receives `403 Forbidden`.
     *
     * @group Users
     *
     * @authenticated
     *
     * @urlParam user string required The ULID of the user. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 200 scenario="Deleted" {"message": "Profile deleted."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     */
    public function forceDestroy(Request $request, User $user)
    {
        $request->user()->tokens->each->revoke();
        $user->forceDelete();

        return response()->json(['message' => __('user.deleted')], 200);
    }

    /**
     * Log out
     *
     * Revokes all of the authenticated user's access tokens and their refresh
     * tokens, ending the session.
     *
     * **Authorization:** requires the `profile:read` or `profile:write` scope.
     *
     * @group Users
     *
     * @authenticated
     *
     * @response 200 scenario="Logged out" {"message": "logout successfully."}
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        //Revoking all the tokens

        $user->tokens()->each(function (Token $token) {
            $token->revoke();
            $token->refreshToken?->revoke();
        });

        return response()->json(['message' => __('auth.logged_out')], 200);
    }
}
