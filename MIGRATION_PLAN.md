# Migration Plan — Supabase → Laravel

## Stack
- **Frontend:** Vue 3 + TypeScript + Vite (repo root)
- **API:** Laravel 13 + Sanctum SPA cookie auth (`api/`)
- **Database:** MySQL 8
- **Server:** Nginx + PHP-FPM on the IONOS VPS (Ubuntu 24.04, 2 GB RAM, shared with other sites)
- **Routing:** one domain. `/` serves the SPA; `/api/*` goes to Laravel.
- **Not used:** Docker, Redis, queue workers. Laravel runs with the `file` session/cache drivers and `sync` queue.

## Phase 1 — port to Laravel (implemented locally, not deployed)
- **Schema:** Laravel migrations for every Supabase table. IDs stay strings; new rows get ULIDs. Booking times are business-local `DATETIME` values.
- **Case-sensitivity:** customer emails dedupe case-insensitively. Rider names use `utf8mb4_bin`, so they match exactly.
- **Booking logic:** `AvailabilityService` (slots), `HorseAvailability` (the one shared horse rule) and `BookingService` (guest booking).
  - Each booking runs in one transaction, serialized by a row lock on `business_config`.
  - Covers two-rider/two-horse lessons, horse conflicts through `booking_horses`, DST handling, and recurring bookings (stored as ordinary booking rows).
- **Auth:** a `users` table with `role`, plus the `auth:sanctum` and `admin` middleware, replaces Supabase Auth and RLS. Admins are created with `php artisan admin:create email`.
- **Frontend:** the `src/lib/api.ts` client and `src/services/apiService.ts` keep the function signatures the stores already use. The Vite dev server proxies `/api`.
- **Tests:**
  - `npm run test:api` runs PHPUnit against MySQL. It includes the ported SQL test cases and a concurrent-booking race test.
  - The Playwright tests (`test:browser`, `test:admin`, `test:riders`) run against `npm run dev` plus `npm run api`.

### Local development
```sh
npm run api      # Laravel on :8000
npm run dev      # Vite on :5173, proxies /api
npm run db:reset # migrate:fresh --seed
```

### Deployment (needs a separate go-ahead)
1. **Read-only audit first:** `free -m`, `php -v`, `nginx -T`, the existing FPM pools, `mysql --version`.
2. **Database:** create an `rjph` database and user. Don't change global MySQL settings.
3. **Server config:** install `deploy/php-fpm-rjph.conf` (RJ's own pool) and the `/api` block from `deploy/nginx-rjperformancehorses.conf` (merged into the existing site file). Run `nginx -t`, then reload. Add TLS with certbot.
4. **Laravel environment:** create `api/.env` with these values:
   ```
   APP_ENV=production
   APP_DEBUG=false
   DB_*=…
   SESSION_DOMAIN=rjperformancehorses.com
   SESSION_SECURE_COOKIE=true
   SANCTUM_STATEFUL_DOMAINS=rjperformancehorses.com,www.rjperformancehorses.com
   ```
5. **First deploy only:** copy `api/storage/` once (deploys skip it) and give the `rjph` user write access to it.
6. **Data and admin:** run `php artisan db:seed --class=ReferenceDataSeeder` (lesson types and waiver only), then `php artisan admin:create`.
7. **Deploy:** `deploy/deploy.sh user@host`. Add a daily `mysqldump` cron job for `rjph`.
8. **After the RAM upgrade:** raise `pm.max_children`. Nothing else changes.

## Phase 2 — after launch
- Remove redundant site options (list to come).
- A user account for each rider (`riders.user_id` already exists).
- SSO login through Laravel Socialite (`users.password` is nullable).
- Open question: do minors get their own login, or are they managed through a parent's account?
