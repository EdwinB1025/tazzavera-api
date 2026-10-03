<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLoginForWriteScope
{
    /**
     * Step-up authentication: an authorization request that asks for the
     * profile:write scope always makes the user sign in again, whether or not
     * the client sent prompt=login.
     *
     * It relies on Passport's own prompt=login handling: the first pass logs
     * the user out and flags the new session with promptedForLogin; after the
     * login the flag lets the request through once and Passport clears it, so
     * the user is asked only once per authorization.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**Only applies to authorize where scope parameter lives */
        if (! $request->isMethod('GET') || ! $request->is('oauth/authorize')) {
            return $next($request);
        }

        $scopes = explode(' ', $request->query('scope', ''));
        if (! in_array('profile:write', $scopes)) {
            return $next($request);
        }

        // prompt=none would make Passport ignore every other prompt value, so
        // it is dropped: a write scope cannot be granted without the login.
        $prompt = collect(explode(' ', $request->query('prompt', '')))
            ->map(trim(...))
            ->filter()
            ->reject(fn (string $value) => $value === 'none')
            ->push('login')
            ->unique()
            ->implode(' ');

        $request->query->set('prompt', $prompt);

        return $next($request);
    }
}
