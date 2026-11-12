# TEST SCRIPT - PHASE 1 (multi-tenant core)

Manual checks the owner can run in the browser. Phase 1 changed how every
screen decides *which* wedding it is working on, so most steps are about
switching between two events and confirming they never mix.

**Before you start:** the code is on branch `feature/multi-tenant` and the
local DB already has all Phase 1 migrations applied. Start Laragon and open
`http://invitation.test`.

> If the site answers with the production database instead of your local one,
> the vhost is loading the wrong env. Local artisan commands need
> `--env=local`; the web server keeps using `.env` as before.

---

## 1. Existing wedding still works (no regression)

1. Open `http://invitation.test/` in a private window.
2. Expect: you are redirected to `/maul-kahfi` and the invitation opens exactly
   as before (curtain, hero, music, sections).
3. Scroll through: events, gallery, RSVP, thanks. Nothing should look
   different from before Phase 1.

## 2. Legacy links (printed QR codes)

1. Open `http://invitation.test/?guest=arivi-sucy-lskj2`.
2. Expect: redirect to `/maul-kahfi?guest=arivi-sucy-lskj2`, and the page greets
   the guest by name.
3. Open `http://invitation.test/photobooth?guest=arivi-sucy-lskj2`.
4. Expect: redirect to `/maul-kahfi/photobooth?guest=arivi-sucy-lskj2`.
5. Open `http://invitation.test/?guest=kode-ngawur`.
6. Expect: **404**. Open `/photobooth?guest=kode-ngawur` → **404** too.
7. Open `http://invitation.test/v/maul-kahfi`.
8. Expect: permanent (301) redirect to `/maul-kahfi` — the address bar may
   briefly show `/v/maul-kahfi`.

## 3. Create a second wedding

1. Log in at `http://invitation.test/login` as the owner.
2. Open **Invitations** in the sidebar.
3. Expect: a table with `Wedding of Maulia & Kahfi` (Aktif) and a **+ Acara Baru**
   button.
4. Click **+ Acara Baru**, fill: title `Wedding Test B`, slug `wedding-test-b`,
   theme `Heritage Gold`, status `Aktif`, event date = next month.
5. Save. Expect: back on the list with **two** rows.
6. Try to create another with slug `admin` → expect a red validation error
   ("Slug ini tidak tersedia" or similar) and **no** row created.
7. Try slug `Wedding Test` (space/uppercase) → expect a validation error about
   lowercase/hyphen format.

## 4. Add guests to each wedding

1. In the list, click **Buka** on `Wedding of Maulia & Kahfi`.
2. Header switcher now shows the wedding name. Open **Guests** → the original
   list (158 guests) appears.
3. Add a guest `Tamu A` there.
4. Go back to **Invitations** → **Buka** on `Wedding Test B`. Open **Guests**.
5. Expect: an **empty** list (0 guests), not 159. Add `Tamu B`.

## 5. Switching events never mixes data

1. While on `Wedding Test B`, open the **event switcher** in the header and pick
   `Wedding of Maulia & Kahfi`.
2. Expect: the page reloads and the guest list shows the original 158 + `Tamu A`,
   and **not** `Tamu B`.
3. Switch back to `Wedding Test B` → only `Tamu B`.
4. Open **Gallery** for each event: images must differ (B starts empty).

## 6. Cross-access by URL

1. While working in `Wedding Test B`, copy the URL of one of A's guests.
   To get it: switch to A, open Guests, click **Edit** on `Tamu A`; note the id
   in the network request is not visible in the UI, so instead use this shortcut:
   - In A's guest list, click **Copy link** on `Tamu A`. Paste it in a new tab.
   - Expect: `/maul-kahfi?guest=tamu-a-xxxxx` shows the guest name.
2. Now open the same link with `maul-kahfi` replaced by `wedding-test-b`, i.e.
   `/wedding-test-b?guest=tamu-a-xxxxx`.
3. Expect: the page loads **without** a personalised greeting (no name), because
   that guest code does not belong to B. It must never show A's guest.
