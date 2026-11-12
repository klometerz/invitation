# TEST SCRIPT - PHASE 2 (dynamic content, sections, themes)

Manual checks for the owner, in the browser. Phase 2 removed the hardcoded
names/dates/logo and made the page layout configurable per invitation.

**Before you start:** Laragon running, `http://invitation.test` open, logged in
as the owner. Phase 2 is on the working tree (not committed yet), assets are
already built.

---

## 1. Nothing broke on the original wedding

1. Open `http://invitation.test/` in a private window.
2. Expect: redirect to `/maul-kahfi`, curtain opens, loading screen shows,
   then the invitation.
3. The loading screen now shows **the couple's own names and the event date**
   from the invitation (not a fixed `6 SEPTEMBER 2026`), and the logo area
   shows the couple names because no logo is uploaded.
4. Open DevTools → Network → JS: the public page should load several small
   chunks (theme, sections), not one giant bundle that also contains
   `Admin/*` pages.
5. Scroll through: verses, event, info, gallery, RSVP, thanks. Bank accounts
   no longer show a fixed Mandiri/BCA list; the gift block is **hidden**
   because `content.thanks` is empty. That is expected.

## 2. Sections: order and toggles

1. Admin → **Invitations** → **Edit** on `maul-kahfi`.
2. In **Bagian Undangan**, reorder: move *Terima kasih* above *Acara* with ↑.
3. Uncheck **Galeri**. Save.
4. Open `/maul-kahfi`: the gallery section is gone; *Terima kasih* now appears
   before *Acara*.
5. Go back, re-check **Galeri**, restore the original order, save.

## 3. Sections removed when the package feature is off

1. Edit `maul-kahfi` → set **Paket** to `Basic` (its features are placeholders,
   all on for now) → save.
2. If you want to prove filtering, open **Invitations → Edit**, and use a
   second invitation (see Phase 1 script) with a package/`feature_overrides`
   that disables `gallery`. Then gallery disappears from its page even if the
   section is enabled.
3. Restore.

## 4. Photobooth feature gate

1. On an invitation whose package has **photobooth** disabled:
   - `/{slug}/photobooth?guest=CODE` → **404**
   - the "Buka Photobooth" banner does **not** appear for a checked-in guest
   - POST capture returns 403 (JSON)
2. With photobooth enabled: page opens, banner appears after check-in.

## 5. Themes

1. Admin → Invitations → Edit an invitation → **Tema** dropdown now lists
   `Aurum Noctis`, `Heritage Gold`, `Minimal Light`.
2. Pick **Minimal Light** and save.
3. Open that invitation: it renders as a light single-scroll page with the
   same data (hero names/date, event cards, gallery, RSVP, thanks).
4. Switch back to **Heritage Gold** → the original gold/black design returns.
5. `minimal-light` is a contract proof; visual polish is yours.

## 6. Invite message template

1. Admin → **Invitation** (content editor) → scroll to
   **Branding & Pesan Undangan**.
2. The **Template pesan undangan** field shows the default text with
   `{name}`, `{link}`, `{quota}`, `{couple}`.
3. Edit it, e.g. `Halo {name}, ini undangan kami: {link} — {couple}`.
4. The **Pratinjau** box updates live with sample values.
5. Save, then open **Guests** → **Copy pesan** on any guest.
6. Paste into a text editor: the message uses your template with the real
   name/link. The signature follows the invitation's own couple names.

## 7. Branding fields

1. In the content editor:
   - Upload a **Logo undangan** → it appears on the loading screen instead of
     the couple names.
   - Upload a **Gambar QRIS** and add two **Rekening hadiah** rows.
2. Save, open the invitation: the thanks section now shows the bank rows and
   the QRIS image.
3. Remove all bank rows and the QRIS, save → the gift block disappears
   entirely (no placeholders).
4. Add **Tips saat loading** (one per line) → they rotate on the loading
   screen. Empty = no tip line at all.
5. Add a **Hashtag** → it appears at the bottom of the curtain.

## 8. SEO / share preview

1. Open `view-source:http://invitation.test/maul-kahfi`.
2. `<title>` is the invitation's own title, `og:title`/`og:description` match,
   and `og:image` is the hero background (or the first gallery image).
3. For an invitation with **no** hero and **no** gallery, `og:image` is
   omitted entirely — it must never point at another wedding's photo.
4. Paste the link in WhatsApp: the preview uses these values.

## 9. Upload limits

1. Try uploading a gallery image larger than 5 MB → Indonesian error
   ("Setiap foto maksimal 5 MB.") and **nothing** is saved.
2. Try uploading a `.pdf` as a gallery image → rejected as a non-image.
3. Try music larger than 20 MB → rejected.
4. Check the file location after a successful upload:
   `storage/app/public/invitations/{id}/gallery/...`

## 10. Feature toggles on admin screens

1. On an invitation with **guestbook** disabled: `/admin/scanner` and
   `/admin/guests/export` return **403**.
2. With **qr_cards** disabled: `/admin/guests/print-labels` → 403.
3. With **memory_book** disabled: `/admin/photobooth/print` → 403.
4. With **photobooth** disabled: `/admin/photobooth` → 403.
5. The sidebar hides the matching menu items.

## 11. Quick regression

1. RSVP from a guest link still works.
2. Scanner check-in still works.
3. Photobooth capture still works for a checked-in guest.
4. Guest import/export still work.
5. Print labels still show the event title (now from the invitation, not a
   fixed "The Wedding of Maulia & Kahfi").

---

## What to report back

- Any screen showing another wedding's name/date/logo/bank account.
- Any 500 error, with the URL.
- Anything that looks visually broken on `maul-kahfi` compared to before.

If everything passes, reply **"Phase 2 approved"** and Phase 3 starts.
