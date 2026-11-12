<?php

namespace App\Http\Middleware;

use App\Models\Guest;
use App\Models\Invitation;
use App\Support\InvitationAccess;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant resolution for public endpoints that are keyed only by guest_code
 * (RSVP, photobooth capture, guest lookup). AGENTS.md 5.1 / 5.2.
 *
 * The guest is looked up WITHOUT the tenant scope (we do not know the tenant
 * yet), then the context is set from guest.invitation_id and the same
 * lifecycle checks as the public page are applied.
 */
class ResolveInvitationFromGuestCode
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->route('code')
            ?? $request->input('guest_code')
            ?? $request->query('guest');

        if (! is_string($code) || $code === '') {
            abort(404);
        }

        $context = app(TenantContext::class);

        // No tenant is known yet, so the scope must be bypassed for this one
        // lookup. Safe: the route param is the only input and the result is
        // used solely to establish the tenant.
        $guest = $context->withoutScope(
            fn () => Guest::where('guest_code', $code)->first()
        );

        if ($guest === null) {
            abort(404);
        }

        $invitation = Invitation::find($guest->invitation_id);

        if ($invitation === null) {
            abort(404);
        }

        $outcome = InvitationAccess::outcome($invitation, $request);

        if ($outcome === InvitationAccess::NOT_FOUND) {
            abort(404);
        }

        if ($outcome === InvitationAccess::EXPIRED) {
            // P3-T4: RSVP and capture are rejected once an invitation is not
            // active. 403 keeps the JSON contract the frontend already reads.
            abort(403, 'Undangan sudah berakhir.');
        }

        // A not-yet-live invitation must not accept submissions either. An
        // admin previewing it is the only exception.
        if (InvitationAccess::lifecycle($invitation) !== 'active'
            && ! InvitationAccess::isAdminPreview($request)) {
            abort(403, 'Undangan belum aktif.');
        }

        $context->set($invitation->id);

        $request->attributes->set('invitation', $invitation);
        $request->attributes->set('guest', $guest);

        return $next($request);
    }
}
