<?php

namespace App\Passport;

use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Grant that signs a newly registered user in (AUT R44).
 *
 * Built as Passport's own PersonalAccessGrant: it is only enabled on the
 * authorization server of RegistrationTokenService, never on the server behind
 * POST /oauth/token, so it cannot be requested over HTTP. It issues an access
 * token and a refresh token for the given user on the given client (the
 * front's public PKCE client), without changing that client's grant types, so
 * the front refreshes the token with its usual refresh_token request.
 */
class RegistrationGrant extends AbstractGrant
{
    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        if (! $userIdentifier = $this->getRequestParameter('user_id', $request)) {
            throw OAuthServerException::invalidRequest('user_id');
        }

        if (! $clientId = $this->getRequestParameter('client_id', $request)) {
            throw OAuthServerException::invalidRequest('client_id');
        }

        // As PersonalAccessGrant: the client is read from the repository directly, since
        // getClientEntityOrFail() would require this grant in the client's grant types.
        if (! $client = $this->clientRepository->getClientEntity($clientId)) {
            throw OAuthServerException::invalidClient($request);
        }

        $scopes = $this->scopeRepository->finalizeScopes(
            $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope)),
            $this->getIdentifier(),
            $client,
            $userIdentifier
        );

        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
        $responseType->setAccessToken($accessToken);

        $refreshToken = $this->issueRefreshToken($accessToken);
        if ($refreshToken !== null) {
            $responseType->setRefreshToken($refreshToken);
        }

        return $responseType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'registration';
    }
}
