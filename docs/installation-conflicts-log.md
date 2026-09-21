# Deployment notes

> Log of significant conflicts encountered while installing packages/dependencies, plus deploy-time notes.
> Only real conflicts are recorded (something that blocked or broke the installation), not routine steps.

---

## 1. Passport ↔ PHP 8.5.4

- **Command:** `php artisan install:api --passport`
- **Result:** fails; Composer reverts `composer.json` / `composer.lock`. Passport is not installed.
- **Cause:** PHP 8.5.4 is not supported by Passport 13's dependency chain.
  `league/oauth2-server` → `lcobucci/jwt` only supports up to ~PHP 8.4; the one version that could (`lcobucci/jwt 5.6.0`) requires `ext-sodium`, which was missing/disabled.
- **Status:** resolved.
- **Solution:** enable `ext-sodium` in `php.ini` (uncomment `extension=sodium`) and retry. The extension unblocked the `lcobucci/jwt 5.6.0` path and the rest of the tree resolved. Passport installed successfully (confirmed in package discovery).
- **Note:** `--ignore-platform-req=ext-sodium` was NOT used — Passport needs sodium at runtime; forcing it would have left an installation that fails when operating. `ext-sodium` must also be enabled on the deploy server's PHP.

---

## 2. Pest ↔ incomplete scaffold (missing `tests/Pest.php`)

- **Symptom:** running Feature tests: `Target class [validator] does not exist` and `Call to undefined method ...::get(). Did you forget to use the [pest()->extend()] function?`
- **Cause:** a Pest installation command was not run (`./vendor/bin/pest --init`), so `tests/Pest.php` was missing — without it, Laravel's `TestCase` is not applied to the `Feature` folder and the app does not boot during tests.
- **Status:** resolved.
- **Solution:** run `./vendor/bin/pest --init` to generate the scaffold (creates `tests/Pest.php`), then ensure it contains `pest()->extend(Tests\TestCase::class)->use(RefreshDatabase::class)->in('Feature');`.

---

## Deploy notes (not installation conflicts, but relevant)

- **`uncompromised()`** in `Password::defaults()` makes an HTTP call to pwnedpasswords.com. It must be **active only in production** (guard it with `app()->isProduction()`); in local/testing it is disabled to avoid the SSL failure and the network dependency. On the deploy server, ensure `ext-sodium` is enabled (Passport needs it at runtime) and that the CA certificate bundle (`cacert.pem`) is configured if production does use `uncompromised()`.

- **Task scheduler trigger (`certifications:purge` and future scheduled tasks):** the schedule is declared in `routes/console.php` with `Schedule::command('certifications:purge')->daily()`, but that only defines the cadence — it does NOT create any cron. The trigger that runs `php artisan schedule:run` every minute is server infrastructure and depends on the deploy OS: Linux → a single crontab entry (`* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`); Windows Server → Task Scheduler pointing at `schedule:run` every minute; managed platforms (Forge/Vapor/Laravel Cloud) → scheduler already wired, no manual cron. On **local native Windows** there is no cron: use `php artisan schedule:work` (a foreground process that simulates cron) to test, and `php artisan schedule:list` to verify tasks and next run time. The app is portable; only the trigger changes depending on where the server runs.

- **Consensus queue worker (`OfferingConsensusService` via `EvaluationClosed` event):** closing an evaluation dispatches an event whose listener (`RecalculateConsensus implements ShouldQueue`) recomputes the offering's consensus in the background. This is a **reactive worker, NOT a cronjob** — it must be a long-lived process that reacts to jobs the instant they hit the `jobs` table, not a scheduled task. The worker (`php artisan queue:work`) is Laravel; **keeping it alive is infrastructure**, and depends on the deploy OS/platform:
  - **Linux VPS** → Supervisor: `apt install supervisor`, config in `/etc/supervisor/conf.d/*.conf` with `[program:...]` `autostart=true` `autorestart=true`, `command=php artisan queue:work --sleep=3 --tries=3 --max-time=3600`; then `supervisorctl reread/update/start`.
  - **Forge/Vapor/Laravel Cloud** → Daemons/worker panel configures Supervisor for you; no manual config.
  - **Docker** → a SEPARATE service in `docker-compose.yml` reusing the app image with `command: php artisan queue:work ...` + `restart: always` (the container runtime replaces Supervisor; this one lives in the repo). Kubernetes → its own Deployment.
  - **Local native Windows** → no Supervisor: run `php artisan queue:work` in a foreground terminal to test, or set `QUEUE_CONNECTION=sync` in `.env` to run jobs inline without a worker. Tests already use `sync` (phpunit.xml), so the listener runs inline there.
  - **`--max-time=3600`** makes the worker self-restart hourly for **memory hygiene** — long-lived workers accumulate memory (stateful services aren't cleared between jobs). Supervisor/Docker relaunches it immediately.
  - **Every deploy needs `php artisan queue:restart`** — workers cache code in memory and keep running the old version until restarted. Put it in the deploy pipeline after uploading code. In Docker this is automatic since the container is recreated per deploy.
  - Distinct from the **backlog cronjob** for auto-closing stale open evaluations — that one IS a scheduled task (Laravel scheduler, same trigger as `certifications:purge` above), not this reactive worker.

---

## Consensus worker scaling (backlog, NOT MVP)

Consensus recompute runs on a SINGLE worker (`queue:work`) for the MVP — capstone volume, serial processing, no race conditions. Parallelization is future scaling, documented here for when volume justifies it.

**Parallelizing workers:** multiple `queue:work` processes (NOT multithreading — PHP is single-threaded; you scale by PROCESSES, coordinated through the `jobs` table with `reserved_at` locking, not by threads or in-code locks). Enabled via `numprocs=N` in Supervisor or `replicas: N` in Docker. No code changes: each worker is independent and the DB prevents two workers from processing the same job.

**Race condition to resolve IF parallelized:** two evaluations for the SAME offering closed near-simultaneously enqueue two consensus jobs for that offering → two workers could recompute it at the same time. Two (non-exclusive) protections:
1. **Idempotency (already implemented):** `recompute` recalculates from scratch (reads all closed evaluations, computes, overwrites with `updateOrCreate` on the UNIQUE). Two concurrent recomputes end in the same correct result — order doesn't matter. This already protects against corruption; the worst case is duplicated work, not bad data.
2. **`WithoutOverlapping` (only if duplicated work matters):** a job middleware that serializes jobs for the same offering via a cache lock (requires Redis/database cache). To use it, convert the `RecalculateConsensus` listener into a dedicated Job (`RecomputeConsensusJob` with `public int $offeringId` in the constructor), because the middleware needs the offeringId at construction and a listener doesn't have it there:
```php
   public function middleware(): array
   {
       return [new WithoutOverlapping($this->offeringId)];
   }
```
   Serializes same-offering jobs, lets different-offering jobs run in parallel.

**When to activate:** only if (a) one worker can't process jobs in time (they pile up) AND (b) duplicated work for the same offering is a real problem. Neither applies to the MVP. Requires real volume data to decide, not anticipated design.

**Real PHP multithreading** (Swoole, ext-parallel, Fibers) does NOT apply here — it's for high-performance async servers or CPU-intensive compute, not queues. The multiprocess queue model exists precisely to avoid that complexity.

## Template for new conflicts

## N. <Package> ↔ <cause>
- **Command:**
- **Result:**
- **Cause:**
- **Status:** open / resolved
- **Solution (if resolved):**