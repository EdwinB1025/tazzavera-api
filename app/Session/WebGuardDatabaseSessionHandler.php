<?php

namespace App\Session;

use Illuminate\Session\DatabaseSessionHandler;

/**
 * Database session handler that records the user of the web guard in the
 * user_id column.
 *
 * Laravel fills user_id with the default guard, which here is api (token
 * based, no session), so web sessions kept user_id null. Sessions are only
 * used by the web guard (OAuth login, 2FA, /user/security); recording its
 * user lets the API logout end every web session of the user.
 */
class WebGuardDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Get the currently authenticated user's ID on the web guard.
     *
     * @return mixed
     */
    protected function userId()
    {
        return $this->container->make('auth')->guard('web')->id();
    }
}
