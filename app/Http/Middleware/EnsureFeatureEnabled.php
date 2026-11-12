<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route on a package feature (AGENTS.md 5.4, P2-T1).
 *
 * Used as `feature:photobooth`. Public pages get a 404 (the feature simply
 * does not exist there); JSON/API consumers get a 403 with a message they can
 * show.
 *
 * Must run after a tenant middleware, which sets TenantContext.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $invitationId = app(TenantContext::class)->id();

        if ($invitationId === null) {
            abort(404);
        }

        $invitation = \App\Models\Invitation::find($invitationId);

        if ($invitation === null || ! $invitation->hasFeature($feature)) {
            if ($request->expectsJson()) {
                abort(403, 'Fitur ini tidak tersedia pada paket undangan Anda.');
            }

            // Admin/staff see an explicit 403; anonymous visitors get 404 so
            // the route simply does not exist for them.
            if ($request->user() !== null) {
                abort(403, 'Fitur ini tidak tersedia pada paket undangan Anda.');
            }

            abort(404);
        }

        return $next($request);
    }
}
