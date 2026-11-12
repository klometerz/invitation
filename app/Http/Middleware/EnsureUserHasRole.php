<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role gate. Used as `role:admin` or `role:admin,staff`.
 *
 * Roles: admin (owner, everything), staff (scanner only), client (no v1
 * access). Authorization lives on the server, never only in the frontend.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if (! $user->is_active) {
            abort(403, 'Akun ini sudah dinonaktifkan.');
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
