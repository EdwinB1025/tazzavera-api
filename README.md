<p align="center">
  <img src="https://raw.githubusercontent.com/EdwinB1025/tazzavera-api/PROD/public/scribeIcon.svg" width="120">
</p>


REST backend for **Tazavera**, the specialty coffee verification platform. This API serves the data model, OAuth2/PKCE authentication, and the **consensus** engine that averages and cross-checks specialist evaluations of each coffee a specialty coffee shop offers.

> This repository is **the API only**. The front end that consumes this REST API is `tazavera-front` (Next.js, separate repository).

## 🕵️ The problem it tackles

There's a lot of coffee labeled "specialty" that really isn't, and the everyday consumer has no way to tell — grading stays in the hands of a few, and that skews the market. Tazavera doesn't settle for yet another rating: it **verifies** what a coffee shop claims to sell. If it declares "fruity notes, high acidity, natural process," several specialists cup the actual product and the system derives a consensus that confirms or refutes that claim.

The evaluation system is based on the **SCA Coffee Value Assessment (CVA v2, provisional standard 2024–2025)**, adapting the formal cupping method to real coffee-shop conditions (coffee already brewed, without strict replication of the physical-cups protocol).

## 🧪 Evaluation model (overview)

- 🔬 **Descriptive (specialist)** — objective intensity record. Seven axes (fragrance, aroma, flavor, aftertaste, acidity, sweetness, mouthfeel) on a 0–15 scale, plus CATA descriptors from a hierarchical taxonomy and Main Tastes.
- ⭐ **Affective (specialist)** — the quality verdict, eight axes (the seven + overall) on a 1–9 scale. Derives a **cupping score 0–100** per evaluation and feeds the **aggregate consensus** at the offering level.
- 👤 **Consumer** — simple reaction mapped to the 1–9 scale. Role foreseen in the ENUM; its own flow remains in the backlog.

Specialist and consumer are **not averaged against each other** — they are cross-checked attribute by attribute; that's where the market-intelligence value lives.

### The consensus (this API's engine)

When an offering reaches **≥5 closed specialist evaluations**, closing an evaluation triggers — via the `EvaluationClosed` event and its `RecalculateConsensus` listener, **synchronously, inside the same request** — the recalculation of the consensus (`OfferingConsensusService::recompute`). The listener is wired by Laravel's automatic event discovery (`app/Listeners`) and does not implement `ShouldQueue`, so no queue worker is involved; running it in the background with workers is in the backlog.

- **Per-axis averages** (`*_avg`) and **aggregate cupping score** (CVA formula `0.65625·Σ + 52.75 − 2u − 4d`, rounded to 0.25).
- **Inter-specialist concordance** for the descriptive/affective parts (normalized dispersion index, `1 − σ/σ_max`), with per-axis detail in `axis_concordances`.
- **Consensus flavor tree** (`offering_tastes`): grouped descriptors, each with how many distinct specialists marked it.
- **`verification_status`**: `provisional` → `verified` once the threshold is reached.

The detail of the formulas, the threshold, and the reinterpretation of the deductions `u` (non-uniformity, derived from inter-specialist dispersion) and `d` (defects) is in the design documentation.

## 📚 Documentation

