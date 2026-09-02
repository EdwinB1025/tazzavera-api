# Tazavera — REST Endpoint Design

Roles: `specialist`, `coffeeshop` (separate `roles` table; there is no `admin` role, each user manages their own profile).

**Specialist:** authenticated professional cupper/taster. Their role is to create, edit and close evaluations on existing offerings — they provide the expert judgment that feeds an offering's consensus. They do not create or edit offerings.

**Coffeeshop:** authenticated coffee shop, owner of its own offerings. Creates and edits its offerings (associating a coffee and a location), and creates/edits the provisional technical evaluation (at most one per offering) required to publish it. Does not close evaluations or evaluate other shops' offerings.

**User (public):** unauthenticated visitor (or authenticated without an elevated role) who only reads information — accesses the public index/show endpoints for offerings and evaluations. Does not create, edit, close or delete anything; read-only consumption.

## Offerings

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/offerings` | — | Public | Query: `name`, `city`, `origin`, `process`, `score`, `main_tastes[]`, `specific_tastes[]` |
| GET | `/offerings/{id}` | — | Public | — |
| POST | `/offerings` | coffeeshop | Authenticated | Body: `location_id`, `coffee_id` |
| PUT | `/offerings/{id}` | coffeeshop (owner) | Authenticated | Body: `location_id`, `coffee_id` |
| DELETE | `/offerings/{id}` | coffeeshop (owner) | Authenticated | — |

`location_id` and `coffee_id` reference existing records (they are not created nested in the same request). The pair `(location_id, coffee_id)` is unique.

## Evaluations

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/evaluations` | — | Public | Query: `id` (evaluator), `coffee_id`, `city`, `location_id`, `process`, `score`, `status` |
| GET | `/evaluations/{id}` | — | Public | — |
| POST | `/evaluations` | specialist, coffeeshop | Authenticated | Body: `offering_id`, `extraction_method`, `status`, `descriptive`, `affective`, `note` |
| PUT | `/evaluations/{id}` | specialist (own, includes closing with `status: closed`), coffeeshop (own, max. 1 per offering) | Authenticated | Body: `extraction_method`, `status`, `descriptive`, `affective`, `note` |
| DELETE | `/evaluations/{id}` | specialist (own), coffeeshop (own) | Authenticated | — |

`evaluator_id` and `evaluator_role` are **not** request inputs: the backend derives them from the authenticated user (`evaluator_id` = logged-in user; `evaluator_role` = that user's role). Closing an evaluation has no dedicated route — it is the same `PATCH` changing `status` to `closed`.

## Users

| Method | Endpoint | Authorization | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| POST | `/register` | — | Public | Body: `role`, `name`, `surname`, `email`, `password`, `password_confirmation` |
| POST | `/login` | — | Public (web session) | Body: `email`, `password` — creates web session (Fortify, stateful guard); response `{"two_factor": false}`. Authenticate step before `/oauth/authorize`, does NOT issue a token |
| GET | `/oauth/authorize` | — | Requires active web session | Query: `client_id`, `redirect_uri`, `response_type=code`, `scope`, `state`, `code_challenge`, `code_challenge_method=S256`. With `skipsAuthorization` (first-party) returns `302` with the `code` in the `Location` header |
| POST | `/oauth/token` | — | Public (PKCE client) | Body (form-urlencoded): `grant_type=authorization_code`, `client_id`, `redirect_uri`, `code`, `code_verifier`. No `client_secret`. Returns `access_token` + `refresh_token` |
| POST | `/oauth/token` | — | Public (confidential client) | Body (form-urlencoded): `grant_type=password`, `client_id`, `client_secret`, `username` (email), `password`, `scope`. Returns `access_token` + `refresh_token` |
| POST | `/logout` | Authenticated | `auth:api` | — Revokes the request token (access + refresh) |
| GET | `/user` | Authenticated | `auth:api` | — No id. Returns the authenticated user's data (the front-end gets its id here) |
| PUT | `/users/{user}` | Own (policy `update`) + scope `profile:write` | `auth:api` | Body: `name`, `surname`, `email` (all `sometimes`) |
| PUT | `/users/{user}/password` | Own (policy `update`) + scope `profile:write` | `auth:api` | Body: `current_password`, `password`, `password_confirmation` |
| DELETE | `/users/{user}` | Own (policy `delete`) + scope `profile:write` | `auth:api` | — Soft delete (deactivate account, recoverable via `restore`). Sets `deleted_at`, keeps profile and related data |
| DELETE | `/users/{user}/force` | Own (policy `delete`) + scope `profile:write` | `auth:api` | — Hard delete (permanent removal). `forceDelete`; removes the row and cascades to related data (`ON DELETE CASCADE`). Binding uses `withTrashed` |

Login is a three-step PKCE flow: `POST /login` (creates the Fortify web session) → `GET /oauth/authorize` (with that session, returns the `code`) → `POST /oauth/token` (exchanges `code` + `code_verifier` for the `access_token`). The `access_token` authenticates `auth:api` routes via `Authorization: Bearer`. The `code_verifier` belongs to the client and only travels in the token step; only its hash (`code_challenge`) is sent to `/oauth/authorize`.

**Step-up (scope `profile:write`):** sensitive actions (update profile, change password, deactivate and delete account) require a token carrying the `profile:write` scope, verified with `CheckTokenForAnyScope::using('profile:write')` (Passport 13). That token is obtained through the same authorization flow by requesting `scope=profile:write` at `/oauth/authorize` — a single login mechanism, re-authenticating to elevate the token. A token without that scope receives a `403`.

**Pending:**
- `GET /users/{id}` to fetch *other* users (third-party profiles): role allowed and exposed fields not yet defined.
- Anonymization / policy for generated data (evaluations) on hard delete.
- **User contact information as a separate entity:** define the model (likely relational, not JSON), its cardinality (one or several contacts per user?), which fields it holds, and its endpoints (own CRUD or nested under the user).