<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Invitation;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Landing behaviour for Phase 1 (AGENTS.md 5.2): keep the current UX by
 * redirecting `/` to the showcase invitation. Replaced by a real marketing
 * page in P3-T6.
 *
 * Legacy printed QR / WhatsApp links point at `/?guest=CODE`; those must keep
 * working, so they are resolved to the slug URL here.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $code = $request->query('guest');

        if (is_string($code) && $code !== '') {
            $invitation = $this->invitationForGuestCode($code);

            if ($invitation === null) {
                abort(404);
            }

            return redirect()->to(
                url('/'.$invitation->slug).'?guest='.urlencode($code)
            );
        }

        $invitation = Invitation::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->first()
            ?? Invitation::query()->orderBy('id')->first();

        if ($invitation === null) {
            // Fresh install: no wedding to show yet, but `/` still responds.
            return Inertia::render('Empty');
        }

        return redirect()->to(url('/'.$invitation->slug));
    }

    private function invitationForGuestCode(string $code): ?Invitation
    {
        // The tenant is unknown at this point (that is what we are resolving),
        // so this single lookup must bypass the tenant scope. The code comes
        // only from the query string and is used solely to pick the tenant.
        $guest = app(TenantContext::class)->withoutScope(
            fn () => Guest::where('guest_code', $code)->first()
        );

        if ($guest === null || $guest->invitation_id === null) {
            return null;
        }

        return Invitation::find($guest->invitation_id);
    }
}