4. Open `http://invitation.test/maul-kahfi` while logged out (private window) →
   works. Open `http://invitation.test/wedding-test-b` → works.
5. Archive B: Invitations → **Arsip** on B. Open `/wedding-test-b` in the private
   window → **404**.
6. Switch to B while it is archived is not offered; re-open it via Edit → set
   status back to `Aktif` if you need it again.

## 7. Guest reset is scoped and needs confirmation

1. Switch to `Wedding Test B`, open **Guests**.
2. Click the danger button (**Kosongkan Semua Data** / delete-all).
3. Expect: a modal asking you to type the slug `wedding-test-b`.
4. Type the wrong slug → the **Hapus semua tamu** button stays disabled.
5. Cancel. Confirm `Wedding of Maulia & Kahfi` still has its guests (switch to
   it and look).
6. Try again on B, type `wedding-test-b` correctly, confirm.
7. Expect: B's list is empty; switch to A → A's guests are intact.

## 8. Import isolation

1. On `Wedding Test B`, import an Excel/CSV with a name that already exists in A
   (e.g. `Tamu A`, city `Jakarta`).
2. Expect: B gains that guest.
3. Switch to A and search for `Tamu A`: it must still have A's original city and
   quota, unchanged.

## 9. Staff accounts

1. Open **Users**. Create `Petugas B` with email `petugas-b@example.com`,
   password of at least 8 characters, assigned to `Wedding Test B`, role `Staff`.
2. Log out, log in as `petugas-b@example.com`.
3. Expect: you land on **/admin/scanner**, and the sidebar shows only
   **Scanner** (no Guests, Gallery, Invitations, Users).
4. Manually open `/admin/guests`, `/admin/gallery`, `/admin/invitations`,
   `/admin/users`.
5. Expect: **403 Forbidden** on all four.
6. Log back in as the owner, open **Users**, press **Nonaktifkan** on Petugas B.
7. Log out and try to log in as Petugas B again → expect "Akun ini sudah
   dinonaktifkan." and no session.

## 10. Scanner stays bound to its own wedding

1. Log in as the owner, switch to `Wedding Test B`, open **Scanner**.
2. Scan or type A's guest code (`arivi-sucy-lskj2`).
3. Expect: "QR Code tidak ditemukan" (404) and A's guest is **not** marked as
   arrived (switch to A and check the guest still says Belum Hadir).

## 11. Drafts and preview

1. Invitations → **Edit** on `Wedding Test B`, set status to `Draft`, save.
2. Open `/wedding-test-b` in a private window → **404**.
3. While still logged in as the owner, open `/wedding-test-b` → the page opens
   (admin preview).
4. Set it back to `Aktif`.

## 12. Duplicate

1. Invitations → **Duplikat** on `Wedding Test B`.
2. Expect: a new row `Wedding Test B (Copy)`, status Draft, new slug, same theme
   and package, and **no** guests.
3. Open its Edit form: payment fields are reset (Belum bayar, 0).

## 13. Exports and print lists

1. Switch to `Wedding Test B`, open Guests → **Export Excel**.
2. Expect: the filename contains `wedding-test-b`. Open the file: it contains
   only B's guests, never A's.
3. Print labels from B → only B's names appear.
4. Photobooth print page for B → no photos from A.

## 14. Quick regression on the old features

1. On `maul-kahfi`, submit an RSVP from a guest link → success message, and the
   row updates in Guests.
2. Check in a guest with the scanner as the owner → the guest becomes Hadir.
3. Upload one gallery image on `Wedding Test B` → it lands under
   `storage/app/public/invitations/{id}/gallery/`.
4. Open the public photobooth for a **checked-in** guest → capture works.

---

## What to report back

- Any screen that shows another wedding's data (release blocker).
- Any 500 error, with the URL.
- Anything that looks different from before Phase 1 on `maul-kahfi`.

If everything passes, reply **"Phase 1 approved"** and Phase 2 starts.
