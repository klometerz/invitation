<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when tenant-scoped work is attempted without a tenant selected.
 *
 * The tenant layer fails closed on purpose: a missing tenant must never
 * silently fall back to "all rows" (that is a cross-client data leak).
 */
class TenantContextMissingException extends RuntimeException
{
    public static function forQuery(string $model): self
    {
        return new self(
            "No tenant selected while querying [{$model}]. "
            .'Set App\Support\TenantContext before querying tenant data, '
            .'or wrap the call in TenantContext::withoutScope() with a comment explaining why.'
        );
    }

    public static function forCreate(string $model): self
    {
        return new self(
            "Refusing to create [{$model}] without an invitation_id. "
            .'Set App\Support\TenantContext, or pass invitation_id explicitly '
            .'inside TenantContext::withoutScope().'
        );
    }

    public static function forMismatch(string $model, int $modelTenant, int $contextTenant): self
    {
        return new self(
            "Refusing to create [{$model}] for invitation [{$modelTenant}] while the "
            ."active tenant is [{$contextTenant}]. Cross-tenant writes are blocked."
        );
    }
}
