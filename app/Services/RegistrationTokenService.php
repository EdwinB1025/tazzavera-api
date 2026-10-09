<?php

namespace App\Services;

use App\Contracts\RegistrationTokenServiceContract;
use App\Models\Passport\Client;
use App\Models\User;
use App\Passport\RegistrationGrant;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Container;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * EDB 10/08/26: signs a newly registered user in (AUT R44).
 *
 * Tokens are issued on the front client, which keeps its PKCE and
 * refresh_token grants unchanged.
 */
class RegistrationTokenService implements RegistrationTokenServiceContract
{
    private AuthorizationServer $server;

    public function __construct(
        private Container $container
    ) {
        $this->server = $this->registrationAuthorizationServer();
    }

    public function issueFor(User $user): ?array
    {
        $client = Client::query()->front()->first();
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

    /**
     * EDB 10/09/26:
     * The authorization server is built inside this service and kept private: it only has RegistrationGrant enabled, 
     * as Passport does for personal access tokens; the server behind POST /oauth/token never knows this grant.
     */
    private function registrationAuthorizationServer(): AuthorizationServer
    {
        $privateKey = str_replace('\\n', "\n", config('passport.private_key') ?? '')
            ?: 'file://' . Passport::keyPath('oauth-private.key');

        $server = new AuthorizationServer(
            $this->container->make(ClientRepository::class),
            $this->container->make(AccessTokenRepository::class),
            $this->container->make(ScopeRepository::class),
            new CryptKey($privateKey, null, Passport::$validateKeyPermissions),
            Passport::tokenEncryptionKey($this->container->make('encrypter')),
        );
        $server->setDefaultScope(Passport::$defaultScope);
        $server->revokeRefreshTokens(Passport::$revokeRefreshTokenAfterUse);

        $grant = new RegistrationGrant();
        $grant->setRefreshTokenRepository($this->container->make(RefreshTokenRepository::class));
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        $server->enableGrantType($grant, Passport::tokensExpireIn());

        return $server;
    }
}
