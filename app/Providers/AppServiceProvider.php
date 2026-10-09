<?php

namespace App\Providers;

use App\Contracts\EvaluationServiceContract;
use App\Models\Passport\Client;
use App\Models\User;
use App\Services\EvaluationService;
use App\Session\WebGuardDatabaseSessionHandler;
use Carbon\CarbonInterval;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
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
        $this->app->bind(EvaluationServiceContract::class, EvaluationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->passwordDefaults();
        $this->webGuardSessions();
        $this->resetPasswordMail();
        //EDB 10/09/26: enabling verify email.
        $this->verifyEmailMail();
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
            'profile:read' => 'Retreive data to performed actions'
        ]);

        /** EDB 09/15/26: setting default scope so tokens without an explicit scope
         *  get profile:read, * to be invalidated through the middleware
         */

        Passport::defaultScopes(['profile:read']);

        /** Defining relations aliases for polomirphic relations, everytime a contactabe model is adde need to be reflected here */

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

    /**
     * The database session driver records the web guard's user in user_id
     * (the default guard is api), so the API logout can end the web sessions.
     */
    private function webGuardSessions(): void
    {
        Session::extend('database', function ($app) {
            $connection = $app['db']->connection($app['config']->get('session.connection'));

            return new WebGuardDatabaseSessionHandler(
                $connection,
                $app['config']->get('session.table'),
                $app['config']->get('session.lifetime'),
                $app
            );
        });
    }

    /**
     * Password reset e-mail: texts from lang/{locale}/notifications.php in the
     * locale of the request that asked for it (set by SetWebThemeAndLocale),
     * and a link that keeps that locale and the theme on the reset page.
     */
    private function resetPasswordMail(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
                'lang' => App::getLocale(),
                'theme' => session('ui.theme'),
            ], false));
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = call_user_func(ResetPassword::$createUrlCallback, $notifiable, $token);
            $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

            return (new MailMessage)
                ->subject(__('notifications.reset_password.subject'))
                ->line(__('notifications.reset_password.intro'))
                ->action(__('notifications.reset_password.action'), $url)
                ->line(__('notifications.reset_password.expire', ['count' => $expire]))
                ->line(__('notifications.reset_password.outro'));
        });
    }

    /**
     * Verification e-mail: texts from lang/{locale}/notifications.php in the
     * locale of the request, and a signed link that keeps locale and theme.
     */
    private function verifyEmailMail(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                    'lang' => App::getLocale(),
                    'theme' => session('ui.theme'),
                ]
            );
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject(__('notifications.verify_email.subject'))
                ->line(__('notifications.verify_email.intro'))
                ->action(__('notifications.verify_email.action'), $url)
                ->line(__('notifications.verify_email.outro'));
        });
    }
}
