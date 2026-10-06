<?php

use App\Http\Controllers\CoffeeController;
use App\Http\Controllers\CoffeeInventoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OfferingController;
use App\Http\Controllers\RoasteryController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEvaluationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

/**EDB 09/10/26: Public routes */
Route::post('/register', [UserController::class, 'store']);
Route::get('/taxonomies', [TaxonomyController::class, 'index']);
Route::get('/offerings/{offering}', [OfferingController::class, 'show']);
Route::get('/offerings', [OfferingController::class, 'index']);
Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show']);
Route::get('/evaluations', [EvaluationController::class, 'index']);
Route::get('/users/{user}/evaluations', [EvaluationController::class, 'indexByUser']); //EDB 10/06/26: public evaluations of a given user (evaluator)
Route::get('/coffees', [CoffeeController::class, 'index']);
Route::get('/roasteries', [RoasteryController::class, 'index']);


Route::middleware(['auth:api', CheckTokenForAnyScope::using('profile:read', 'profile:write')]) //EDB 09/16/26: adding the read general scope, RejectWildcardScope force client to request a valid scope.
    ->group(function () {

        /**EDB 09/10/26: Routes to collect user profile data and logs out */

        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/user', [UserController::class, 'show']);

        /**EDB 10/06/26: Routes for user to read their own contacts and locations (personal and management data) */
        Route::middleware('can:view,user')->group(function () {
            Route::get('/users/{user}/contacts', [ContactController::class, 'index']);
            Route::get('/users/{user}/locations', [LocationController::class, 'indexByUser']);
        });

        /**EDB 09/10/26: Routes for coffeeshops to retrive information to create an offering*/
        Route::middleware('role:coffeeshop')->group(function () {
            Route::get('/locations', [LocationController::class, 'index']);
            Route::post('/locations', [LocationController::class, 'store'])
                ->middleware(CheckTokenForAnyScope::using('profile:write')); //EDB 10/06/26: location + primary contact
            Route::get('/coffeeInventory', [CoffeeInventoryController::class, 'index']);
            Route::post('/offerings', [OfferingController::class, 'store'])
                ->middleware('owns.location:locations');
            Route::delete('/offerings/{offering}', [OfferingController::class, 'destroy'])
                ->middleware(['owns.offering', CheckTokenForAnyScope::using('profile:write')]);
            Route::delete('/offerings', [OfferingController::class, 'massDestroy'])
                ->middleware(['owns.offering:offerings', CheckTokenForAnyScope::using('profile:write')]);
        });

        /**EDB 09/17/26: Routes for specialist to manage evaluations */
        Route::middleware('role:specialist')->group(function () {

            /**EDB 10/06/26: Routes for specialist to read and manage their own evaluations */
            Route::get('/user/evaluations', [UserEvaluationController::class, 'index']);
            Route::get('/user/evaluations/{evaluation}', [UserEvaluationController::class, 'show']);
            Route::post('/user/evaluations', [UserEvaluationController::class, 'store']);
            Route::middleware('can:update,evaluation')->group(function () {
                Route::put('/user/evaluations/{evaluation}', [UserEvaluationController::class, 'update']);
                Route::patch('/user/evaluations/{evaluation}/close', [UserEvaluationController::class, 'close']);
            });
            Route::delete('/user/evaluations/{evaluation}', [UserEvaluationController::class, 'destroy'])
                ->middleware('can:delete,evaluation');

            /**EDB 10/06/26: Previous paths kept active, same controller; named legacy.* and left out of the API docs */
            Route::post('/evaluations', [UserEvaluationController::class, 'store'])->name('legacy.evaluations.store');
            Route::middleware('can:update,evaluation')->group(function () {
                Route::put('/evaluations/{evaluation}', [UserEvaluationController::class, 'update'])->name('legacy.evaluations.update');
                Route::patch('/evaluations/{evaluation}/close', [UserEvaluationController::class, 'close'])->name('legacy.evaluations.close');
            });
            Route::delete('/evaluations/{evaluation}', [UserEvaluationController::class, 'destroy'])
                ->middleware('can:delete,evaluation')->name('legacy.evaluations.destroy');
        });

        /**EDB 09/10/26: Routes for user to administer theri own profile*/
        Route::middleware(CheckTokenForAnyScope::using('profile:write'))->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update'])
                ->middleware('can:update,user');
            Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])
                ->middleware('can:update,user');
            Route::post('/users/{user}/contacts', [ContactController::class, 'store'])
                ->middleware('can:update,user'); //EDB 10/06/26: the user's single primary contact
            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->middleware('can:delete,user');
            Route::delete('/users/{user}/force', [UserController::class, 'forceDestroy'])
                ->middleware('can:delete,user')->withTrashed();
        });
    });
