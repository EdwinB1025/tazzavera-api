<?php

namespace App\Http\Middleware;

use App\Http\Requests\StoreOfferingRequest;
use App\Models\Location;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\HttpFoundation\Response;

class OwnLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $field): Response
    {

        $ulids = (array) $request->input($field, []);
        $locations = Location::whereIn('ulid', $ulids)->get();

        /** EDB 09/14/26: evaluates the locaiton policy for each of the location passed */
        foreach ($locations as $location) {
            if (Gate::denies('ownLocation', $location)) {
                abort(403, __('locations.location_not_owned', ['ulid' => $location->ulid]));
            }
        }

        return $next($request);
    }
}
