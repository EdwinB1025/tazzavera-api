<?php

/**EDB 10/06/26: CORS for the routes the front calls with fetch (config/cors.php) */

test('cors_preflight_allows_the_front_origin_on_token_endpoint', function () {
    $front = config('app.front_url');

    $this->withHeaders([
        'Origin' => $front,
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type',
    ])->options('/oauth/token')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', $front);
});

test('cors_allows_the_front_origin_on_api_routes', function ($path) {
    $front = config('app.front_url');

    $this->withHeaders(['Origin' => $front])
        ->getJson($path)
        ->assertHeader('Access-Control-Allow-Origin', $front);
})->with([
    'offerings'   => ['/offerings'],
    'evaluations' => ['/evaluations'],
    'taxonomies'  => ['/taxonomies'],
    'roasteries'  => ['/roasteries'],
    'coffees'     => ['/coffees'],
]);

test('cors_never_echoes_other_origins', function () {
    //With a single allowed origin the middleware always answers that origin, so a browser on another origin is blocked
    $response = $this->withHeaders([
        'Origin' => 'http://evil.test',
        'Access-Control-Request-Method' => 'POST',
    ])->options('/oauth/token');

    expect($response->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('http://evil.test')
        ->not->toBe('*');
});
