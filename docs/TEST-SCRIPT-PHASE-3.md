# TEST SCRIPT - PHASE 3 (commercial layer)

Manual checks for the owner. Phase 3 turned the system into something you can
sell: dashboard, packages, settings, landing page, demo, lifecycle and limits.

**Before you start:** Laragon running, `http://invitation.test`, logged in as
the owner. The demo invitation already exists at `/demo`.

---

## 1. Dashboard

1. Open `http://invitation.test/admin`.
2. Expect: cards for Aktif / Draft / Berakhir / Arsip, Belum lunas, and
   "Berakhir ≤ 7 hari"; two lists (acara 30 hari ke depan, pembayaran belum
   lunas) and quick action buttons.
3. Click an event name in a list → it switches the active event.

## 2. Settings

1. Open **Settings**. Fill:
   - Nama Usaha: (your business name)
   - Nomor WhatsApp: `628xxxxxxxxxx` (your real number)
   - Instagram URL, Footer text
   - Upload a platform logo
2. Save.
3. Expect: the admin sidebar logo becomes your logo (instead of a plain
   letter), and the "Nama Usaha" shows under "Admin Panel".
4. Leave a field empty and save again → it is cleared, not restored to a
   default.

## 3. Packages

1. Open **Packages**. Three placeholders exist: Basic / Plus / Premium, all
   with price **null**.
2. Edit **Basic**: uncheck *Photobooth* and *Memory book*, set
   `Maks. tamu` = 100, set a price. Save.
3. Expect the list shows "X / 8" features and the price.
4. Leave the price empty on another package → the list shows "Hubungi kami".
5. A package in use by an invitation shows "N undangan" in "Dipakai".

## 4. Limits are enforced

1. Create a test invitation on the **Basic** package (100-guest limit).
2. Add guests until you reach 100 → the next "Tambah Tamu" shows a clear
   error naming the limit.
3. Import an Excel with more rows than the remaining quota → the success
   message reports how many rows were **skipped**, and nothing is half-written.
4. On a package with photobooth limit 1, take 2 photos as the same guest →
   the second is rejected with a message.
5. Gallery: try uploading more images than `max_gallery_images` → a single
   error, and **no** images are saved.

## 5. Landing page

1. Open `http://invitation.test/` in a private window.
2. Expect: hero, "Cara kerjanya" (6 steps), Paket (from the packages you
   edited), FAQ, footer with Syarat & Ketentuan / Kebijakan Privasi.
3. The "Konsultasi via WhatsApp" button opens wa.me with your number and a
   prefilled message. If you clear the number in Settings, the button
   **disappears** (it is never invented).
4. July: after `demo:reset`, a "Lihat demo" button appears.
5. Packages with a null price show "Hubungi kami"; packages marked private
   (`is_public = false`) do **not** appear.

## 6. Legal pages

1. Open `/terms` and `/privacy`.
2. Expect a visible amber notice: `TODO(owner): legal review before publishing`.
3. Do not publish until you have replaced the placeholder text.

## 7. Demo

1. Run `php artisan demo:reset` (or ask me to).
2. Open `/demo`.
3. Expect: a demo invitation with an invented couple, 4 gallery images, a
   bottom banner "Undangan contoh — data fiktif".
4. Open `/demo` in a private window: still works (it is `active`).
5. Confirm the demo has one guest already checked in (open `/admin/scanner`
   on the demo event and scan/type that guest's code → "sudah check-in").
6. Run `demo:reset` twice: no duplicates appear.

## 8. Lifecycle

1. Edit an invitation with **status Aktif** and set `Aktif Sampai` to
   yesterday. Save.
2. Open its public URL in a private window → a friendly
   **"Undangan sudah berakhir"** page (HTTP 410 in the Network tab).
3. Try an RSVP from a guest link of that invitation → rejected (403).
4. Open `/admin/invitations` → the row now shows **Berakhir**, with an
   **Aktifkan** button. Click it → status Active, `active_from` set, and
   `active_until` computed from the package's "Hari aktif setelah acara".
5. Try to change an **Archived** invitation back to Active → validation error
   (archived is terminal by design).
6. On the command line: `php artisan invitations:expire --dry-run` lists what
   would change without changing it.

## 9. Hardening (rate limit / upload safety)

1. Open a guest link and submit RSVP 7 times quickly (use two tabs) → after
   a few attempts you get "Too Many Requests" (429).
2. Try logging in with a wrong password 6 times → login is rate limited.
3. Photobooth: the server now re-encodes every photo to JPEG. Take a normal
   photo → it saves fine and the file endswith `.jpg` in
   `storage/app/public/invitations/{id}/photobooth/`.
4. Check-in as a staff account → the "checked in by" column shows the staff
   name, never a name typed by the browser.

## 10. Memory book

1. Open an invitation with photobooth photos → `/admin/photobooth/print`.
2. Expect the cover with the couple names + date, then photo pages.
3. With more than 200 photos you get "Bagian 1 dari N" and
   Sebelumnya/Berikutnya buttons instead of one enormous page.

## 11. Feature gating on an invitation without photobooth

1. Assign an invitation the **Basic** package (photobooth unchecked).
2. `/{slug}/photobooth?guest=CODE` → **404**; no photobooth banner on the
   invitation; `/admin/photobooth` → **403**.
3. The sidebar hides Photobooth for that event.

---

## What to report back

- Any error message that is not in Indonesian, or leaks a raw exception.
- Any place where a client could see another client's data or branding.
- Anything on `/` that looks broken on a phone (it must stay lightweight).

If everything passes, reply **"Phase 3 approved"** and Phase 4 (deploy
readiness) starts.
