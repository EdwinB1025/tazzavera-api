<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Http\Responses\PasswordResetResponse as FortifyPasswordResetResponse;

class PasswordResetResponse extends FortifyPasswordResetResponse
{
    /**
     * Redirect to the front-end after a successful reset: the web login only
     * accepts requests whose url.intended points to oauth/authorize.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => trans($this->status)], 200)
            : redirect()->away(config('app.front_url'));
    }
}
