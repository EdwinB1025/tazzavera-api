<?php

namespace App\Providers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password as RulesPassword;

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
    }

    private function passwordDefaults(): void
    {
        RulesPassword::defaults(
            function () {
                return RulesPassword::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised();
            }
        );
    }
}
