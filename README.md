# Dyno

Self-hosted, invite-only progressive web app for curated climbing strength training.
Single Laravel codebase; PWA + Filament admin, same-origin. See the product brief for the
full design rationale (injury-aware scheduler, workout runner, WHOOP-style UI).

## Stack

Laravel 13 · Breeze (Blade auth) · Filament 5 admin at `/admin` · Livewire 4 · MySQL 8.4 ·
Sail (Docker). No local PHP required.

## Dev

```
make setup     # first run: deps, Sail, migrate, build assets
make admin     # create the first admin (registration is invite-only)
make up/down/test/fresh/logs
```

App → http://127.0.0.1:8091  ·  Admin → http://127.0.0.1:8091/admin
Dev stack binds to 127.0.0.1 only — not publicly routed through Traefik.

## Auth model (invite-only)

- **No public signup.** `/register` 404s unless given a valid, unused, unexpired invite token.
- **Invites** (`invites` table): 32-byte CSPRNG token, stored **SHA-256 hashed**, bound to an
  email, single-use, expiring. Issued from the Filament admin (`/admin/invites`); the raw link
  is shown **once** on creation and never stored.
- **Users** carry `role` (`member`/`admin`, string column so a 3rd role needs no migration) and
  `active` (revoke access without deleting records). Deactivated users can't log in.
- **Filament panel** gated by `canAccessPanel` → `role === 'admin' && active`.
- Login: separate IP (30) and account (5) rate limiters; session regenerated on login.
- Session lifetime defaults high (30d) so an installed PWA stays logged in.

## Build sequence

1. ✅ Auth + invites + Filament panel guard
2. ⬜ Exercise + workout library (Filament resources)
3. ⬜ Workout runner (both timers, Web Audio cues, Wake Lock) — *gym-test before proceeding*
4. ⬜ Logging + history
5. ⬜ Schedule builder (brute-force generator + drag-to-edit, constraint tiers)
6. ⬜ Baseline tests (gated, scripted warmups)
7. ⬜ Offline + background sync
8. ⬜ Push notifications
