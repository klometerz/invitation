# PROGRESS

Handoff document. Read this first in a new session, then `AGENTS.md` §0-6 and
the current phase.

Last updated: **2026-11-12** (Phase 1 complete, awaiting the gate).
Branch: `feature/multi-tenant` — **changes are uncommitted on purpose: the
owner asked to review before any commit.**

---

## How to run things locally

```powershell
$env:PATH = 'C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\composer;' + $env:PATH
```

- Any **DB‑touching** artisan command needs `--env=local` (the tracked `.env`
  is a production copy). `npm` must be run as `cmd /c "npm run build"`.
- Tests need no env flag: `phpunit.xml` forces sqlite `:memory:`.

---

## Phase 0 - Safety and audit

| Task | Status | Commit |
|---|---|---|
| P0-T1 Git baseline | DONE | `ba6d81e` |
| P0-T2 Backups | DONE | `b9451ca` |
| P0-T3 Test isolation | DONE | `b06b289` |
| P0-T4 Audit | DONE | `2e51334` |
| P0-T5 Docs | DONE | `2e51334` |

Backup verified by restoring it into a scratch DB (158/6/11/1/3/1/15).

---

## Phase 1 - Multi-tenant core

**All tasks implemented. 37 tests / 146 assertions green. `npm run build` green.
Nothing committed yet.**

| Task | Status | What was built |
|---|---|---|
| P1-T1 Schema | DONE | 7 additive migrations: `packages`, `settings`, invitation lifecycle columns, additive `themes` columns, `users.role`+`invitation_id`, `galleries.invitation_id`, `photobooth_photos.invitation_id`. `guests` already had its FK. SQL reviewed with `--pretend` (`docs/migrate-pretend-p1t1.txt`). |
| P1-T2 Backfill | DONE | `2026_11_12_110000_backfill_multi_tenant_data.php`: chunked raw `DB::table()` updates, derived photos via `guest_id`, users→`admin`, invitation→`active` + `event_date` 2026-09-06, seeded `heritage-gold` + Basic/Plus/Premium (`price = null`). Verified 0 NULL tenants, totals unchanged. |
| P1-T3 Tenant core | DONE | `App\Support\TenantContext` (singleton), `App\Models\Scopes\TenantScope` (fails closed with `1 = 0`), `App\Models\Concerns\BelongsToInvitation` (auto-fills and throws), applied to Guest/Gallery/PhotoboothPhoto. `App\Exceptions\TenantContextMissingException`. |
| P1-T4 Middleware/roles | DONE | `tenant.public`, `tenant.guestcode`, `tenant.admin`, `tenant.selected`, `role:admin,staff` aliases; `InvitationAccess` lifecycle helper; `User::isAdmin/isStaff/isClient`; `is_active` guard. |
| P1-T5 Routing | DONE | 44 routes; `/{slug}` declared last and constrained; reserved slugs in `App\Support\SlugRules`; legacy `/?guest=`, `/photobooth?guest=` redirects; `/v/{slug}` 301; admin split into no-tenant and tenant groups; staff limited to scanner. |
| P1-T6 Controllers | DONE | K1 (`Invitation::first` gone, editor never auto-creates, null-safe merge), K2 (`resetGuests` with slug confirmation), K3 (import matches `(invitation_id, name, city)`), K4 (gallery/photobooth/exports scoped). Uploads under `invitations/{id}/…`. Deleted `GuestController copy.php`. |
| P1-T7 Shared props | DONE | `auth.user{name,role}`, `tenant{id,title,slug,features}`, `invitations` (admin), `flash`; `AdminLayout` event switcher + role/feature-filtered nav + flash→toast. |
| P1-T8 Invitations/Users admin | DONE | `/admin/invitations` list + create/edit/duplicate/archive/switch; `/admin/users` create/edit/reset password/deactivate. New pages `InvitationIndex.svelte`, `InvitationForm.svelte`, `UserIndex.svelte`, `ResetGuestsModal.svelte`, `Pages/Empty.svelte`. |
| P1-T9 Isolation tests | DONE | `tests/Feature/TenantIsolationTest.php` rows T1-T14, `TenantScopeTest`, `TenantContextTest`, plus 5 factories. |
| P1-T10 Tighten schema | DONE | `2026_11_12_130000_make_tenant_columns_not_null.php`, pretend reviewed, NULL pre-check inside, skipped on SQLite. |

### Extra migration not in the plan

`2026_11_12_120000_add_is_active_to_users_table.php` — needed for P1-T8
"deactivate". See `DECISIONS.md` P1-D.

### Deliberately NOT done (later phases)

- Expired page is HTTP 410, not a styled page (P3-T2).
- `config/features.php`, `feature:{key}` middleware and real limit enforcement
  (P2-T1/P3-T3). Phase 1 only ships `Invitation::featureFlags()/hasFeature()/limit()`
  and uses them for the shared `tenant.features` prop.
- Public Inertia props still send raw `Invitation`/`Guest` models (K6, P2-T5).
- Hardcoded names/dates/logo still present (K9, P2-T2).
- `app.js` still eager-loads pages (K10, P2-T6).
- `/admin` has no dashboard route yet (P3-T8), so it 404s. Login sends the
  owner to `/admin/guests` and staff to `/admin/scanner`.
- `resources/js/Pages/* copy.svelte` leftovers were not deleted.

---

## Open questions for the owner

1. **Commit strategy** — the whole Phase 1 diff is uncommitted. Review it, then
   either approve the one-commit-per-task plan or ask for a single commit.
2. **Owner decision #2** (AGENTS §11) — does `maul-kahfi` stay `active` as a
   portfolio piece? Right now it is `active` and is the `/` showcase.
3. **Theme mapping** — the existing wedding still points at theme `aurum-noctis`
   (`svelte_path = Wedding`); a separate `heritage-gold` row was seeded. P2-T6
   should decide whether the live wedding is repointed to `heritage-gold`.
