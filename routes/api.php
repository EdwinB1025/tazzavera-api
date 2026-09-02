<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'store']);

Route::middleware('auth:api')
    ->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/user', [UserController::class, 'show']);

        Route::middleware('scope:profile:write')->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update'])
                ->middleware('can:update,user');
            Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])
                ->middleware('can:update,user');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->middleware('can:delete,user');
            Route::delete('/users/{user}/destroy', [UserController::class, 'forceDestroy'])
                ->middleware('can:delete,user')->withTrashed();
        });
    });
