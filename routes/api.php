<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'store']);

Route::middleware('auth:api')
    ->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
    });
