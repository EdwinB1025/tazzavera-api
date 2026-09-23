# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

This API uses **Laravel Passport (OAuth 2.0)**. Authenticated endpoints require a Bearer access token in the `Authorization` header. Two grant flows are supported:

**Authorization Code + PKCE** (recommended, for public/first-party clients such as SPAs and mobile apps that cannot safely store a client secret):
1. Generate a `code_verifier` (random string) and derive a `code_challenge` = BASE64URL(SHA256(`code_verifier`)).
2. Redirect the user to `GET /oauth/authorize` with `response_type=code`, your `client_id`, `redirect_uri`, `scope`, `state`, `code_challenge`, and `code_challenge_method=S256`.
3. After the user approves, exchange the returned `code` at `POST /oauth/token` with `grant_type=authorization_code`, the `code`, `redirect_uri`, `client_id`, and the original `code_verifier`.
4. The response contains `access_token`, `refresh_token`, and `expires_in`.

**Password grant** (for trusted first-party clients only):
- `POST /oauth/token` with `grant_type=password`, `client_id`, `client_secret`, the user's `username` and `password`, and the required `scope`.
- The response contains `access_token`, `refresh_token`, and `expires_in`.

Send the token as: `Authorization: Bearer {ACCESS_TOKEN}`.

**Scopes:** endpoints enforce `profile:read` and/or `profile:write`. Request the appropriate scope when obtaining your token, or the request will be rejected.
