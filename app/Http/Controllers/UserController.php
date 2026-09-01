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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
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

        return (new ResourcesUser($user))->response()->setStatusCode(201, 'User Created');
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
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return (new ResourcesUser($user))->response()->setStatusCode(200, 'User Updated');
    }

    /**
     * Update password for user model
     */
    public function updatePassword(UpdatePasswordRequest $request, User $user)
    {
        $user->password = $request->password;
        $user->save();

        return response()->json(['message' => __('auth.password_updated')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**Revoke tokens: logging user out */

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
