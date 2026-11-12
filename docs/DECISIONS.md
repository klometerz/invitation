# DECISIONS

Non-obvious choices and their reasons. Newest first. Every entry needs: what
was decided, why, and who decided (owner or agent).

---

## D0-1 - Work on `feature/multi-tenant` with one commit per task

**Decided by:** owner (asked in session, 2026-11-12), then resolved.
**History:** the branch could not be created at first because the session's
file sandbox denied writes under `.git/refs`. The owner initially chose to
stay on `v3` without commits. Once the session had full file access the branch
was created normally, so the original rule (§2.4) applies again.
**Current state:** on branch `feature/multi-tenant`, committing one task per
commit.

---

## D0-2 - (superseded) Sandbox write restrictions

**Status:** no longer applicable.
The session now has unrestricted file access, so the earlier constraints
(`tests/`, `storage/logs/`, `app/` unwritable) are gone. P0-T3 was completed
afterwards. Recorded only so the audit history makes sense.

---

## D0-3 - `.gitignore` additions

**Decided by:** agent.
**Decision:** add `/backups`, `/storage/app/public/*` with a `!/storage/app/public/.gitignore`
re-include, and keep `AGENTS.md` ignored.
**Why:** §2.1 requires `backups/` to be git-ignored and uploads must never be
committed. Only the tracked placeholder `.gitignore` files stay in git.

---

## D0-4 - Backup verified complete (an earlier alarm was wrong)

**Decided by:** agent; owner informed.
**Context:** the audit first reported the Phase 0 dump as stale (10 migrations
/ 9 photos / 0 themes) by reading `information_schema.tables.table_rows`.
That column is a **lagging InnoDB estimate**, not a count.
**Verification:** the dump was restored into a scratch database
`invitation_verify` and counted exactly: guests 158, galleries 6,
photobooth_photos 11, invitations 1, users 3, themes 1, migrations 15 - an
exact match with the live `invitation` DB. The scratch DB was then dropped.
**Decision:** `backups/pre-multitenant-20261112.sql` is a valid, complete
baseline. No re-dump is required before P1-T1.
**Rule for later phases:** verification steps must use exact `COUNT(*)`,
never `information_schema.table_rows`.

---

## C1 (from AUDIT) - `themes` already exists with a different shape

**Decided by:** pending owner.
**Context:** AGENTS.md §1 says `Theme` "may not exist as a table yet". It does:
`id, name, slug (unique), preview_image, svelte_path, is_premium, timestamps`.
There is no `key` and no `is_active`.
**Impact:** P1-T1 must ALTER, not CREATE. P1-T2 seeds by `slug`, not `key`.
`themes` is also referenced by `invitations.theme_id` (NOT NULL, FK).
**Question for the owner:** keep the existing columns and add `key`/`is_active`
(additive), or migrate `slug` to `key` and drop `svelte_path`?
**Recommendation:** additive. Add `key` (unique, backfilled from `slug`), add
`description`, add `is_active default true`; keep `svelte_path` until P2-T6
decides how themes are resolved.

---

## C2 (from AUDIT) - public URL is `/` and `/{slug}` is `/v/{slug}`

**Decided by:** pending owner.
**Context:** `GET /` renders the invitation; `GET /v/{slug}` renders a theme
from the DB. `GET /photobooth` is top-level.
**Impact:** §5.2 assumes `/{slug}` is the invitation URL. P1-T5 must introduce
`/{slug}`, keep `/v/{slug}` and `/` working, and reserve `photobooth`, `v`,
`api`, `admin`, `storage`, `login`, etc.
**Question for the owner:** may `/v/{slug}` be retired (301 to `/{slug}`), or
must it stay forever because links were shared with it?

---

## C3 (from AUDIT) - RSVP endpoint location

**Decided by:** agent (fact).
`POST /api/rsvp/{code}` is registered in `routes/api.php:11`. The `web.php`
variant at `routes/web.php:81` is commented out. Nothing to decide; recorded
so nobody re-adds a duplicate.

---

## C5 (from AUDIT) - `photobooth_photos` has no `invitation_id`

**Decided by:** agent (fact).
The table only has `guest_id` (FK, cascade). P1-T1 adds the column, and
P1-T2 must backfill it **via guest_id**, not directly. Any photo whose guest
row is missing will have to be reported, not guessed.

---

## C9 (from AUDIT) - historical migration defect in `create_theme_table`

**Decided by:** pending owner.
**Context:** `database/migrations/2025_12_30_021349_create_theme_table.php`
has `down(): Schema::dropIfExists('theme')` - wrong table name.
**Decision:** NOT fixed. Editing an already-applied migration is a schema
change that requires a backup and is out of Phase 0 scope.
**Recommendation:** leave it. It only matters if someone rolls back that one
migration, which the rules already forbid outside a freshly written migration.

---

## D0-5 - Laravel 12 instead of "Laravel 11+ style"

**Decided by:** agent (fact).
`AGENTS.md` §1 says "Laravel (11+ style `bootstrap/app.php`)". The installed
version is **12.44.0**. The bootstrap layout is the same, so the task list is
unaffected. Recorded to avoid confusion in later phases.

---

## D0-6 - Test isolation strategy (pending verification)

**Decided by:** agent, pending a working test run.
`phpunit.xml` already sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`,
and there is no `.env.testing`. That is the safest isolation and needs no
change. A separate `invitation_test` MySQL database is NOT needed unless a
phase requires MySQL-specific behaviour (enum/JSON differences in Phase 1
could make this worth revisiting).
