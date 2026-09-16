<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectWildcardScope
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**Only applies to authorize where scope parameter lives */
        if (! $request->is('oauth/authorize')) {
            return $next($request);
        }

        $scopes = explode(' ', $request->query('scope', ''));
        if (in_array('*', $scopes) && $request->is('oauth/authorize')) {
            return abort(400, 'Invalid Scope');
        }

        return $next($request);
    }
}
