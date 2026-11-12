<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on a tenant table to the active invitation.
 *
 * Fails closed: when no tenant is selected the query matches nothing
 * (`where 1 = 0`), instead of returning every client's rows.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        // Explicit escape hatch (legacy redirects, guest-code resolution,
        // seeders, console commands). Never leave this on for request paths.
        if ($context->scopeSuppressed()) {
            return;
        }

        $invitationId = $context->id();

        if ($invitationId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.invitation_id', $invitationId);
    }
}
