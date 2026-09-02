# HTTP response codes — API reference

> HTTP codes are standard (they are not Laravel-specific; Laravel just uses them).
> This reference covers the ones relevant to a REST API, with guidance on when to use each.

---

## 2xx — Success

| Code | Name | When to use it |
|------|------|----------------|
| 200 | OK | Generic successful request (GET, PUT/PATCH returning the resource, actions that create nothing) |
| 201 | Created | A resource was **created** (registration POST, store) — return the created resource |
| 202 | Accepted | Accepted for **later** processing (queued jobs); not yet completed |
| 204 | No Content | Success **with no body** (typical DELETE, or an update that returns nothing) |

## 3xx — Redirection
*(Rarely used in JSON APIs; relevant in web apps with views)*

| Code | Name | When |
|------|------|------|
| 301 | Moved Permanently | Resource permanently moved |
| 302 | Found | Temporary redirect (web responses, not JSON API) |
| 304 | Not Modified | Cache: the resource has not changed since the last request |

## 4xx — Client error

| Code | Name | When to use it |
|------|------|----------------|
| 400 | Bad Request | Generic malformed request (invalid JSON, incoherent parameters) |
| 401 | Unauthorized | **Not authenticated** — missing or invalid token (Passport/Sanctum) |
| 403 | Forbidden | Authenticated but **not permitted** (authorization / policy / role fails) |
| 404 | Not Found | Resource does not exist (`findOrFail` failure → automatic in Laravel) |
| 405 | Method Not Allowed | HTTP method not allowed on that route (POST where only GET exists) |
| 409 | Conflict | State conflict (duplicate via race condition, stale version) |
| 422 | Unprocessable Entity | **Validation failed** — the most common case (Form Request → automatic) |
| 429 | Too Many Requests | Rate limiting (throttle middleware) |

## 5xx — Server error

| Code | Name | When |
|------|------|------|
| 500 | Internal Server Error | Unhandled exception, database error (`QueryException`) — automatic |
| 503 | Service Unavailable | App in maintenance (`php artisan down`), or a service is down |

---

## Automatic in Laravel (you don't set the code)

Laravel generates these on its own; you only customize the response format (JSON) via `withExceptions`:

| Situation | Automatic code |
|-----------|----------------|
| Form Request validation fails | 422 |
| Form Request `authorize()` returns false | 403 |
| `findOrFail` / `ModelNotFoundException` | 404 |
| Missing/invalid token (`auth:api`) | 401 |
| `QueryException` / unhandled exception | 500 |
| Wrong HTTP method on the route | 405 |
| Throttle / rate limit exceeded | 429 |

---

## Users

| Endpoint | Method | Code | Message / i18n key | Notes |
|----------|--------|------|--------------------|-------|
| `/register` | POST | 201 | `user.created` | creates user + assigns role |
| `/register` | POST | 422 | validation + `errors{}` | validation failed |
| `/register` | POST | 500 | `RoleAssignmentException` | role assignment failed |
| `/login` | POST | 200 | — | Fortify web session; `{"two_factor": false}`; no token |
| `/login` | POST | 401 | `Unauthenticated.` | invalid credentials |
| `/login` | POST | 422 | validation + `errors{}` | validation failed |
| `/oauth/token` | POST | 200 | — | returns `access_token` + `refresh_token` |
| `/oauth/token` | POST | 401 | `invalid_client` | client auth failed |
| `/oauth/token` | POST | 400 | `invalid_request` / `invalid_grant` | bad grant/verifier |
| `/logout` | POST | 200 | `auth.logged_out` | revokes ALL user tokens (access + refresh) |
| `/logout` | POST | 401 | `Unauthenticated.` | no/invalid token |
| `/user` | GET | 200 | — | authenticated user's own data (no id) |
| `/user` | GET | 401 | `Unauthenticated.` | no/invalid token |
| `/users/{user}` | PUT | 200 | `user.updated` | profile update; own + scope `profile:write` |
| `/users/{user}` | PUT | 401 | `Unauthenticated.` | no/invalid token |
| `/users/{user}` | PUT | 403 | `This action is unauthorized.` | not owner / missing scope |
| `/users/{user}` | PUT | 404 | model not found | user does not exist |
| `/users/{user}` | PUT | 422 | validation + `errors{}` | validation failed |
| `/users/{user}/password` | PUT | 200 | `user.password_updated` | own + scope `profile:write` |
| `/users/{user}/password` | PUT | 401 | `Unauthenticated.` | no/invalid token |
| `/users/{user}/password` | PUT | 403 | `This action is unauthorized.` | not owner / missing scope |
| `/users/{user}/password` | PUT | 422 | validation + `errors{}` | incl. `current_password` mismatch |
| `/users/{user}` | DELETE | 200 | `user.deactivated` | soft delete (recoverable); own + scope `profile:write` |
| `/users/{user}` | DELETE | 401 | `Unauthenticated.` | no/invalid token |
| `/users/{user}` | DELETE | 403 | `This action is unauthorized.` | not owner / missing scope |
| `/users/{user}` | DELETE | 404 | model not found | user does not exist |
| `/users/{user}/force` | DELETE | 200 | `user.deleted` | hard delete (cascade); own + scope `profile:write`; `withTrashed` |
| `/users/{user}/force` | DELETE | 401 | `Unauthenticated.` | no/invalid token |
| `/users/{user}/force` | DELETE | 403 | `This action is unauthorized.` | not owner / missing scope |
| `/users/{user}/force` | DELETE | 404 | model not found | user does not exist |

