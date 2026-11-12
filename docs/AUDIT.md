# AUDIT - Phase 0 (P0-T4)

Facts observed on 2026-11-12 against the local checkout
`C:\laragon\www\invitation` and the local MySQL database `invitation`.
No code was changed for this audit. Secrets were not read or printed.

> **Read this first:** several assumptions in `AGENTS.md` section 1 and 6 do
> not match the code. They are listed in [section 10](#10-contradictions-with-agentsmd)
> and copied to `docs/DECISIONS.md` as open owner questions.

---

## 1. Platform and versions

| Item | Value | Source |
|---|---|---|
| Laravel | 12.44.0 | `php artisan --version`, `composer.json` `laravel/framework: ^12.0` |
| PHP (CLI) | 8.3.16 (ZTS, x64), Xdebug 3.4.7 loaded | `C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe` |
| PHP version constraint | `^8.3` (`config.platform.php = 8.3.0`) | `composer.json` |
| MySQL client / server | 8.4.3 (Windows service `mysql`, running) | `mysql --version` |
| Node | v22 (`C:\laragon\bin\nodejs\node-v22`) | folder listing |
| Inertia (server) | `inertiajs/inertia-laravel ^2.0` | `composer.json` |
| Svelte | 5 (runes used in pages) | `package.json`, component source |
| Vite | 7 | `package.json` |
| Tailwind | 4 | `package.json` |
| Test framework | **PHPUnit 11.5** (not Pest) | `phpunit.xml`, `composer.json` |
| Pint | `laravel/pint ^1.24` | `composer.json` (dev) |
| Other packages | `maatwebsite/excel ^3.1`, `simplesoftwareio/simple-qrcode ^4.2`, `laravel/sanctum ^4.0`, `laravel/tinker ^2.10`, `spatie/laravel-package-tools` | `composer.json` |
| Filament | **not installed** (confirmed) | `composer.json` |

PHP extensions relevant to the upgrade are all present: `pdo_mysql`,
`pdo_sqlite`, `sqlite3`, `gd`, `fileinfo`, `zip`, `mbstring`, `openssl`.

### Installed but unused

- `spatie/laravel-package-tools` is required by `composer.json` but nothing in
  `app/` uses it. Filament was removed; the `composer.json`
  `post-autoload-dump` script still calls `@php artisan filament:upgrade`
  (a command that no longer exists). See open questions.

---

## 2. Routes (`php artisan route:list --except-vendor`)

Raw output: `docs/routes.txt` - 29 routes.

| Method | URI | Name | Handler | File |
|---|---|---|---|---|
| GET | `/storage/{path}` | - | closure (serves `storage/app/public`) | `routes/web.php:40` |
| GET | `/` | - | closure: `Invitation::first()`, `?guest=CODE` | `routes/web.php:50` |
| GET | `/photobooth` | - | `PhotoboothController@index` | `routes/web.php:78` |
| POST | `/photobooth/capture` | - | `PhotoboothController@store` | `routes/web.php:79` |
| GET | `/api/guests/{code}` | - | `GuestController@show` | `routes/web.php:80` **and** `routes/api.php:10` |
| GET | `/login` | `login` | `Auth\AuthenticatedSessionController@create` | `routes/web.php:85` |
| POST | `/login` | - | `Auth\AuthenticatedSessionController@store` | `routes/web.php:86` |
| POST | `/logout` | `logout` | `Auth\AuthenticatedSessionController@destroy` | `routes/web.php:90` |
| POST | `/api/rsvp/{code}` | - | `GuestController@updateRsvp` | **`routes/api.php:11`** |
| GET | `/v/{slug}` | `wedding.index` | closure, renders `$invitation->theme->svelte_path` | `routes/web.php:123` |

Admin routes (`auth` middleware, all in `routes/web.php:94-120`):

| Method | URI | Name | Handler |
|---|---|---|---|
| GET | `/admin/guests` | `admin.guests.index` | `GuestController@index` |
| POST | `/admin/guests` | `admin.guests.store` | `GuestController@store` |
| POST | `/admin/guests/import` | `admin.guests.import` | `GuestController@import` |
| GET | `/admin/guests/export` | `admin.guests.export` | `GuestController@export` |
| GET | `/admin/guests/print-labels` | `admin.guests.print` | `GuestController@printLabels` |
| PUT | `/admin/guests/{id}` | `admin.guests.update` | `GuestController@update` |
| DELETE | `/admin/guests/{id}` | `admin.guests.destroy` | `GuestController@destroy` |
| DELETE | `/admin/guests-truncate` | `admin.guests.truncate` | `GuestController@truncate` |
| GET | `/admin/gallery` | `admin.gallery.index` | `GalleryController@index` |
| POST | `/admin/gallery` | `admin.gallery.store` | `GalleryController@store` |
| DELETE | `/admin/gallery/{id}` | `admin.gallery.destroy` | `GalleryController@destroy` |
| GET | `/admin/scanner` | `admin.scanner.index` | `Admin\ScannerController@index` |
| POST | `/admin/scanner/checkin` | `admin.scanner.checkin` | `Admin\ScannerController@checkin` |
| GET | `/admin/scanner/stats` | `admin.scanner.stats` | `Admin\ScannerController@todayStats` |
| GET | `/admin/invitation` | `admin.invitation.edit` | `Admin\InvitationController@edit` |
| POST | `/admin/invitation` | `admin.invitation.update` | `Admin\InvitationController@update` |
| GET | `/admin/photobooth` | `admin.photobooth.index` | `PhotoboothController@adminIndex` |
| GET | `/admin/photobooth/print` | `admin.photobooth.print` | `PhotoboothController@printView` |
| DELETE | `/admin/photobooth/{id}` | `admin.photobooth.destroy` | `PhotoboothController@destroy` |

### Answers to the routing questions in AGENTS.md section 1

- **Which `/rsvp` route is live?** Only `POST /api/rsvp/{code}` is registered
  (`routes/api.php:11`). The `web.php` version (`Route::post('/rsvp/{code}')`)
  is **commented out** at `routes/web.php:81`. The "a `/rsvp/{code}` route also
  existed in `web.php`" note in section 1 is correct but it is dead code.
- **Where is `/api/rsvp/{code}` defined?** `routes/api.php`, not `web.php`.
  It is therefore behind the `api` middleware group, and the frontend must hit
  the `/api` prefix.
- **`GET /api/guests/{code}` is declared twice** (once in `web.php:80`, once in
  `api.php:10`). The API-file version wins for `/api/guests/...`; the web-file
  one shadows nothing there but is dead weight. Confirm behaviour in Phase 1
  before deleting it.
- **`/storage/{path}` is a hand-written PHP route**, not a symlink-only setup.
  It serves files straight from `storage/app/public` without auth. Keep it
  working (or replace it with `storage:link`) when uploads move under
  `invitations/{id}/`.
- **The public invitation page is `/`**, not `/{slug}`. `/{slug}` is `GET
  /v/{slug}` (with a `v/` prefix) and renders a theme from the database.
  This collides with the planned section 5.2 map and is a Phase 1 routing
  decision (P1-T5).
- **`/` has no named route and no constraint**, and `/photobooth` is a
  top-level path. Both are captured by the planned `/{slug}` catch-all unless
  the reserved-slug list and route ordering in 5.2 are applied.

---

## 3. Model and query inventory

`grep` over `app/`:

### `Invitation::first()`

| File:line | Context |
|---|---|
| `app/Http/Controllers/Admin/InvitationController.php:16` | `edit()` - reads the single invitation |
| `app/Http/Controllers/Admin/InvitationController.php:44` | `update()` - writes the single invitation |
| `routes/web.php:53` | public `/` closure |

There are exactly **two** live `Invitation::first()` controllers and one in the
route closure. (AGENTS.md K1 is confirmed.)

### Raw-ish model statics

| File:line | Call | Scoping today |
|---|---|---|
| `app/Http/Controllers/GuestController.php:14` | `Guest::latest()` | none |
| `:57-62` | `Guest::count()`, `Guest::where(...)->count()` x5 (stats) | none |
| `:66` | `Guest::whereNotNull('category')` | none |
| `:95` | `Guest::create($validated)` | none |
| `:105` | `Guest::findOrFail($id)` | none |
| `:130` | `Guest::updateOrCreate(...)` (Excel import - K3) | none |
| `:145`, `:218` | `Guest::where('guest_code', $code)->firstOrFail()` | code-keyed, fine for guests |
| `:194` | `Guest::orderBy('name')` (export) | none |
| `:246` | `Guest::findOrFail($id)->delete()` | none |
| `:252` | `Guest::truncate()` (K2 - deletes **all** tenants) | n/a |
| `app/Http/Controllers/GalleryController.php:15,30,42` | `Gallery::orderBy/create/findOrFail` | no `invitation_id` column at all (K4) |
| `app/Http/Controllers/PhotoboothController.php:49,71,89,108,128,141` | `PhotoboothPhoto::with('guest')->latest()`, `count()`, `create()`, `findOrFail()`, print query | table has **no `invitation_id`** |
| `app/Http/Controllers/Admin/ScannerController.php:30` | `Guest::where('guest_code', ...)->first()` | none |
| `:87` | `Guest::where('checkin_status','arrived')` | none |
| `app/Exports/GuestsExport.php:15` | `Guest::orderBy('name')->get()` | none |
| `app/Http/Controllers/WeddingController.php:26` | `Guest::where('invitation_id', $invitation->id)` | scoped by pass-through id |

### `DB::table`

**None** in `app/`. (Backfill migrations in P1-T2 will introduce the first
uses.)

### Dead code found

- `app/Http/Controllers/GuestController copy.php` - a full copy of the
  controller, including its own `Guest::truncate()` at line 104. Not routed,
  but it is a live hazard if anyone greps for "Guest::" and edits the wrong
  file. Should be deleted in Phase 1.
- `routes/web.php:16-35` - the previous `/` closure, commented out.
- `resources/js/Pages/Photobooth copy.svelte`, `Wedding copy.svelte`,
  `Components/Sections/EventSection copy.svelte` - editor leftovers carrying
  hardcoded names (see section 6).

---

## 4. Schema: what actually exists

Live schema dumped to `docs/schema-current.sql`. 15 migrations are applied
(`docs/migrations-list.txt`).

### `invitations`

```
id, user_id (FK users, cascade), theme_id (FK themes, RESTRICT),
slug (unique), title, content (json, NOT NULL), is_active (bool, default 0),
created_at, updated_at
```

Missing versus AGENTS.md 5.6 / P1-T1: no `status`, `package_id`,
`feature_overrides`, `sections`, `active_from`, `active_until`, `event_date`,
`client_*`, `price_agreed`, `payment_status`, `amount_paid`, `paid_at`,
`internal_notes`, `is_demo`. No `packages` table, no `settings` table.

`is_active` exists and is **not** read by the public `/` closure today (it uses
`Invitation::first()` regardless).

### `themes` - **exists, but with a different shape than AGENTS.md assumes**

```
id, name, slug (unique), preview_image (nullable), svelte_path (NOT NULL),
is_premium (bool), created_at, updated_at
```

There is **no `key` column** and **no `is_active` column**. The column is
`slug`, and the theme carries a `svelte_path` string. Section 1 says "`Theme`
(referenced, may not exist as a table yet)" - it does exist, 1 row.
P1-T1 must reconcile this table, not simply create a new one.

### `guests`

```
id, invitation_id (FK invitations, nullable, cascade), name, city, category,
guest_code (unique), quota (int, default 1), is_rsvp (bool), attendance,
message (text), checkin_status enum('not_arrived','arrived'),
checked_in_at, actual_guest_count (int), checked_in_by (varchar), timestamps
```

`invitation_id` already has an FK with cascade - P1-T1 only needs the index
(the FK already creates one) and P1-T10 the NOT NULL.

### `galleries`

```
id, filename, path, order, created_at, updated_at
```

**No `invitation_id`** (K4 confirmed).

### `photobooth_photos`

```
id, guest_id (FK guests, cascade), image_path, message, voice_note_path,
created_at, updated_at
```

**No `invitation_id`**. Tenant must be derived via `guest_id` for the backfill.

### `users`

```
id, name, email (unique), email_verified_at, password, remember_token,
timestamps
```

No `role`, no `invitation_id`. Default `staff` / backfill `admin` is P1-T1/T2.

### Does `Invitation->user` / `Invitation->theme` work?

Yes. `app/Models/Invitation.php:26` and `:31` define both `belongsTo`
relations, and both FKs exist in the schema. `Invitation->guests` (`hasMany`)
also exists. The public `/` closure and `/{slug}` route are the only readers
of `->theme`, and `tests` do not cover them.

---

## 5. Migrations

15 applied, listed in `docs/migrations-list.txt`.

**Do they run on sqlite?** Unverified - see the blocker in
[section 12](#12-p0-t3-test-isolation-status). Structurally, the risk is
`guests.checkin_status`, an **enum** created in
`2026_08_04_014645_add_quota_and_checkin_to_guests_table.php`. SQLite renders
enums as `varchar` + a CHECK constraint, so the column will exist, but enum
comparisons in tests must use strings. `invitations.content` is `json`, fine on
SQLite. There is no `->change()` migration in Phase 0, which is the main thing
to watch (Laravel 12 can alter SQLite columns but enum changes need care).

**`themes` migration defect:** `2025_12_30_021349_create_theme_table.php`
creates `themes` but its `down()` calls `Schema::dropIfExists('theme')` -
the wrong table name, so rollback would leave the table behind. Recorded as
an open question; do not "fix" it until the owner approves, because fixing it
changes a historical migration.

---

## 6. Hardcode inventory (K9, K12)

`grep -E 'Maul|Kahfi|Maulia|Kresno|mk\.png|saweria|logo2|Shangri|Tepian|06 \. 09|2026'`
over `resources/` and `app/`.

**`app/` is clean** - zero matches. Every hardcoded identity string lives in
`resources/`.

### Person names

| File:line | String |
|---|---|
| `resources/views/app.blade.php:8,11,18` | `Wedding Maulia & Kahfi` (title + OG + twitter fallbacks) |
| `resources/js/Pages/Wedding.svelte:187` | `Maulia Nur Rahma` (fallback) |
| `:191` | `Maul` (fallback) |
| `:212` | `M Kahfi Kresnotutuko` (fallback) |
| `:215` | `Kahfi` (fallback) |
| `resources/js/Pages/Welcome.svelte:137,163` | `Maul`, `Kahfi` (literal markup) |
| `resources/js/Components/Sections/ThankSection.svelte:76,120` | `Maul` / `Kahfi` fallbacks |
| `resources/js/Components/LoadingScreen.svelte:88` | `Maul` / `Kahfi` fallback markup |
| `resources/js/Pages/Photobooth.svelte:233,340,377,471,472,476,477` | canvas text + share text |
| `resources/js/Pages/Admin/GuestPrintLabels.svelte:93` | `The Wedding of Maulia & Kahfi` |
| `resources/js/Pages/Admin/PhotoboothPrint.svelte:40,53` | cover names + page header |
| `resources/js/Pages/Admin/GuestIndex.svelte:134` | `const signature = '*Maulia & Kahfi*'` (invite message) |
| `resources/js/Components/Admin/GuestFormModal.svelte:135` | placeholder `Tamu Kahfi, Tamu Maul, ...` |

### Date

| File:line | String |
|---|---|
| `resources/js/Pages/Photobooth.svelte:237,348,381` | `06 . 09 . 2026` |
| `resources/js/Pages/Admin/PhotoboothPrint.svelte:41` | `06 . 09 . 2026` |

(`2026` also appears as a year literal inside those date strings.)

### Assets and branding

| File:line | String |
|---|---|
| `resources/views/app.blade.php:13,20` | `url('/mk.png')` OG image fallback (K12-adjacent) |
| `resources/js/Components/LoadingScreen.svelte:7` | `logoUrl = "/mk.png"` default |
| `resources/js/Pages/Wedding.svelte:86,94` | `logoUrl="/mk.png"` |
| `resources/js/Components/Admin/AdminLayout.svelte:72` | `src="/storage/logo/logo2.png"` |

### Venue

| File:line | String |
|---|---|
| `resources/js/Components/Sections/EventSection copy.svelte:169` | `Wedding Hall | Tepian Rasa` (dead copy file) |
| `:178` | maps URL `https://maps.google.com/?q=Tepian+Rasa+Cirebon` |

### Not found

`saweria` and `Shangri` have **zero matches**. If the owner expects a Saweria
link or a Shangri-La venue string in `ThankSection.svelte` / `EventSection.svelte`,
it is not in this checkout - confirm in P2-T2.

### Music fallback (K12)

The `/music/background.mp3` fallback named in K12 did **not** appear in the
grep for identity strings. It must be confirmed separately during P2-T2 with a
targeted search of `MusicPlayer.svelte` and `Wedding.svelte`; it is not
disproven, merely not part of this inventory's pattern.

---

## 7. Database row counts (live `invitation` DB)

| Table | Rows (`COUNT(*)`) |
|---|---|
| `guests` | 158 |
| `galleries` | 6 |
| `photobooth_photos` | 11 |
| `invitations` | 1 |
| `users` | 3 |
| `themes` | 1 |
| `migrations` | 15 |

Source: `docs/rowcounts2.txt` (exact `COUNT(*)`, not estimates).

### Correction: the backup is complete (an earlier note here was wrong)

An earlier version of this section claimed the Phase 0 backup contained a
stale 10 migrations / 9 photos / 0 themes. **That was wrong.** The figures
came from `information_schema.tables.table_rows`, which for InnoDB is an
**estimate** that lags the real data (`migrations` read 10 but was 15;
`photobooth_photos` read 9 but was 11; `themes` read 0 but was 1).

The backup was verified by actually restoring it into a scratch database
(`invitation_verify`, since dropped) and running exact `COUNT(*)`:

```
guests 158 | galleries 6 | photos 11 | invitations 1
users 3 | themes 1 | migrations 15
```

That matches the live DB exactly, so
`backups/pre-multitenant-20261112.sql` **is** a valid, complete baseline.

`backups/pre-multitenant-20261112-v2.sql` is a re-dump taken during this
session; it is byte-identical to the original except the dump timestamp.

**Lesson for later phases:** never take row counts from
`information_schema.table_rows` in this project's verification steps - it
would silently pass a broken backfill.

---

## 8. Test suite status

- `phpunit.xml` correctly isolates tests: `DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`, `APP_ENV=testing`, array cache/session/mail.
  **Tests cannot reach the `invitation` DB with the current config.**
- There is **no `.env.testing`**. Good - `phpunit.xml` is the single source of
  truth.
- Only two example tests exist before P0-T3: `tests/Feature/ExampleTest.php`
  (asserts `/` returns 200) and `tests/Unit/ExampleTest.php` (`assertTrue(true)`).
- `tests/TestCase.php` was empty - the safety guard was added in P0-T3.
- The suite originally did **not** pass; it is green after P0-T3 (section 12).

---

## 9. Frontend structure

```
resources/js/Pages/Wedding.svelte          (public invitation, ~full page)
resources/js/Pages/Photobooth.svelte
resources/js/Pages/Welcome.svelte
resources/js/Pages/Admin/*                 (GuestIndex, GuestPrintLabels,
                                            PhotoboothPrint, InvitationEditor,
                                            Scanner, Gallery, ...)
resources/js/Components/Sections/*         (Event, Gratitude, Info, Gallery,
                                            RSVP, ThankSection)
resources/js/Components/Admin/AdminLayout.svelte (CSS variables on :root)
resources/js/Components/{Curtain,LoadingScreen,MusicPlayer}.svelte
resources/js/app.js                        (Inertia resolver)
```

`app.js` page resolution was **not** inspected for `eager: true` (K10) in this
audit - that check belongs to P2-T6. `resources/js/Pages/* copy.svelte` files
are dead and contain hardcoded names (section 6).

---

## 10. Contradictions with AGENTS.md

| # | AGENTS.md says | Audit found | Phase impact |
|---|---|---|---|
| C1 | "`Theme` (referenced, may not exist as a table yet)" | `themes` **table and model exist**, 1 row, columns `slug`/`svelte_path`/`is_premium`; no `key`, no `is_active` | P1-T1 must **alter**, not create. P1-T2 "seed `heritage-gold`" must write to `slug` |
| C2 | Public pages: `/` reads `?guest=CODE`; "`/{slug}` would collide" (K5) | Public invitation is `/`; the slug page is `/v/{slug}`; `/photobooth` is top-level | P1-T5 must migrate `/v/{slug}` -> `/{slug}` and keep both old URLs alive |
| C3 | RSVP lives at `/api/rsvp/{code}` "used by the frontend" | Correct, defined in `routes/api.php`; the `web.php` variant is commented out | none, but delete the dead route in P1-T5 |
| C4 | "`galleries` has no `invitation_id`" | Correct | as planned |
| C5 | Implied: `photobooth_photos` can be scoped by `invitation_id` | Table has **no** `invitation_id`; only `guest_id` | P1-T1 adds the column; P1-T2 backfills **via `guest_id`** |
| C6 | `invitations.theme_id` implies themes are required | `theme_id` is `NOT NULL` and FK-restricted | P1-T1 "link X to theme if empty" is moot: it is never empty |
| C7 | Tests are expected to pass | Suite was red; **now green** (6 passed) after P0-T3 | resolved |
| C8 | Section 1 does not mention duplicate `GET /api/guests/{code}` | Declared in both `web.php` and `api.php` | confirm before deleting |
| C9 | Migration rollbacks should work | `create_theme_table` `down()` drops `theme`, not `themes` | open question, do not fix silently |

Every row above is also recorded in `docs/DECISIONS.md`.

---

## 11. Risks for Phase 1

1. **`Guest` has no factory and `RateLimiter` is not used** - P1-T9 needs
   factories for `Invitation`/`Guest`/`Gallery`/`PhotoboothPhoto`; none exist
   today (`database/factories` contains only `UserFactory` unless verified
   otherwise).
2. **`GET /storage/{path}`** serves uploads by path with no tenant check. Once
   files move to `invitations/{id}/`, a guessed path still leaks across
   tenants. Decide in Phase 1/T5 whether to keep it.
3. **`/admin/guests-truncate` is a `DELETE` on a literal path** that precedes
   `/admin/guests/{id}` in the registrar - order is fine today, but the planned
   `resetGuests` keeps a risky action on a GET-less destructive endpoint.
4. **`InvitationController@edit` auto-creates** a default invitation
   (confirmed in the controller; see K1) - it must not after P1-T6.
5. **The stale backup** (section 7) must be re-taken before any migration.

---

## 12. P0-T3 test isolation status - resolved

The isolation **config** was already correct (`phpunit.xml` -> sqlite
`:memory:`). The suite was red for two reasons, both now fixed:

1. `storage/logs/laravel.log` could not be opened for append, so Monolog threw
   `UnexpectedValueException` and masked the real error. This was a
   file-permission problem in the working session, not an application bug; it
   disappeared once the session had full file access.
2. `tests/Feature/ExampleTest.php` failed with `no such table: invitations`
   because it did not use `RefreshDatabase`, so no schema was created against
   `:memory:`. Fixed by adding the trait.

**Current state:** `php artisan test` -> **6 passed (9 assertions)**.

- `tests/TestCase.php` now carries the guard: it throws if the environment is
  not `testing`, or if a non-sqlite connection points at the `invitation`
  database.
- `tests/Feature/TestDatabaseGuardTest.php` covers four cases: real DB
  rejected, non-testing environment rejected, sqlite `:memory:` allowed,
  separate `invitation_test` MySQL DB allowed.
- The guard was also proven end-to-end: forcing `DB_CONNECTION=mysql` /
  `DB_DATABASE=invitation` made the suite abort with
  "Refusing to run tests against the real database 'invitation'". (Note that
  `phpunit.xml` normally overrides process env vars, so this is a second line
  of defence rather than the primary one.)

**Section 5 question answered:** the migrations **do** run on SQLite -
`RefreshDatabase` applies all 15 to `:memory:` without error.
