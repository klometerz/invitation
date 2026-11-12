# PROGRESS

Handoff document. Read this first in a new session, then `AGENTS.md` §0-6 and
the current phase.

Last updated: **2026-11-12** (Phase 0, session 1).
Branch: `feature/multi-tenant`.

---

## Phase 0 - Safety and audit

| Task | Status | Commit | Notes |
|---|---|---|---|
| P0-T1 Git baseline | **DONE** | `ba6d81e` | `.gitignore` now ignores `/backups` and upload contents. Branch `feature/multi-tenant` created; `git branch --show-current` prints it. |
| P0-T2 Backups | **DONE** | `b9451ca` | `backups/pre-multitenant-20261112.sql` + `backups/storage-public-20261112/` (27 files, 10.6 MB) + `docs/RESTORE.md`. Dump **restore-verified** against a scratch DB: 158/6/11/1/3/1/15. |
| P0-T3 Test isolation | **DONE** | `b06b289` | `phpunit.xml` was already isolated on sqlite `:memory:`. Added `RefreshDatabase` to `ExampleTest`, the guard in `tests/TestCase.php`, and `TestDatabaseGuardTest` (4 cases). `php artisan test` -> **6 passed**. |
| P0-T4 Audit | **DONE** | see below | `docs/AUDIT.md` + raw evidence. |
| P0-T5 Docs scaffolding | **DONE** | see below | This file + `docs/DECISIONS.md`. |
| GATE | **AWAITING APPROVAL** | - | Summary given in chat. |

Verification for each task: `php artisan test` -> 6 passed (9 assertions);
`npm run build` -> exit 0 (warnings are pre-existing).

---

## Facts worth carrying forward

- **Laravel 12.44.0**, PHP 8.3.16, MySQL 8.4.3, Node 22, PHPUnit 11.5, Vite 7,
  Svelte 5, Tailwind 4. Filament is gone.
- Real DB is the **local `invitation`** database (the `.env` also names a
  remote `kald5548_invitation`, which is **not** used by this checkout and
  must not be touched).
- Live row counts (**exact** `COUNT(*)`): guests 158, galleries 6,
  photobooth_photos 11, invitations 1, users 3, themes 1, migrations 15.
- **Never** use `information_schema.table_rows` for verification - it is a
  lagging estimate and caused a false "stale backup" alarm in this session.
- Migrations **do** run on SQLite (all 15 apply under `RefreshDatabase`).
- `themes` **already exists** (no `key`, no `is_active`; has `slug`,
  `svelte_path`, `is_premium`).
- `photobooth_photos` has **no** `invitation_id` - only `guest_id`.
- Public URL is `/`; the slug page is `/v/{slug}`.
- `POST /api/rsvp/{code}` lives in `routes/api.php`.
- Hardcoded identities: 47 matches, all in `resources/`, none in `app/`.
  Full file:line list in `docs/AUDIT.md` §6.
- Dead code to remove in Phase 1: `GuestController copy.php`,
  `routes/web.php:16-35` (commented closure), `Pages/* copy.svelte`.

---

## Local command setup (Laragon binaries are not on PATH)

```powershell
$env:PATH = 'C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\composer;' + $env:PATH
```

`npm` cannot be invoked directly in this shell (its `.cmd` shim is not
executable here); use `cmd /c "npm run build"`.

---

## Open owner questions (blocking Phase 1 detail work)

From `docs/DECISIONS.md`:

1. **C1** - `themes` table shape: additive `key`/`is_active`, or replace
   `slug`/`svelte_path`? (Recommendation: additive.)
2. **C2** - may `/v/{slug}` be retired with a 301 to `/{slug}`, or must it stay?
3. **C9** - leave the `create_theme_table` `down()` defect
   (`dropIfExists('theme')`) alone? (Recommendation: leave it.)

---

## Next actions (in order)

1. Owner answers C1/C2/C9 and says "Phase 0 approved".
2. Start Phase 1 with P1-T1 (additive migrations), `migrate --pretend` first.
3. Cross-tenant hardening for `/admin/guests-truncate` (K2) - see `AUDIT.md` §11.