---

## Table of custom definitions per endpoint

> Fill in one row per API endpoint. "Success code" = the one you return in the `return`.
> "Possible errors" = the ones that may arise (automatic or thrown by you).
> "Message / key" = the translation key for the response message (success or error).

| Endpoint | Method | Success code | Possible errors | Message / i18n key | Notes |
|----------|--------|--------------|-----------------|--------------------|-------|
| `/register` | POST | 201 | 422 (validation), 500 (`RoleAssignmentException`) | `user.created` | creates user + assigns role |
| `/login` | POST | 200 | 401 (credentials), 422 (validation) | `auth.logged_in` | creates web session (Fortify, stateful); returns `{"two_factor": false}`; does NOT return token — auth step before `/oauth/authorize` |
| `/oauth/authorize` | GET | 302 | 401 (no session), 400/`unauthorized_client`, `invalid_client` | — | requires active web session + PKCE params; with `skipsAuthorization` returns `code` in `Location` |
| `/oauth/token` | POST | 200 | 400/`invalid_request`, 401/`invalid_client`, `invalid_grant` | — | exchanges `code` + `code_verifier` (or password grant) for `access_token` + `refresh_token` |
| `/logout` | POST | 200 | 401 | `auth.logged_out` | revokes ALL of the user's tokens (access + refresh) |
| `/user` | GET | 200 | 401 | — | authenticated user's own data (no id) |
| `/users/{user}` | PUT | 200 | 401, 403, 404, 422 | `user.updated` | profile update (name, surname, email); own + scope `profile:write` |
| `/users/{user}/password` | PUT | 200 | 401, 403, 404, 422 | `user.password_updated` | password change (current_password + confirmed); own + scope `profile:write` |
| `/users/{user}` | DELETE | 200 | 401, 403, 404 | `user.deactivated` | soft delete (deactivate account, recoverable via `restore`); own + scope `profile:write` |
| `/users/{user}/force` | DELETE | 200 | 401, 403, 404 | `user.deleted` | hard delete (permanent removal, cascade); own + scope `profile:write`; binding `withTrashed` |

---

## How the code is set in each case

- **Success with a resource:** `return (new UserResource($user))->response()->setStatusCode(201);`
- **Success with a resource + message:** `return (new UserResource($user))->additional(['message' => __('user.updated')])->response()->setStatusCode(200);`
- **Success with no body:** `return response()->noContent();` (204)
- **Success with a message only:** `return response()->json(['message' => __('user.deactivated')], 200);`
- **Business error (custom exception):** extend `HttpException` with the code in the constructor, or define it in the `render` of `withExceptions`.
- **Automatic errors:** you don't set the code; only the JSON format in `withExceptions`.