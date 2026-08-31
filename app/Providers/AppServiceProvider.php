<?php

namespace App\Providers;

use App\Models\Passport\Client;
use Carbon\CarbonInterval;
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
        FormRequest::failOnUnknownFields();
        Blade::anonymousComponentNamespace('layouts', 'layouts');

        /** Enable Password Grant for Passport */
        Passport::enablePasswordGrant();

        Passport::useClientModel(Client::class);
        Passport::authorizationView('auth.oauth.authorize');
        /** Tokens lifecycle */
        Passport::tokensExpireIn(CarbonInterval::minutes(30));
        Passport::refreshTokensExpireIn(CarbonInterval::minutes(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::day(6));
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
