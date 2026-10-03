<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetWebThemeAndLocale
{
    public const THEMES = ['traditional', 'dark'];

    public const LOCALES = ['en', 'es', 'ca'];

    public const DEFAULT_THEME = 'traditional';

    public const DEFAULT_LOCALE = 'es';

    /**
     * Theme and language of the web views (OAuth login, 2FA, password reset,
     * /user/security). The client sends ?theme= and ?lang= on oauth/authorize;
     * valid values are kept in the web session so they survive the redirects
     * (authorize → login → 2FA → back). An unsupported value is ignored.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        $theme = $this->pick($request->query('theme'), self::THEMES)
            ?? $this->pick($session->get('ui.theme'), self::THEMES)
            ?? self::DEFAULT_THEME;

        $locale = $this->pick($request->query('lang'), self::LOCALES)
            ?? $this->pick($session->get('ui.lang'), self::LOCALES)
            ?? self::DEFAULT_LOCALE;

        App::setLocale($locale);
        View::share('theme', $theme);
        $this->remember($session, $theme, $locale);

        $response = $next($request);

        // Passport's prompt=login invalidates the session within this same
        // request; storing the values again keeps them for the login page.
        $this->remember($request->session(), $theme, $locale);

        return $response;
    }

    /** Returns the value when it is one of the allowed ones. */
    private function pick(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private function remember(Session $session, string $theme, string $locale): void
    {
        $session->put('ui.theme', $theme);
        $session->put('ui.lang', $locale);
    }
}
