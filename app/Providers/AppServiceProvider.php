<?php

namespace App\Providers;

use App\Models\Passport\Client;
use App\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->passwordDefaults();
        //FormRequest::failOnUnknownFields(); EDB 09/01/2026: This fields fails request with _token and _method fields embeded, discarded, not usefull.

        Blade::anonymousComponentNamespace('layouts', 'layouts');

        /** Enable Password Grant for Passport */
        Passport::enablePasswordGrant();

        /** Introducing custom client model to allos for skip authorization of firstparty */
        Passport::useClientModel(Client::class);

        /** Registering authorization view even if not used in first paty clients */
        Passport::authorizationView('auth.oauth.authorize');

        /** Tokens lifecycle */
        Passport::tokensExpireIn(CarbonInterval::minutes(30));
        Passport::refreshTokensExpireIn(CarbonInterval::hour(1));
        Passport::personalAccessTokensExpireIn(CarbonInterval::day(6));

        /** Registering scopes to enable the authorization flow in front-end*/
        Passport::tokensCan([
            'profile:write' => 'Modify or delete profile',
        ]);

        /** Defining relations aliases for polomirphic relations */

        Relation::enforceMorphMap(
            [
                'user' => User::class,
                'location' => \App\Models\Location::class,
                'roastery' => \App\Models\Roastery::class,
            ]
        );
    }

    private function passwordDefaults(): void
    {
        RulesPassword::defaults(
            function () {
                $rule = RulesPassword::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers();
                return app()->isProduction() ? $rule->uncompromised() : $rule;
            }
        );
    }
}
