<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

/**
 * P3-T2: flips `active` invitations past their `active_until` to `expired`.
 * Safe and idempotent; scheduled hourly.
 */
class ExpireInvitations extends Command
{
    protected $signature = 'invitations:expire {--dry-run : Show what would change without writing}';

    protected $description = 'Mark active invitations past active_until as expired';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $candidates = Invitation::query()
            ->where('status', 'active')
            ->whereNotNull('active_until')
            ->where('active_until', '<', now())
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'active_until']);

        if ($candidates->isEmpty()) {
            $this->info('No invitations to expire.');

            return self::SUCCESS;
        }

        foreach ($candidates as $invitation) {
            $this->line(sprintf(
                '%s %s (%s) active_until=%s',
                $dryRun ? '[dry-run]' : '[expire]',
                $invitation->title,
                $invitation->slug,
                $invitation->active_until?->toDateTimeString()
            ));

            if (! $dryRun) {
                $invitation->forceFill([
                    'status' => 'expired',
                    'is_active' => false,
                ])->save();
            }
        }

        $this->info(sprintf('%d invitation(s) %s.', $candidates->count(), $dryRun ? 'would expire' : 'expired'));

        return self::SUCCESS;
    }
}
