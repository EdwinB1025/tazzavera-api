<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Foundation\Http\FormRequest;
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

        /** Enable Password Grant for Passport */
        Passport::enablePasswordGrant();

        /** Tokens lifecycle */
        Passport::tokensExpireIn(CarbonInterval::days(15));
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::months(6));
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
