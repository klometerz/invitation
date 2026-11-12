# PROGRESS

Handoff document. Read this first in a new session, then `AGENTS.md` §0-6 and
the current phase.

Last updated: **2026-11-12** (Phase 2 complete, awaiting the gate).
Branch: `feature/multi-tenant`.

Git state: **Phase 0 + Phase 1 committed** (`b379f14` and earlier). **Phase 2 is
uncommitted** — it is waiting for the owner's review/approval.

---

## Run locally

```powershell
$env:PATH = 'C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\composer;' + $env:PATH
```

`.env` now targets the local `invitation` DB, so `php artisan migrate` works
with no extra flag. `npm` must be run as `cmd /c "npm run build"`.

---

## Phase 0 - Safety and audit

| Task | Status | Commit |
|---|---|---|
| P0-T1 Git baseline | DONE | `ba6d81e` |
| P0-T2 Backups | DONE | `b9451ca` |
| P0-T3 Test isolation | DONE | `b06b289` |
| P0-T4 Audit | DONE | `2e51334` |
| P0-T5 Docs | DONE | `2e51334` |

---

## Phase 1 - Multi-tenant core (approved)

| Task | Status | Notes |
|---|---|---|
| P1-T1..P1-T10 | DONE (commit `b379f14`) | 25 migrations, 10 factories/tests, 44 routes. |
| Phase 1 gate | **APPROVED** by owner 2026-11-12 | Committed as one commit. |

Related fix after approval: the owner corrected `.env` to the local DB, so the
`--env=local` workaround is gone.

---

## Phase 2 - Dynamic content, sections, themes

**All tasks implemented. 67 tests / 221 assertions green. `npm run build` green.
2 migrations applied locally (22 -> 27 pending? see below).**

| Task | Status | What was built |
|---|---|---|
| P2-T1 Feature registry | DONE | `config/features.php` (8 keys + labels). `Invitation::featureKeys/featureFlags/hasFeature/limit`. Middleware `feature:{key}` (404 public, 403 for logged-in/JSON). Gated: photobooth page+capture, scanner+export (`guestbook`), print labels (`qr_cards`), admin photobooth grid (`photobooth`), memory book print (`memory_book`), gallery admin (`gallery`). |
| P2-T2 De-hardcode | DONE | `App\Support\InvitationPresenter` is now the only source of names/dates/logo/thanks/tips. `LoadingScreen`, `Curtain`, `ThankSection`, `Photobooth`, `PhotoboothPrint`, `GuestPrintLabels`, `GuestIndex`, `AdminLayout`, `app.blade.php` no longer hardcode a couple, `/mk.png`, `logo2.png`, `#KALItech`, bank numbers or the QRIS file. Music fallback removed. New content fields: `hero.hashtag`, `logo_url`, `loading_tips[]`, `thanks{bank_accounts[],qris_image_url}`, `invite_message_template`. Editor UI added. |
| P2-T3 Sections | DONE | `App\Support\SectionRegistry` + `Invitation::resolvedSections()` (welcome forced first, unknown/duplicate dropped, feature-disabled sections removed). Editor with checkboxes + up/down reorder. Defaults for new invitations. |
| P2-T4 Photobooth/print dynamic | DONE | Photobooth receives `meta` and draws `coupleNames`/`dateText` on both frames; share title/text from presenter. `PhotoboothPrint` cover + page headers from presenter. |
| P2-T5 SEO + DTOs | DONE | `InvitationPresenter::seo()` per invitation, absolute URLs; `og:image` = hero -> gallery -> platform setting -> none. Public props are whitelisted arrays (`invitation{id,title,slug}`, `guestData` 7 whitelisted keys, `galleries[{id,path}]`) - K6 fixed. |
| P2-T6 Theme system | DONE | `resources/js/Themes/heritage-gold/{Index.svelte,sections/*}` (moved, not rewritten). Loader `Pages/Invitation/Show.svelte` lazy-imports by key. `app.js` switched to lazy `import.meta.glob` and the debug `console.log`s removed. Dead `* copy.svelte` files deleted. Build emits per-page chunks (51 assets). |
| P2-T7 Second theme | DONE | `Themes/minimal-light/Index.svelte` (single light scroll page, same contract). Seeded via migration, selectable in the invitation form. |
| P2-T8 Invite template | DONE | `content.invite_message_template` with `{name} {link} {quota} {couple}`, default template in the presenter, editor field + live preview, `GuestIndex` renders it. |
| P2-T9 Upload hardening | DONE | Explicit mime/size rules + Indonesian messages for hero, music, mempelai photos, logo, QRIS, gallery. Per-tenant directories kept. `docs/DEPLOY.md` documents `php.ini` values. |

### New tests

- `tests/Feature/FeatureGatingTest.php` (9 tests) - matrix T16 + each gated route.
- `tests/Feature/SectionsTest.php` (9 tests) - defaults, order, filtering, unknown keys.
- `tests/Feature/PresenterTest.php` (12 tests) - names order, dates, OG fallback, invite template, thanks, whitelisted payload (T20).

### Final cleanup

- Deleted the dead `Pages/Welcome.svelte` (no route/import, contained client names).
- `MusicPlayer.svelte` no longer defaults to `/music/background.mp3` (K12):
  it renders nothing to play when no music is uploaded.
- Remaining `Maul/Kahfi/mk.png/logo2/saweria/KALItech/06 . 09/background.mp3`
  matches under `resources/` and `app/`: **0** (was 47 at audit time).

### Deliberately NOT done (later phases)

- Photobooth base64 hardening and rate limits (P3-T4).
- Packages GUI, settings GUI, landing page, expired page, dashboard (Phase 3).
- `minimal-light` visual polish (`TODO(owner)`).
- Verses/announcement *default copy* now only appears when the section is
  enabled (see `DECISIONS.md` P2-A); i18n packs are still backlog.

---

## Open questions for the owner

1. **Commit strategy for Phase 2** - the diff is uncommitted. One commit, or
   one-per-task?
2. **Theme of the live wedding** - `maul-kahfi` still points at theme
   `aurum-noctis` (`svelte_path = Wedding`). Both `heritage-gold` and
   `minimal-light` exist now. Repoint it in P3 or leave as is?
3. **Owner decision #2** - does `maul-kahfi` stay public as a portfolio piece?
4. **Branding** - `settings` is empty, so the admin sidebar shows an initial
   letter and OG images have no platform logo until P3-T5.
