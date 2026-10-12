<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

class RequireHintedUser
{
    /**
     * Binds the web session to the user the client expects: when the client
     * sends `login_hint` (the ULID of that user), only that user goes on.
     *
     * - oauth/authorize (step-up): it acts on the pass that follows the forced
     *   login (Passport keeps the promptedForLogin flag until then). Another
     *   user is signed out and the client receives `access_denied` on its
     *   registered redirect URI, so the wrong user never gets a code.
     * - The security and email verification pages: another user's web session
     *   is signed out and the browser comes back to the same URL, so auth:web
     *   sends it to the login page with that URL as the intended destination.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        /**Only applies to authorize where login_hint lives */
        if ($request->is('oauth/authorize')) {
            return $this->stepUp($request, $next);
        }

        //EDB 10/12/26: the client opens these pages with login_hint; the e-mail link may come without it
        if ($request->is('user/security', 'email/verify', 'email/verify/*')) {
            return $this->webPage($request, $next);
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    private function stepUp(Request $request, Closure $next): Response
    {
        $hint = (string) $request->query('login_hint', '');
        $user = $request->user('web');
        if ($hint === '' || $user === null || ! $request->session()->get('promptedForLogin', false)
            || $user->ulid === $hint) {
            return $next($request);
        }

        $this->signOut($request);

        $client = Passport::client()->newQuery()->find($request->query('client_id'));
        $redirect = (string) $request->query('redirect_uri', '');
        if ($client === null || ! in_array($redirect, $client->redirect_uris, true)) {
            abort(403, __('auth.step_up_other_user'));
        }

        $query = http_build_query(array_filter([
            'error' => 'access_denied',
            'error_description' => __('auth.step_up_other_user'),
            'state' => $request->query('state'),
        ]));

        return redirect()->away($redirect . (str_contains($redirect, '?') ? '&' : '?') . $query);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    private function webPage(Request $request, Closure $next): Response
    {
        $hint = (string) $request->query('login_hint', '');
        $user = $request->user('web');
        if ($hint === '' || $user === null || $user->ulid === $hint) {
            return $next($request);
        }

        $this->signOut($request);

        /**The raw URI keeps the query as sent (fullUrl() sorts it and would break a signed link) */
        return redirect()->to($request->getSchemeAndHttpHost() . $request->getRequestUri());
    }

    private function signOut(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
