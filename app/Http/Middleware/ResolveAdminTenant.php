<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin tenant resolution (AGENTS.md 5.1):
 *  - `admin` picks the tenant through the event switcher (session key);
 *  - `staff` / `client` are always forced to their own invitation_id.
 *
 * When an admin has no selection and the installation has exactly ONE
 * invitation, that one is selected automatically so the original
 * single-wedding admin flow keeps working. With more than one invitation the
 * admin is sent to the invitation list instead (see EnsureTenantSelected).
 */
class ResolveAdminTenant
{
    public const SESSION_KEY = 'admin_invitation_id';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $context = app(TenantContext::class);

        if ($user === null) {
            return $next($request);
        }

        if ($user->isAdmin()) {
            $invitationId = $request->session()->get(self::SESSION_KEY);

            if ($invitationId === null) {
                $candidates = Invitation::query()->orderBy('id')->limit(2)->pluck('id');

                if ($candidates->count() === 1) {
                    $invitationId = $candidates->first();
                    $request->session()->put(self::SESSION_KEY, $invitationId);
                }
            }

            if ($invitationId !== null && Invitation::whereKey($invitationId)->exists()) {
                $context->set((int) $invitationId);
            }

            return $next($request);
        }

        // staff/client: bound to exactly one invitation, never a session choice.
        if ($user->invitation_id !== null) {
            $context->set((int) $user->invitation_id);
        }

        return $next($request);
    }
}