- 📖 **API docs:** [View documentation](https://edwinb1025.github.io/tazzavera-api/public/docs/index.html)

The page includes example requests (bash, JavaScript), a Postman collection (`/docs/collection.json`), and an OpenAPI spec (`/docs/openapi.yaml`).

> 🔄 In case of a discrepancy between docs and code, **the code is the source of truth**.

## 🛠️ Stack

- 🐘 **Framework:** Laravel 13, PHP 8.5
- 🔐 **Auth:** Laravel Passport 13 (OAuth2 + PKCE) + Fortify (web session for the authorization flow)
- 🛡️ **Roles/permissions:** Spatie Laravel-Permission (guard `api`)
- 🗄️ **Database:** MySQL 8 (dev/prod) · SQLite `:memory:` (tests)
- ⚙️ **Queues:** `database` driver configured; no queued job or listener today (the consensus runs synchronously); `sync` in tests
- 🧪 **Tests:** Pest (TDD)

## ✅ Prerequisites

- PHP 8.5+ with Laravel's standard extensions + **`ext-sodium`** (required by Passport at runtime — see `deployment-notes.md`)
- Composer 2.x
- **MySQL 8** running and reachable
- Node.js 18+ and npm (only if compiling the minimal OAuth views)

## 🚀 Installation

### 1. Clone the repository

```bash
git clone https://github.com/EdwinB1025/tazzavera-api.git
cd tazzavera-api
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to point to MySQL 8:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tazavera
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

Create the empty database before migrating:

```bash
mysql -u root -p -e "CREATE DATABASE tazavera;"
```

### 4. Install Passport (OAuth2)

```bash
php artisan install:api --passport
php artisan passport:keys
```

> ⚠️ If `install:api --passport` fails and reverts `composer.json`, it's almost certainly `ext-sodium` being disabled — enable `extension=sodium` in `php.ini` and retry (details in `deployment-notes.md`).

Create a public (PKCE) client for the front end:

```bash
php artisan passport:client --public
```

### 5. Migrate and seed

```bash
php artisan migrate --seed
```

🌱 The seeder populates the olfactory taxonomy (from CSV, source WCR Sensory Lexicon), the coffee catalog, roasters, inventory, and base data to test the flow end to end:

- `LocationSeeder` — a closed list of 20 real specialty coffee businesses in Barcelona (`database/seeders/data/coffeeshops-barcelona.json`, e.g. Nomad Coffee, Satan's Coffee Corner, Syra Coffee, Cafès El Magnífico, Cometa): one `coffeeshop` user per business, named after it, with 1 to 3 locations named after the business and its neighbourhood, each with its primary contact (real address, `080xx` postal code, Barcelona, España) and coordinates inside Barcelona. No network during seeding.
- `OfferingBaselineSeeder` — 1 to 5 offerings per coffee shop, spread over its locations, each with the coffee shop's closed **baseline** evaluation (its provisional evaluation of the offering).
- `VerifiedOfferingSeeder` — a sample of 15 offerings, each with 5 to 9 closed specialist evaluations from a pool of 10 specialists; it dispatches `EvaluationClosed` per offering, so the consensus runs and those offerings end up `verified`.

## ▶️ Running the project

### Server

```bash
php artisan serve
```

The API is available at `http://localhost:8000`.

### Consensus recalculation

No worker is needed: closing an evaluation dispatches `EvaluationClosed`, and Laravel runs the `RecalculateConsensus` listener inside the same request (automatic event discovery, no `implements ShouldQueue`), so the close response comes back once the consensus is computed. `php artisan event:list` shows the event → listener wiring.

> Running it in the background with a queue worker is in the backlog (see below).

### Tests

```bash
php artisan test
```

They run against SQLite `:memory:` with `QUEUE_CONNECTION=sync`; the consensus executes inline, as it does in every environment today.

## 🔑 Authentication (OAuth2 / PKCE)

Login in three steps:

1. `POST /login` — creates the web session (Fortify); returns `{"two_factor": false}`, **without** a token.
2. `GET /oauth/authorize` — with that session + PKCE parameters, returns the `code` (302, consent skipped for the first-party client).
3. `POST /oauth/token` — exchanges `code` + `code_verifier` for `access_token` + `refresh_token`.

The `access_token` authenticates the `auth:api` routes via `Authorization: Bearer`. Two scopes: `profile:read` (default) and `profile:write` (step-up for sensitive actions). Full detail in `endpoints.md`.

A `profile:write` request always forces a fresh login. When the client sends `login_hint` (the ULID of the user it expects), the code is issued only to that user: another user who signs in is signed out and the client receives `error=access_denied` (with its `state`) on its registered redirect URI (`RequireHintedUserForStepUp`).

## ☕ Public coffee shop directory

A **coffee shop** is the business: a user with role `coffeeshop` that owns one or more locations. The directory is public (no token), like `GET /offerings`, and returns **business data only** — never the owner's email, surname, personal contacts or account data.

- `GET /coffeeshops` — paginated (same `links`/`meta` as `GET /offerings`) list of the coffee shops with at least one location. Filters: `name` (partial, case-insensitive), `city` and `postalCode` (any of its locations' primary contacts), `verified` (1: at least one verified offering in any location; 0: none), `orderBy=name`, `orderDirection` (default `name asc`) and `page` (15 per page, as `GET /offerings`). Each item carries `locationsCount`, `offeringsCount`, `verifiedOfferingsCount` and **every** location (the map shows all the locations of the filtered coffee shops).
- `GET /coffeeshops/{ulid}` — one coffee shop; `404` for an unknown ULID or a user that is not a coffee shop. The detail page has two tabs: **Offerings**, read with `GET /offerings?coffeeshopUlid={ulid}`, and **Locations**, the `locations` array.

## 📍 Contacts, locations and coordinates

- `GET /users/{user}/contacts` and `GET /users/{user}/locations` return the authenticated user's own contacts and locations (self only, `403` otherwise).
- `POST /users/{user}/contacts` (`profile:write`) creates the user's single primary contact; a second one is a `409`.
- `POST /locations` (role `coffeeshop`, `profile:write`) creates a location and its primary contact in one transaction; the owner is always the authenticated user.
- **Coordinates are computed client-side**: the front geocodes the address and sends `latitud`/`longitud` (optional, each required with the other, -90..90 / -180..180). The API adds no geocoding dependency and stores them as received; `latitud`, `longitud` and `description` are nullable.

## 📊 Implementation status

**API complete.** All MVP endpoints are built and covered by Pest tests:

| Piece | Status |
|---|---|
| OAuth2 + PKCE auth (Passport) | ✅ End-to-end (Postman + Pest) |
| Scopes + step-up + wildcard rejection | ✅ Implemented |
| User CRUD (soft + hard delete) | ✅ Implemented |
| Offerings (batch create, delete single/batch, filtered index, show) | ✅ Implemented |
| Evaluations (create, update, close, delete, filtered index, show) | ✅ Implemented |
| Evaluation filters by offering (`offeringId`) and type (`evaluationType`: specialist / baseline) on the public, per-user and own lists | ✅ Implemented |
| Coffee shop baseline (provisional evaluation): seeded per offering, read through `GET /evaluations?offeringId=…&evaluationType=baseline` | ✅ Seeded and readable (creation endpoint in the backlog) |
| Individual cupping score (0–100, 8 real axes) | ✅ Implemented |
| Aggregate consensus (event + synchronous listener on **close**) | ✅ Implemented (background workers in the backlog) |
| Inter-specialist concordance (normalized dispersion) | ✅ Implemented (columns + `axis_concordances`) |
| `−4d` (defects) and `−2u` (non-uniformity) deductions in cupping | ✅ Implemented |
| Consensus flavor tree (`offering_tastes`) | ✅ Populated (parents + leaves, count = distinct specialists) |
| `verification_status` (provisional → verified) | ✅ Implemented |
| Own contacts (list, single primary contact) and own locations (list, create with primary contact) | ✅ Implemented |
| Client-side coordinates (`latitud`/`longitud` optional, validated, nullable columns) | ✅ Implemented |
| Public coffee shop directory (`GET /coffeeshops` with filters + pagination, `GET /coffeeshops/{ulid}`) | ✅ Implemented |
| Barcelona specialty coffee shops seeded from a fixed data file | ✅ Seeded |

## 🗺️ Backlog

What was **deliberately left out of the MVP** to keep the scope manageable — future product functionality, not technical debt.

- 👤 **Consumer evaluation** — the role exists in the ENUM, but its form, validation, and own structure (CATA restricted to the upper taxonomy levels) remain to be defined; likely a separate entity.
- 🏪 **Coffee shop baseline — creation endpoint** — a dedicated endpoint for the coffee shop to create its provisional evaluation of its own offering (one per offering, with ownership and uniqueness). Today baselines are seeded and read through the evaluation filters; creating them through the API is designed, not built.
- 🔀 **Consensus segregated by extraction method** — today the consensus mixes espresso, V60, French press, etc. Split the calculation by method once there's enough volume to avoid losing sample size.
- ⚙️ **Consensus in the background (queue workers)** — make `RecalculateConsensus` implement `ShouldQueue`, so closing an evaluation writes a job to the `jobs` table and answers without waiting for the recalculation; a `php artisan queue:work` process (its own container in Docker) runs it. The event wiring (automatic discovery, `bootstrap/app.php`) does not change. Worth it when the recalculation slows the close response; with parallel workers, see the race-condition notes in the implementation notes.
- ⏱️ **Automatic evaluation-close cron** — bulk-close evaluations left open beyond a certain time (Laravel scheduler), instead of relying on manual closing. Distinct from the consensus recalculation (a scheduled task, not an event listener).
- 🎯 **Q calibration tag on acidity** — cross the tag with the evaluator's certification to detect whether Q-certified specialists agree more with each other on acidity descriptors.
- 🧾 **Transactional marketplace** — orders, payments, and communication with the coffee shop. The MVP is a directory + verification, not sales.
- 💸 **Automated payout** to coffee shops (scheduled settlement).
- 🏅 **Loyalty panel for coffee shops** — subscriptions, points.
- 📈 **Market trend reports** — value weighted per attribute by consumer segment.
- 🎓 **Specialist verification — full subsystem** — question bank, self-validation, and a combination of Q Grader certification + exposure + community rating. The MVP settles for the minimal declaration.
- ☕ **Third side of the market — monetized specialists** — consulting, recipes, cupping workshops.
- 🚚 **Third-party logistics integration** — delivery APIs.
- 🌱 **Green coffee physical assessment** — evaluation of the unroasted bean; the SCA standard for this is in alpha.
- 📖 **Usage guide + FAQ** — deferred until the evaluation decisions are settled.
- 🎭 **Several roles for the same user** — let one account hold both `specialist` and `coffeeshop` and choose which one it acts as. To design first: whether the API restricts actions to the chosen role (an `active_role` on the user, changed through its own endpoint and validated against the roles the user has; policies check the active role instead of `hasRole`) or only exposes the roles and leaves the choice to the client; and whether the choice belongs to the user (every device) or to the session/token. The user resource would expose `roles` as a JSON array (today `role` is a single string), which changes the contract.
- 🧮 **Statistical refinement of the consensus** — revisit `σ_max` (theoretical vs. realistic) and evaluate ICC / Fleiss as a concordance index once there's enough multi-offering volume.

## 📝 Notes

- 🔍 `php artisan tinker` to inspect data quickly (e.g. `Evaluation::first()->affective`).
- 🩹 If something fails when migrating with a SQL syntax error, check that `.env` points to MySQL 8 and not `sqlite`.
- 🔄 Once the consensus runs on a queue worker (backlog), remember that the worker caches code in memory: after editing the consensus service, restart `queue:work` (or recreate its container).
