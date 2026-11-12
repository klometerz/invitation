# Restore procedure (pre-multi-tenant backup)

Backup date: **2026-11-12** (before any Phase 1 schema change).
Everything in this file is run from the project root: `C:\laragon\www\invitation`.

## What the backup contains

| Item | Location | Notes |
|---|---|---|
| Full MySQL dump (schema + data) of `invitation` | `backups/pre-multitenant-20261112.sql` | 48 KB, taken with `--single-transaction --routines --triggers` |
| Stored user uploads (hero, music, gallery, photobooth) | `backups/storage-public-20261112/` | 27 files, ~10.6 MB, copy of `storage/app/public` |
| **Not** in the backup | `public/storage` symlink, `public/build`, `.env` | symlink is recreated by `storage:link`; `.env` and build assets are never versioned |

`backups/` is git-ignored. It is your only copy: if a restore is needed, do **not** delete it.

## 0. Prerequisites

Use the Laragon binaries (they are not necessarily on `PATH`):

```powershell
# run once per PowerShell window
$env:PATH = 'C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\composer;' + $env:PATH
```

Make sure the MySQL service is running (`Get-Service mysql`).

## 1. Restore the database

> This replaces the contents of the `invitation` database. Confirm twice that
> `--database` is `invitation`, not a production host.

```powershell
# (a) Take a fresh safety copy of the CURRENT state first, even if it is broken
mysqldump -h 127.0.0.1 -P 3306 -u root --routines --triggers --single-transaction `
  --default-character-set=utf8mb4 invitation > backups\safety-before-restore-$(Get-Date -Format yyyyMMdd-HHmm).sql

# (b) Restore
mysql -h 127.0.0.1 -P 3306 -u root invitation < backups\pre-multitenant-20261112.sql

# (c) Verify: expect migrations=10, guests=158, galleries=6, photobooth_photos=9, invitations=1
mysql -h 127.0.0.1 -P 3306 -u root -N -e "SELECT CONCAT('migrations=', (SELECT COUNT(*) FROM invitation.migrations)), CONCAT('guests=', (SELECT COUNT(*) FROM invitation.guests)), CONCAT('galleries=', (SELECT COUNT(*) FROM invitation.galleries)), CONCAT('photobooth_photos=', (SELECT COUNT(*) FROM invitation.photobooth_photos)), CONCAT('invitations=', (SELECT COUNT(*) FROM invitation.invitations));"
```

Notes:

- `mysqldump`/`mysql` read the root password from the console prompt if one is set.
  The dump uses `DROP TABLE IF EXISTS`, so it fully replaces these 14 tables.
- If MySQL runs on a non-default port, change `-P 3306`.

## 2. Restore the uploaded files

```powershell
# Remove the current uploads first so deleted files do not linger
Remove-Item .\storage\app\public\* -Recurse -Force -Exclude '.gitignore'

# Copy the backup back
Copy-Item .\backups\storage-public-20261112\* .\storage\app\public\ -Recurse -Force

# Re-link the public disk (the symlink is not versioned)
php artisan storage:link

# Verify: expect 27 files and 10,568,674 bytes
(Get-ChildItem .\storage\app\public -Recurse -File | Measure-Object Length -Sum) | Format-List Count,Sum
```

## 3. After restoring

```powershell
php artisan optimize:clear   # config, route, view caches
php artisan migrate:status    # inspect the migration state
```

Then open `http://invitation.test` and `http://invitation.test/admin` and check:
the invitation page loads with hero image and music, the guest list shows 158 rows,
photobooth photos show, and the scanner page is reachable.

## 4. Restoring only the code

The database backup is independent of the code. To go back to the code state before
multi-tenant work started:

```powershell
git switch main        # main == v3 at the moment the backup was taken (commit 9622d80)
```

`backups/` stays valid across branch switches: it lives outside version control.

## Known limitation

`backups/` only protects against schema/data mistakes made **locally during the
upgrade**. It is not an off-site backup strategy. For production see the Phase 4
task `P4-T2` (`docs/BACKUP.md`, scheduled `backup:db`).
