<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /**EDB 10/06/26: the API routes live at the root (apiPrefix ''), so the default 'api/*' never matched. These are the routes the front calls with fetch (contracts/openapi.yaml); oauth/authorize, login and user/security are reached by navigation, not fetch. */
    'paths' => [
        'oauth/token',           // token exchange and refresh (add-auth)
        'logout',
        'register',
        'user', 'user/*',        // profile and /user/evaluations...
        'users/*',               // /users/{ulid}, /password, /force, /evaluations
        'evaluations', 'evaluations/*',
        'offerings', 'offerings/*',
        'locations',
        'coffeeInventory',
        'coffees', 'roasteries', // comboboxes with ?name= from the browser
        'taxonomies',
    ],

    'allowed_methods' => ['*'],

    /**EDB 10/06/26: only the front's origin; it must match the browser URL exactly (localhost, not 127.0.0.1) */
    'allowed_origins' => [env('FRONT_URL', 'http://localhost:3000')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // EDB 10/06/26: the front sends a Bearer token, no cookies

];
