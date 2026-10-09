<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

class RequireHintedUserForStepUp
{
    /**
     * Step-up bound to the expected user: when the client sends `login_hint`
     * (the ULID of the user it expects), the code is issued only to that user.
     *
     * It acts on the pass that follows the forced login (Passport keeps the
     * promptedForLogin flag until then). Another user is signed out and the
     * client receives `access_denied` on its registered redirect URI, so the
     * wrong user never gets a code.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**Only applies to authorize where login_hint lives */
        if (! $request->isMethod('GET') || ! $request->is('oauth/authorize')) {
            return $next($request);
        }

        $hint = (string) $request->query('login_hint', '');
        $user = $request->user('web');
        if ($hint === '' || $user === null || ! $request->session()->get('promptedForLogin', false)
            || $user->ulid === $hint) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
}
