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
|----------|--------|-----------------|--------------------|-------|
| `/register` | POST | 201 | `User created` | creates user + assigns role |
| `/register` | POST | 201 | `User created` | creates user + assigns role |
| | | | | | |
| | | | | | |
| | | | | | |

---

## Table of custom definitions per endpoint

> Fill in one row per API endpoint. "Success code" = the one you return in the `return`.
> "Possible errors" = the ones that may arise (automatic or thrown by you).
> "Message / key" = the translation key for the response message (success or error).

| Endpoint | Method | Success code | Possible errors | Message / i18n key | Notes |
|----------|--------|--------------|-----------------|--------------------|-------|
| `/register` | POST | 201 | 422 (validation) | `users.created` | creates user + assigns role |
| `/login` | POST | 200 | 401 (credentials), 422 (validation) | `auth.logged_in` | creates web session (Fortify, stateful); returns `{"two_factor": false}`; does NOT return token — auth step before `/oauth/authorize` |
| `/oauth/authorize` | GET | 302 | 401 (no session), 400/`unauthorized_client`, `invalid_client` | — | requires active web session + PKCE params; with `skipsAuthorization` returns `code` in `Location` |
| `/oauth/token` | POST | 200 | 400/`invalid_request`, 401/`invalid_client`, `invalid_grant` | — | exchanges `code` + `code_verifier` (or password grant) for `access_token` + `refresh_token` |
| `/users` | GET | 200 | 401 | — | list (protected) |
| `/users/{id}` | GET | 200 | 401, 404 | — | detail |
| `/users/{id}` | PUT/PATCH | 200 | 401, 403, 404, 422 | `users.updated` | update |
| `/users/{id}` | DELETE | 204 | 401, 403, 404 | `users.deleted` | no body |
| | | | | | |
| | | | | | |
| | | | | | |

---

## How the code is set in each case

- **Success with a resource:** `return (new UserResource($user))->response()->setStatusCode(201);`
- **Success with no body:** `return response()->noContent();` (204)
- **Success with a message:** `return response()->json(['message' => __('key'), 'data' => ...], 201);`
- **Business error (custom exception):** extend `HttpException` with the code in the constructor, or define it in the `render` of `withExceptions`.
- **Automatic errors:** you don't set the code; only the JSON format in `withExceptions`.