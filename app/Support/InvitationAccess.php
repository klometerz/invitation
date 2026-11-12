<?php

namespace App\Support;

use App\Models\Invitation;
use Illuminate\Http\Request;

/**
 * Single place that decides whether an invitation may be shown, and with
 * which lifecycle outcome (AGENTS.md 5.6).
 *
 * Statuses:
 *   draft    -> only a logged-in admin may preview; everyone else 404
 *   active   -> visible inside active_from .. active_until
 *   expired  -> friendly "undangan sudah berakhir" response (410)
 *   archived -> 404
 */
class InvitationAccess
{
    public const VISIBLE = 'visible';

    public const NOT_FOUND = 'not_found';

    public const EXPIRED = 'expired';

    /**
     * Resolve the effective lifecycle state of an invitation.
     *
     * `status` is authoritative, but an `active` invitation outside its
     * window is treated as not-yet-live (draft) or expired.
     */
    public static function lifecycle(Invitation $invitation): string
    {
        $status = $invitation->status ?: 'draft';

        if (in_array($status, ['archived', 'draft', 'expired'], true)) {
            return $status;
        }

        $now = now();

        if ($invitation->active_from !== null && $now->lt($invitation->active_from)) {
            // Not live yet: behave like a draft for visitors.
            return 'draft';
        }

        if ($invitation->active_until !== null && $now->gt($invitation->active_until)) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * @return self::*
     */
    public static function outcome(Invitation $invitation, Request $request): string
    {
        $lifecycle = self::lifecycle($invitation);

        if ($lifecycle === 'active') {
            return self::VISIBLE;
        }

        // A logged-in admin may preview draft and expired invitations.
        if (self::isAdminPreview($request) && in_array($lifecycle, ['draft', 'expired'], true)) {
            return self::VISIBLE;
        }

        return $lifecycle === 'expired' ? self::EXPIRED : self::NOT_FOUND;
    }

    public static function isAdminPreview(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && method_exists($user, 'isAdmin') && $user->isAdmin();
    }
}
