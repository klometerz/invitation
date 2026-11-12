<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards admin pages that operate on one invitation.
 *
 * - admin with no selected event -> redirect to the invitation list
 * - staff/client with no bound invitation -> 403 (misconfigured account)
 */
class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(TenantContext::class)->has()) {
            return $next($request);
        }

        $user = $request->user();

        abort_if($user === null, 403);

        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.invitations.index')
                ->with('error', 'Pilih acara terlebih dahulu.');
        }

        abort(403, 'Akun ini belum terhubung ke acara mana pun.');
    }
}
