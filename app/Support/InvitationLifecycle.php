<?php

namespace App\Support;

use App\Models\Invitation;

/**
 * Lifecycle rules (AGENTS.md 5.6). Kept in one place so the controller, the
 * commands and the tests agree on what is allowed.
 */
class InvitationLifecycle
{
    /**
     * Allowed status transitions. `archived` is terminal: un-archiving is a
     * deliberate data change, not a status toggle.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'draft' => ['active', 'archived'],
        'active' => ['expired', 'archived'],
        'expired' => ['active', 'archived'],
        'archived' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function isExpiredByDate(Invitation $invitation): bool
    {
        return $invitation->status === 'active'
            && $invitation->active_until !== null
            && now()->gt($invitation->active_until);
    }

    /**
     * Default `active_until` at activation time: event date plus the package's
     * `active_days_after_event`. Null when either value is unknown, which
     * means "no expiry" and must be set manually.
     */
    public static function defaultActiveUntil(Invitation $invitation): ?\Illuminate\Support\Carbon
    {
        $days = $invitation->limit('active_days_after_event');

        if ($days === null || $invitation->event_date === null) {
            return null;
        }

        return $invitation->event_date->copy()->endOfDay()->addDays($days);
    }
}
