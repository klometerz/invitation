<?php

namespace App\Models\Concerns;

use App\Exceptions\TenantContextMissingException;
use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as belonging to an invitation (tenant).
 *
 * Adds the fail-closed global scope and fills `invitation_id` on create:
 *  - from the active TenantContext, or
 *  - from an explicit value, but only inside TenantContext::withoutScope()
 *    (seeders, backfills, factories), and it must match the active tenant
 *    when one is set.
 */
trait BelongsToInvitation
{
    public static function bootBelongsToInvitation(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            /** @var TenantContext $context */
            $context = app(TenantContext::class);

            $explicit = $model->getAttribute('invitation_id');
            $active = $context->id();

            if ($explicit !== null) {
                // Writing for another tenant while one is selected is always
                // a bug, even inside withoutScope().
                if ($active !== null && (int) $explicit !== $active) {
                    throw TenantContextMissingException::forMismatch(
                        $model::class,
                        (int) $explicit,
                        $active
                    );
                }

                return;
            }

            if ($active === null) {
                throw TenantContextMissingException::forCreate($model::class);
            }

            $model->setAttribute('invitation_id', $active);
        });
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Invitation::class);
    }
}
