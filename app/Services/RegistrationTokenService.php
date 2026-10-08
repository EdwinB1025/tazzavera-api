<?php

namespace App\Services;

use App\Contracts\RegistrationTokenServiceContract;
use App\Models\Passport\Client;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * EDB 10/08/26: signs a newly registered user in (AUT R44).
 *
 * The authorization server injected here is private to this service (bound in
 * AppServiceProvider) and only has RegistrationGrant enabled, as Passport does
 * for personal access tokens; the server behind POST /oauth/token never knows
 * this grant. Tokens are issued on the front client, which keeps its PKCE and
 * refresh_token grants unchanged.
 */
class RegistrationTokenService implements RegistrationTokenServiceContract
{
    public function __construct(
        protected AuthorizationServer $server,
    ) {
    }

    public function issueFor(User $user): ?array
    {
        $client = Client::front()->first();
        if (! $client) {
            return null;
        }

        $request = (new ServerRequest('POST', config('app.url')))->withParsedBody([
            'grant_type' => 'registration',
            'client_id' => $client->getKey(),
            'user_id' => $user->getAuthIdentifier(),
        ]);

        try {
            $response = $this->server->respondToAccessTokenRequest($request, new Response());
        } catch (OAuthServerException) {
            return null;
        }

        return json_decode((string) $response->getBody(), true);
    }
}
