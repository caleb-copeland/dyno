# CLAUDE.md — Dyno

Invite-only progressive web app for curated climbing strength training. Single
Laravel 13 codebase: member PWA (Breeze/Blade auth + Livewire 4) and a Filament 5
admin at `/admin`, same-origin. WHOOP-style OLED-dark UI. See `README.md` for the
product shape; this file is the working guide.

## Running it

No local PHP — everything runs in Docker.

- **Dev**: Sail, driven by `make` (`make setup`, `up`, `down`, `test`, `fresh`,
  `admin`, `logs`). App on **127.0.0.1:8091**, MySQL 3307, Vite 5174 — bound to
  loopback, *not* publicly routed. Dockerized composer for package changes:
  `docker run --rm -v "$(pwd):/app" -w /app composer <cmd>`.
- **Prod**: `compose.prod.yaml` + `Dockerfile` (FrankenPHP, image-baked) at
  **dyno.gizmostash.com** behind the shared Traefik (`../traefik`), Cloudflare
  TLS, no Authentik gate. Separate compose project `dyno-prod`. See `DEPLOY.md`.
- Run the full test suite: `./vendor/bin/sail artisan test` (PHPUnit, class-based
  — Pest is not installed).

## Gotchas (learned the hard way)

- **`.env` is policy-blocked** from reads in this environment — put committed
  config in `config/*.php`, never rely on editing `.env`.
- **Alpine `@`-shorthand events collide with Blade directives** — a
  `@session-finished.window` attribute is parsed as Blade's `@session`. Always
  use `x-on:` in Blade files.
- **Livewire component views**: avoid raw `<script>` and echo-less inline `@if`
  inside a Livewire view — both break its Blade compiler. Put JS in the layout;
  build display strings server-side.
- **Filament 5** moved layout components — `Section` is
  `Filament\Schemas\Components\Section`, not `Filament\Forms\Components`.
- **Breeze Livewire stack pins Livewire 3, Filament 5 needs Livewire 4** — we use
  Breeze's *blade* stack + Filament's Livewire 4.

## Shape

- Auth: invite-only (hashed CSPRNG tokens, `/admin/invites`); `User.role`/`active`;
  Filament gated by `canAccessPanel`. First admin: `artisan app:create-admin`.
- Domain: `App\Enums\FocusArea` (6 areas incl. `push`/antagonist, each with a
  semantic accent). Library: exercises → workouts → ordered `workout_exercises`.
- Runner: `App\Livewire\WorkoutRunner` + Alpine in `layouts/runner` — timers,
  Web Audio, Wake Lock; set toggles go through `App\Actions\LogSet` /
  `POST /api/set-log` with a localStorage offline queue (flush on reconnect).
- Scheduler: `App\Services\ScheduleGenerator` (brute-force + constraint tiers) +
  `App\Livewire\ScheduleBuilder`.
- Baseline tests: gated `App\Livewire\BaselineTest`; feed `% -of-test` into the runner.
- Push: `minishlink/web-push`, VAPID via `artisan app:vapid`; `app:send-reminders`
  runs daily via the scheduler container.

Every change should keep `artisan test` green. Security-sensitive edits (auth,
the `/api/*` endpoints, Livewire public props) have had multiple audit passes —
keep client-supplied Livewire properties validated/`#[Locked]` and everything
user-scoped by `Auth::id()`.
