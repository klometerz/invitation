<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use App\Support\InvitationAccess;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public tenant resolution: sets TenantContext from the {slug} route param
 * and applies lifecycle gating (AGENTS.md 5.6).
 *
 * Registered as the `tenant.public` alias.
 */
class ResolveInvitation
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if (! is_string($slug) || $slug === '') {
            abort(404);
        }

        $invitation = Invitation::where('slug', $slug)->first();

        if ($invitation === null) {
            abort(404);
        }

        $outcome = InvitationAccess::outcome($invitation, $request);

        if ($outcome === InvitationAccess::NOT_FOUND) {
            abort(404);
        }

        if ($outcome === InvitationAccess::EXPIRED) {
            // Friendly "undangan sudah berakhir" page (P3-T2), still 410 so
            // search engines and messengers treat it as gone.
            $response = Inertia::render('Invitation/Expired', [
                'invitation' => [
                    'id' => $invitation->id,
                    'title' => $invitation->title,
                    'slug' => $invitation->slug,
                ],
            ])->toResponse($request);

            $response->setStatusCode(410);

            return $response;
        }

        app(TenantContext::class)->set($invitation->id);

        // Downstream controllers read the resolved invitation from here.
        $request->attributes->set('invitation', $invitation);

        return $next($request);
    }
}
