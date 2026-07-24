# Production deployment

Dyno serves at **https://dyno.gizmostash.com** from `compose.prod.yaml` +
`Dockerfile` (FrankenPHP, PHP 8.4). Code and built assets are **baked into the
image** — no bind mounts. Dev keeps using `compose.yaml` (Sail) untouched; the
prod stack uses a separate compose project (`dyno-prod`) so the two never
collide.

TLS is issued automatically by the shared Traefik stack (Cloudflare DNS-01);
the DNS record for `dyno.gizmostash.com` already resolves to this host. There is
**no Authentik gate** — the app has its own invite-only login (§3).

## Stack

- **app** — FrankenPHP web container, HTTP :8080, routed by Traefik. Runs
  `migrate --force` on boot.
- **scheduler** — runs `schedule:work`, which fires `app:send-reminders` daily
  (push notifications for users training today).
- **mysql** — 8.4, internal network only, no published ports.

## Fresh deploy

Prereqs: Docker + Compose, the shared Traefik stack (`traefik/`) running, and
the external `frontend` network:

```bash
docker network create frontend   # skip if it already exists
```

1. Create the env file and fill in the secrets:

   ```bash
   cp .env.production.example .env
   ```

   Set `DB_PASSWORD` and `DB_ROOT_PASSWORD` (strong, distinct). Optionally set
   the `MAIL_*` relay (for password-reset emails) and `VAPID_*` (push).

2. Build, generate the app key, paste it into `.env`:

   ```bash
   docker compose -f compose.prod.yaml build
   docker compose -f compose.prod.yaml run --rm --no-deps --entrypoint php app artisan key:generate --show
   # → put the base64:... value in .env as APP_KEY
   ```

3. (Optional) Generate VAPID keys for push and add the three values to `.env`:

   ```bash
   docker compose -f compose.prod.yaml run --rm --no-deps --entrypoint php app artisan app:vapid
   ```

4. Start everything (the app waits for MySQL, then migrates):

   ```bash
   docker compose -f compose.prod.yaml up -d
   ```

5. Create the first admin (registration is invite-only — this is the bootstrap):

   ```bash
   docker compose -f compose.prod.yaml exec app php artisan app:create-admin
   ```

   Then sign in at https://dyno.gizmostash.com/admin, curate the exercise /
   workout library, and issue member invites from **/admin/invites**.

## Redeploy (after a git pull)

```bash
docker compose -f compose.prod.yaml up -d --build
```

The image rebuilds; the `app` container re-runs `migrate --force` and rebuilds
runtime caches on boot. Data lives in the `mysql-data` volume and survives.

## Notes

- **Reminders** run only if `VAPID_*` is set and the `scheduler` container is
  up. Without VAPID the push feature no-ops cleanly.
- **Secrets**: `.env` is gitignored — never commit it. Only
  `.env.production.example` is tracked.
- To seed the starter exercise/workout content:
  `docker compose -f compose.prod.yaml exec app php artisan db:seed --class=LibrarySeeder --force`.
