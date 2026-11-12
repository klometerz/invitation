# DEPLOY - upload limits and server requirements

This file documents the server settings the upload features depend on
(P2-T9). The full deploy procedure is P4-T1.

---

## Required `php.ini` values

Photos and music are uploaded through the admin panel, so PHP's upload limits
must be at least as large as the app's validation limits:

| Directive | Minimum | Why |
|---|---|---|
| `upload_max_filesize` | `20M` | hero music (max 20 MB in the app) |
| `post_max_size` | `24M` | must exceed `upload_max_filesize`, plus form fields |
| `max_file_uploads` | `20` | gallery accepts several images per request |
| `memory_limit` | `256M` | GD re-encoding + Maatwebsite/Excel imports |
| `max_execution_time` | `120` | large Excel imports |

These are **soft** limits: the app rejects files above its own validation
rules first (see the table below). If PHP is lower than the app limit, PHP
wins and the user sees a generic failure, so keep them aligned.

### Application limits (enforced in code)

| Field | Types | Max size |
|---|---|---|
| Hero background | JPG, PNG, WEBP | 8 MB |
| Background music | MP3, WAV, M4A, OGG | 20 MB |
| Mempelai photo | JPG, PNG, WEBP | 4 MB |
| Invitation logo | JPG, PNG, WEBP | 2 MB |
| QRIS image | JPG, PNG, WEBP | 2 MB |
| Gallery image | JPG, PNG, WEBP | 5 MB each |

Error messages are in Indonesian, e.g. "Setiap foto maksimal 5 MB."

---

## Storage

- Uploads go to `storage/app/public/invitations/{invitation_id}/...`
  (`hero/`, `music/`, `mempelai/`, `gallery/`, `photobooth/`, `logo/`,
  `gift/`).
- `storage:link` must exist so `/storage/...` resolves. If symlinks are
  blocked on shared hosting, keep the existing `/storage/{path}` route that
  serves files from `storage/app/public` directly (it is still registered).
- Existing pre-migration files stay at their old `/storage/...` paths; do not
  move them.

## HTTPS

The photobooth and the scanner use the camera via `getUserMedia`. Browsers
only allow that on `https://` or `localhost`. Production must be HTTPS.

## Timezone

`APP_TIMEZONE=Asia/Jakarta` so check-in timestamps and event dates match the
owner's local time.
