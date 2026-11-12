<?php

namespace App\Console\Commands;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\PhotoboothPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * P3-T2: retention purge for ARCHIVED invitations.
 *
 * DESTRUCTIVE BY DESIGN, so:
 *  - dry-run is the default;
 *  - it requires --force to write;
 *  - it only touches invitations whose status is `archived` and whose
 *    last update is older than the retention window;
 *  - it removes guest personal data and photobooth files only, never the
 *    invitation row itself (the owner may still want the record).
 *
 * TODO(owner): the retention period is an owner decision (AGENTS.md 11.3).
 * Until then the default is 365 days and can be overridden with --days=.
 */
class PurgeInvitations extends Command
{
    protected $signature = 'invitations:purge
        {--days=365 : Retention window in days after the invitation was archived}
        {--force : Actually delete data (without this it is a dry run)}';

    protected $description = 'Delete guest data and photobooth files of archived invitations (dry-run by default)';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $force = (bool) $this->option('force');
        $cutoff = now()->subDays($days);

        $invitations = Invitation::query()
            ->where('status', 'archived')
            ->where('updated_at', '<', $cutoff)
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'updated_at']);

        if ($invitations->isEmpty()) {
            $this->info("No archived invitations older than {$days} day(s).");

            return self::SUCCESS;
        }

        $totalGuests = 0;
        $totalPhotos = 0;

        foreach ($invitations as $invitation) {
            // Console context: there is no request tenant, so the fail-closed
            // scope must be bypassed explicitly for this maintenance query.
            $guests = Guest::withoutGlobalScopes()
                ->where('invitation_id', $invitation->id)
                ->count();

            $photos = PhotoboothPhoto::withoutGlobalScopes()
                ->where('invitation_id', $invitation->id)
                ->get(['id', 'image_path']);

            $totalGuests += $guests;
            $totalPhotos += $photos->count();

            $this->line(sprintf(
                '%s %s (%s): %d guest(s), %d photo(s)',
                $force ? '[purge]' : '[dry-run]',
                $invitation->title,
                $invitation->slug,
                $guests,
                $photos->count()
            ));

            if (! $force) {
                continue;
            }

            foreach ($photos as $photo) {
                if (! is_string($photo->image_path)) {
                    continue;
                }

                $relative = str_replace('/storage/', '', $photo->image_path);

                if ($relative !== '' && Storage::disk('public')->exists($relative)) {
                    Storage::disk('public')->delete($relative);
                }
            }

            PhotoboothPhoto::withoutGlobalScopes()
                ->where('invitation_id', $invitation->id)
                ->delete();

            Guest::withoutGlobalScopes()
                ->where('invitation_id', $invitation->id)
                ->delete();
        }

        $this->info(sprintf(
            '%s: %d invitation(s), %d guest(s), %d photo(s).',
            $force ? 'Purged' : 'Would purge',
            $invitations->count(),
            $totalGuests,
            $totalPhotos
        ));

        if (! $force) {
            $this->warn('Dry run. Re-run with --force to delete.');
        }

        return self::SUCCESS;
    }
}
