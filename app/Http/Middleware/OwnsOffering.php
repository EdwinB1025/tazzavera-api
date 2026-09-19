<?php

namespace App\Http\Middleware;

use App\Models\Offering;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class OwnsOffering
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $field = null): Response
    {
        $routeUlid = $request->route('offering');

        // EDB 09/16/26: if the route parameter offering is passed, it has the priority (single delete route, body is ignored).
        if ($routeUlid) {
            $ulids = [$routeUlid];
        } else {
            // EDB 09/16/26: server error if mass delete is requested, the middleware parameter is mandatory if not passed.
            abort_if($field === null, 500, "Middleware requires a field argument on mass routes");
            $ulids = (array) $request->input($field);
        }

        $offerings = Offering::whereIn('ulid', $ulids)->get();

        foreach ($offerings as $offering) {
            if (Gate::denies('delete', $offering)) {
                abort(403, __('offerings.not_owned'));
            }
        }

        return $next($request);
    }
}
