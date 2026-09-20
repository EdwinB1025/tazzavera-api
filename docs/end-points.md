# Tazavera — REST Endpoint Design

Roles: `specialist`, `coffeeshop` (separate `roles` table; there is no `admin` role, each user manages their own profile).

> **API naming convention:** everything that enters or leaves through the API (request body, query params, JSON responses) uses **camelCase**. DB column names are snake_case (see `entidades_relacionales.md`) and are resolved internally. **Exception:** OAuth2/PKCE parameters (`grant_type`, `client_id`, `redirect_uri`, `code_verifier`, `code_challenge`, `client_secret`, etc.) stay snake_case because they are defined by the OAuth standard, not by this API.

> **Response shapes:** JSON examples below are derived from the actual Resources. Notes worth keeping in mind:
> - Decimal-cast fields (`cuppingAvg`, the `*Avg` axes, `concordance`, `latitud`, `longitud`) serialize as **strings** (MySQL DECIMAL → string in PHP). The front parses them.
> - `whenNotNull` fields are **omitted from the JSON when null** (e.g. a provisional offering with no consensus won't include the `*Avg`/`concordance` keys at all).
> - `whenLoaded` relations only appear when the controller eager-loaded them; absent otherwise.
> - Resource collections are wrapped in `data`; `->additional([...])` merges keys (like `message`) at the root.

**Specialist:** authenticated professional cupper/taster. Their role is to create, edit and close evaluations on existing offerings — they provide the expert judgment that feeds an offering's consensus. They do not create or edit offerings.

**Coffeeshop:** authenticated coffee shop, owner of its own offerings. Creates and edits its offerings (associating a coffee inventory lot and one or more of its own locations), and creates/edits the provisional technical evaluation (at most one per offering) required to publish it. Does not close evaluations or evaluate other shops' offerings.

**User (public):** unauthenticated visitor (or authenticated without an elevated role) who only reads information — accesses the public index/show endpoints for offerings and evaluations. Does not create, edit, close or delete anything; read-only consumption.

## Offerings

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/offerings` | — | Public | Query:  `evaluationCount`, `defectiveCount`, `cuppingAvgFrom`, `cuppingAvgTo`, `fragranceFrom`, `aromaFrom`, `flavorFrom`, `aftertasteFrom`, `acidityFrom`, `sweetnessFrom`, `mouthfeelFrom`, `overallFrom`, `fragranceTo`, `aromaTo`, `flavorTo`, `aftertasteTo`, `acidityTo`, `sweetnessTo`, `mouthfeelTo`, `overallTo`, `cataRef`, `fragranceCata`, `aromaCata`, `flavorCata`, `aftertasteCata`, `mouthfeelCata`, `coffeeshopUlid`, `locationUlid`, `city`, `coffeeName`, `originCountry`, `originRegion`, `process`, `producer` |
| GET | `/offerings/{offeringId}` | — | Public | — |
| POST | `/offerings` | coffeeshop | Authenticated (`profile:write`) | Body: `coffeeInventoryId`, `locations: []` (batch — one offering per location, all for the same inventory lot) |
| DELETE | `/offerings/{offeringId}` | coffeeshop (owner) | Authenticated (`profile:write`) | — |
| DELETE | `/offerings` | coffeeshop (owner) | Authenticated (`profile:write`) | Body: `offerings: []` (array of ulids to delete, `max:50`) |

**Batch creation (`POST /offerings`):** the coffeeshop selects one inventory lot and one or more of its own locations; the endpoint creates one offering per location, all pointing to the same inventory lot. The pair `(location, coffeeInventory)` is UNIQUE.

- **Ownership (hard 403):** every location in `locations` is validated first against the authenticated coffeeshop (middleware `owns.location:locations` + `LocationPolicy`). If any location is not owned by the caller → `403`, nothing is created. This is authorization, not a warning.
- **Response `201`:** a collection of the created offerings (`OfferingResource`) plus a `message`.

```json
{
  "data": [
    {
      "ulid": "01J…",
      "verificationStatus": "provisional",
      "location": { "…": "LocationResource (whenLoaded)" },
      "coffeeInventory": { "…": "CoffeeInventoryResource (whenLoaded)" }
    }
  ],
  "message": "Offering(s) created."
}
```

> A freshly created offering is provisional: it has no consensus yet, so `evaluationCount`, `defectiveEvaluationCount`, the `*Avg` axes and `concordance` are null and therefore **omitted** (`whenNotNull`). `sensoryTaxonomy` is empty/absent until `updateConsensus()` runs.

`coffeeInventoryId` and `locations` reference existing records by their public ulid; the backend resolves each ulid to its model (a non-existent ulid → 404/invalid; an existing one owned by another coffeeshop → 403). The DB UNIQUE and FKs operate on internal ids; the ulid is the public API layer only.

**Deletion is a hard delete.** `Offering` does **not** use the `SoftDeletes` trait, so both `DELETE` endpoints remove the row permanently. This is deliberate: the UNIQUE `(location, coffeeInventory)` pair is freed immediately, which is what makes the DELETE + POST-batch pattern work (re-creating the same pair right after deleting it). There is no soft-delete/restore for offerings — unlike users, offerings carry no data worth preserving after removal (the derived consensus averages recompute from the surviving evaluations).

**Single delete (`DELETE /offerings/{offeringId}`):** ownership verified by middleware `owns.offering` + `OfferingPolicy::delete` (which checks `user->id === offering->location->user_id`). The model is resolved by route-model binding on the ulid, so the policy receives the loaded model directly.

**Batch delete (`DELETE /offerings`):** the coffeeshop sends an array of offering ulids in the body to delete several at once.

- **No policy-by-binding.** Unlike the single delete, there is no `{offering}` in the route, so nothing is resolved by binding and `can:delete,offering` cannot apply. Authorization is done inside the ownership middleware (`owns.offering:offerings`), which reads the ulids from the body, loads the offerings and verifies each one against the authenticated user via the `delete` gate.
- **Rejection is total (hard 403).** If any ulid in the list belongs to an offering the caller does not own, the request is rejected with `403` and **nothing is deleted** — the whole operation fails, it does not delete the owned ones and skip the rest. A foreign ulid in the list is treated as a client bug, made visible rather than silently partially applied.
- **Validation (`MassDeleteOfferingRequest`):** `offerings` is `required|array|max:50`; each element is `required|string|exists:offerings,ulid`. A non-existent ulid is rejected with `422` before ownership runs. The `max:50` is a sanity cap on payload size (the UI never selects more at once), not a performance limit — the delete itself is a single `WHERE ulid IN (...)` query.

### `OfferingResource` (GET index/show — full shape)

```json
{
  "ulid": "01J…",
  "evaluationCount": 12,
  "defectiveEvaluationCount": 1,
  "cuppingAvg": "84.25",
  "fragranceAffectiveAvg": "7.2",
  "aromaAffectiveAvg": "7.8",
  "flavorAffectiveAvg": "7.5",
  "aftertasteAffectiveAvg": "7.1",
  "acidityAffectiveAvg": "7.4",
  "sweetnessAffectiveAvg": "7.6",
  "mouthfeelAffectiveAvg": "7.0",
  "overallAffectiveAvg": "7.9",
  "concordance": "0.812",
  "verificationStatus": "verified",
  "location": { "…": "LocationResource" },
  "coffeeInventory": { "…": "CoffeeInventoryResource" },
  "sensoryTaxonomy": [ { "…": "OfferingTasteResource" } ]
}
```

## Coffee Inventory

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/coffeeInventory` | coffeeshop | Authenticated | Query: `coffeeName`, `originCountry`, `originRegion`, `process`, `producer`, `city` |

Feeds the inventory selector when a coffeeshop creates an offering. Returns the available roast lots, each with its `roastery` and `coffee` nested as JSON (`CoffeeInventoryResource` embedding `RoasteryResource` + `CoffeeResource`). No individual inventory-lot profile endpoint for now (`/{inventoryUlid}` not exposed) — the list is only for offering creation. Closed to authenticated coffeeshops for now.

Filters operate on the nested café (`whereHas('coffee', …)`) since `coffeeName`/`originCountry`/`originRegion`/`process`/`producer` are `coffees` columns, not inventory columns. **`city`** filters by the roastery's contact (`whereHas('roastery.contacts', …)`).

### `CoffeeInventoryResource`

```json
{
  "data": [
    {
      "ulid": "01J…",
      "roastLot": "L-2026-014",
      "productionDate": "2026-09-19",
      "coffee": {
        "ulid": "01J…",
        "name": "…",
        "roastLevel": "medium",
        "process": "washed",
        "variety": "…",
        "producer": "…",
        "country": "…",
        "region": "…",
        "altitude": 1800,
        "lot": "…",
        "certifications": [ 
                            { "code": "organic", 
                              "description": "…" } 
                          ]
      },
      "roastery": { "ulid": "01J…", 
                    "name": "…", 
                    "description": "…" }
    }
  ]
}
```

> `certifications` in `CoffeeResource` lists the **certification type** (`code` + `description`) only — it does not expose the pivot's `issuedAt`/`expiresAt`. Pending: decide whether the response should carry those dates.

## Locations

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/locations` | coffeeshop | `auth:api` | — Returns the authenticated coffeeshop's own locations (query scoped to `user()->locations`) |

Two layers: role middleware (only coffeeshops enter the endpoint) + query scoping (only the owner's locations are returned — ownership lives in the query, not a policy). No ulid in the route: the "whose" comes from the token. Query-parameter filters to be added later.

### `LocationResource`

```json
{
  "data": [
    {
      "ulid": "01J…",
      "name": "…",
      "description": "…",
      "latitud": "41.38790000",
      "longitud": "2.16990000",
      "contacts": [
        {
          "ulid": "01J…",
          "isPrimary": true,
          "phone": "…",
          "email": "…",
          "web": "…",
          "social": "…",
          "address": "…",
          "country": "…",
          "city": "…",
          "postalCode": "…"
        }
      ]
    }
  ]
}
```

## Taxonomy

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/taxonomies` | — | Public | — Full olfactory taxonomy tree |

Returns the complete `olfactory_taxonomies` tree nested (3 levels: each root with its `children`, and each child with its `children` / grandchildren). Feeds the cata descriptor wheel in the evaluation form. Reference data — cacheable aggressively.

### `TaxonomyResource` (recursive)

```json
{
  "data": [
    {
      "level": 0,
      "nameEn": "Floral",
      "nameEs": "Floral",
      "descriptionEn": "…",
      "descriptionEs": "…",
      "color": "#C match",
      "categories": { "aromatics": true, 
                      "mainTastes": false, 
                      "defects": false, 
                      "mouthfeel": false 
                    },
      "children": [
        { "level": 1, 
          "nameEn": "…", 
          "children": [ 
                        { "level": 2, 
                          "…": "…", 
                          "children": [] } 
                      ] 
        }
      ]
    }
  ]
}
```

> `TaxonomyResource` does **not** expose `ulid`. Note that cata references in evaluations/offerings point to taxonomy nodes by ulid (`ref`); if the front needs to match a cata `ref` against this tree to render it, the tree currently offers no ulid to match on. Open decision: add `ulid` to `TaxonomyResource`, or have the front match by another key.

## Evaluations

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/evaluations` | — | Public | Query: `evaluatorId`, `coffeeId`, `city`, `locationId`, `process`, `score`, `status` *(not built yet)* |
| GET | `/evaluations/{evaluationId}` | — | Public | — *(not built yet)* |
| POST | `/evaluations` | specialist | `auth:api` + `profile:read` + `role:specialist` | Body: `offeringId`, `extractionMethod`, `descriptive`, `affective`, `extrinsics` |
| PUT | `/evaluations/{evaluationId}` | specialist (own) | `auth:api` + `profile:read` + `role:specialist` + `can:update,evaluation` | Body: `extractionMethod`, `descriptive`, `affective`, `extrinsics` — **full payload** |
| PATCH | `/evaluations/{evaluationId}/close` | specialist (own) | `auth:api` + `profile:read` + `role:specialist` + `can:update,evaluation` | — no body (state transition only) |
| DELETE | `/evaluations/{evaluationId}` | specialist (own) | `auth:api` + `profile:read` + `role:specialist` | — *(not built yet)* |

> **Build status.** Only `POST` (specialist create) and `PUT` (specialist update) are implemented. `GET` index/show, `PATCH close` and `DELETE` are designed here but **not yet built**. The `coffeeshop` baseline flow (a coffeeshop's provisional evaluation, `evaluationType: baseline`, at most one per offering) is a separate future endpoint — see the baseline design notes — so `coffeeshop` is not a role on these routes yet.

`evaluatorId` and `evaluationType` are **not** request inputs: the backend derives them from the authenticated user (`evaluatorId` = logged-in user). `evaluationType` is currently fixed to `specialist` by the column default on this route (the role-derived `coffeeshop→baseline` mapping belongs to the future baseline endpoint, not to this one).

### Authorization and scope

All evaluation routes sit under the `auth:api` group and its read floor (`CheckTokenForAnyScope::using('profile:read','profile:write')`), then `role:specialist`. A standard token carrying `profile:read` is enough — **evaluations do not require `profile:write`**. This is a deliberate exception to the step-up rule that governs profile and offering mutations: an evaluation is transactional data, not sensitive account data, so creating, editing and closing one is not treated as a step-up action. Elevating the token buys nothing here.

Ownership on `PUT` and `PATCH close` is enforced by `can:update,evaluation` → `EvaluationPolicy::update` (`$user->id === $evaluation->evaluator_id`, compared against the FK **column**, not the `evaluator` relation). The same `update` policy method authorizes both routes: editing and closing ask the same question (is this the owner?), so there is no separate `close` method — one is added only if the two ever diverge.

### PUT — full-payload replacement

`PUT` replaces the evaluation's editable content in full: the client sends the complete `descriptive` / `affective` / `extrinsics` blocks, not a partial diff. This is required by the cata-handling logic: on update the backend **deletes and recreates** the whole `evaluation_tastes` set from the payload, so a cata absent from the request is read as "the user removed it". A partial update would make "not sent because unchanged" indistinguishable from "removed" — hence full payload, by design. `affective.defects` accepts `[]` **or `null`** (both mean "no defects"); an absent key is not accepted for defects.

### PATCH close — dedicated state transition

Closing is its own route, separate from editing, so the two flows never mix: a request either edits content (`PUT`) or transitions state (`PATCH close`), never both in one call. `PATCH /evaluations/{evaluationId}/close` takes no body — it moves `status` from `open` to `closed`. Only `closed` evaluations enter an offering's consensus. **Closing is terminal: there is no reopen** (not a planned feature).

### Closed evaluations are immutable (409)

Once an evaluation is `closed` it can no longer be edited. A `PUT` against a closed evaluation is rejected with **`409 Conflict`** — the request is well-formed and the caller is authorized, but it conflicts with the resource's current state. This guard lives in the **controller** (`abort_if($evaluation->status === 'closed', 409, …)` before parsing), not in the policy: "already closed" is a state conflict (409), not an authorization failure (403) nor an input error (422). Keeping it out of the policy is what preserves the correct status code — a policy denial would surface as a misleading 403 ("you can't") when the truth is "this is already closed".

### `EvaluationResource`

```json
{
  "ulid": "01J…",
  "offeringId": "01J…",
  "evaluationType": "specialist",
  "status": "open",
  "extractionMethod": "v60",
  "cuppingScore": "84.25",
  "isDefective": false,
  "descriptive": {
    "fragrance": { "score": 11, "note": null },
    "aroma": { "score": 12, "note": null }
  },
  "affective": {
    "aroma": { "score": 8, "note": null },
    "overall": { "score": 8, "note": "…" }
  },
  "extrinsics": {
    "farming": null,
    "processing": null,
    "trading": null,
    "certifications": null,
    "generalObservation": null
  },
  "tastes": [
    { "ref": "01J…", "type": "aroma" },
    { "ref": "01J…", "type": "main_tastes" }
  ]
}
```

> `tastes` is the flat selection (leaf `ref` = the taxonomy node's ulid + `type`). The front rebuilds the cascade against the taxonomy tree it already has. `type` is snake_case here because it mirrors the DB ENUM values (`main_tastes`, `defects`, …). `cuppingScore` is a string (decimal cast); omitted/`null` on incomplete evaluations. `tastes` only appears when the relation was eager-loaded (`load('tastes.taxonomy')`).

## Users

| Method | Endpoint | Authorization | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| POST | `/register` | — | Public | Body: `role`, `name`, `surname`, `email`, `password`, `passwordConfirmation` |
| POST | `/login` | — | Public (web session) | Body: `email`, `password` — creates web session (Fortify, stateful guard); response `{"two_factor": false}`. Authenticate step before `/oauth/authorize`, does NOT issue a token |
| GET | `/oauth/authorize` | — | Requires active web session | Query: `client_id`, `redirect_uri`, `response_type=code`, `scope`, `state`, `code_challenge`, `code_challenge_method=S256`. With `skipsAuthorization` (first-party) returns `302` with the `code` in the `Location` header |
| POST | `/oauth/token` | — | Public (PKCE client) | Body (form-urlencoded): `grant_type=authorization_code`, `client_id`, `redirect_uri`, `code`, `code_verifier`. No `client_secret`. Returns `access_token` + `refresh_token` |
| POST | `/oauth/token` | — | Public (confidential client) | Body (form-urlencoded): `grant_type=password`, `client_id`, `client_secret`, `username` (email), `password`, `scope`. Returns `access_token` + `refresh_token` |
| POST | `/logout` | Authenticated | `auth:api` | — Revokes the request token (access + refresh) |
| GET | `/user` | Authenticated | `auth:api` | — No id. Returns the authenticated user's data (the front-end gets its id here) |
| PUT | `/users/{user}` | Own (policy `update`) + scope `profile:write` | `auth:api` | Body: `name`, `surname`, `email` (all `sometimes`) |
| PUT | `/users/{user}/password` | Own (policy `update`) + scope `profile:write` | `auth:api` | Body: `currentPassword`, `password`, `passwordConfirmation` |
| DELETE | `/users/{user}` | Own (policy `delete`) + scope `profile:write` | `auth:api` | — Soft delete (deactivate account, recoverable via `restore`). Sets `deleted_at`, keeps profile and related data |
| DELETE | `/users/{user}/force` | Own (policy `delete`) + scope `profile:write` | `auth:api` | — Hard delete (permanent removal). `forceDelete`; removes the row and cascades to related data (`ON DELETE CASCADE`). Binding uses `withTrashed` |

Login is a three-step PKCE flow: `POST /login` (creates the Fortify web session) → `GET /oauth/authorize` (with that session, returns the `code`) → `POST /oauth/token` (exchanges `code` + `code_verifier` for the `access_token`). The `access_token` authenticates `auth:api` routes via `Authorization: Bearer`. The `code_verifier` belongs to the client and only travels in the token step; only its hash (`code_challenge`) is sent to `/oauth/authorize`.

> OAuth2/PKCE body params (`grant_type`, `client_id`, `code_verifier`, etc.) stay snake_case — they follow the OAuth standard, not this API's camelCase convention.

> **Password grant — bootcamp only.** The `grant_type=password` row above is kept for the bootcamp exercise, alongside PKCE, so both flows can be practised. It is **not valid for this project's real context:** the password grant is deprecated in OAuth 2.1 and discouraged by RFC 9700 (it forces the client to handle the user's raw credentials and bypasses the authorize/consent screen, so no step-up is possible). The valid production flow is authorization code + PKCE.

### Scopes and step-up

The API defines exactly two scopes (`Passport::tokensCan`): `profile:read` and `profile:write`. There is no wildcard scope exposed.

- **Default scope.** `Passport::defaultScopes(['profile:read'])` — a token requested without an explicit `scope` is issued with `profile:read` (least privilege). The front only sends `scope=profile:write` when it needs an elevated token.
- **Read floor on the authenticated group.** The outer `auth:api` group also requires `CheckTokenForAnyScope::using('profile:read', 'profile:write')` — any valid token of the system (carrying either scope) passes the base routes (`/logout`, `/user`, the coffeeshop read routes). A token carrying only `profile:write` still passes here.
- **Step-up (scope `profile:write`).** Sensitive actions (update profile, change password, deactivate/delete account, create/delete offerings) require a token carrying `profile:write`, verified with `CheckTokenForAnyScope::using('profile:write')` (Passport 13). That token is obtained through the same authorization flow by requesting `scope=profile:write` at `/oauth/authorize` — a single login mechanism, re-authenticating to elevate the token. A token without that scope receives a `403`.

**Wildcard rejection (`RejectWildcardScope`).** Passport's token `can()` short-circuits to `true` if the token carries the `*` wildcard scope, which would defeat the read/write segmentation entirely — a token issued with `*` passes every `CheckTokenForAnyScope` check. Passport honours `*` by default and offers no native way to disable it. To close this, a custom middleware `RejectWildcardScope` runs on the `web` group, filtered to the `oauth/authorize` path: it reads the requested `scope`, splits it on spaces, and if `*` appears among the tokens it aborts with `400` before Passport issues anything. It rejects (rather than rewrites) the scope because the front is the only client, so a `*` is our own bug and must fail visibly. This lives in the OAuth issuance flow, not in the API routes — by the time a token reaches the API, the wildcard has already been prevented at issuance.

**Pending:**
- `GET /users/{id}` to fetch *other* users (third-party profiles): role allowed and exposed fields not yet defined.
- Anonymization / policy for generated data (evaluations) on hard delete.
- **User contact information as a separate entity:** define the model (likely relational, not JSON), its cardinality (one or several contacts per user?), which fields it holds, and its endpoints (own CRUD or nested under the user).
- Query-parameter filters for `GET /locations`.
- Public `GET /coffeeshops/{ulid}/locations` (locations of a given coffeeshop by ulid) — not yet decided whether it coexists with the private `GET /locations`.

## Coffeeshops

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/coffeeshops` | — | Public | Query: `city` (coffeeshops with a location/contact in that city) |
| GET | `/coffeeshops/{coffeeshopUlid}` | — | Public | — Returns the coffeeshop with its `offerings` and `locations` nested |

## Coffees

| Method | Endpoint | Role | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/coffees` | — | Public | Query: `coffeeName`, `originCountry`, `originRegion`, `process`, `producer`, `city` |
| GET | `/coffees/{coffeeUlid}` | — | Public | — |

Public catalog browsing of coffees (informational). Distinct from `/coffeeInventory`, which is the lot-level resource used to create offerings. Uses `CoffeeResource` (same shape as nested under `coffeeInventory`).