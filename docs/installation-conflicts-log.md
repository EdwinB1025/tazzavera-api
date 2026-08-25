# Installation conflicts log

> Log of significant conflicts encountered while installing packages/dependencies.
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

## Deploy note (not an installation conflict, but relevant)

- **`uncompromised()`** in `Password::defaults()` makes an HTTP call to pwnedpasswords.com. It must be **active only in production** (guard it with `app()->isProduction()`); in local/testing it is disabled to avoid the SSL failure and the network dependency. On the deploy server, ensure `ext-sodium` is enabled (Passport needs it at runtime) and that the CA certificate bundle (`cacert.pem`) is configured if production does use `uncompromised()`.

---

## Template for new conflicts

## N. <Package> ↔ <cause>
- **Command:**
- **Result:**
- **Cause:**
- **Status:** open / resolved
- **Solution (if resolved):**
