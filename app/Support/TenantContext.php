<?php

namespace App\Support;

use App\Exceptions\TenantContextMissingException;

/**
 * Holds the invitation (tenant) that the current request/command works on.
 *
 * Registered as a singleton (see AppServiceProvider). The value is set by
 * middleware (P1-T4), console commands and tests.
 *
 * Two orthogonal switches:
 *  - `id()`        which tenant is active (null = none);
 *  - `withoutScope()` temporarily disables the global scope for explicit
 *    cross-tenant work (legacy redirects, guest-code resolution, seeders,
 *    backfills). Every call site must carry a comment explaining why.
 */
class TenantContext
{
    private ?int $invitationId = null;

    /**
     * Nesting depth of withoutScope() calls, so nested escapes compose.
     */
    private int $scopeSuppressionDepth = 0;

    public function set(?int $invitationId): void
    {
        $this->invitationId = $invitationId;
    }

    public function id(): ?int
    {
        return $this->invitationId;
    }

    public function has(): bool
    {
        return $this->invitationId !== null;
    }

    /**
     * @throws TenantContextMissingException
     */
    public function require(): int
    {
        if ($this->invitationId === null) {
            throw TenantContextMissingException::forQuery('tenant context');
        }

        return $this->invitationId;
    }

    public function forget(): void
    {
        $this->invitationId = null;
    }

    /**
     * Run a callback inside another tenant, restoring the previous one even
     * when the callback throws.
     */
    public function runAs(int $invitationId, callable $callback): mixed
    {
        $previous = $this->invitationId;
        $this->invitationId = $invitationId;

        try {
            return $callback($invitationId);
        } finally {
            $this->invitationId = $previous;
        }
    }

    /**
     * Temporarily disable the tenant global scope.
     *
     * ONLY for explicit cross-tenant work. The caller must explain why in a
     * comment; unscoped queries on tenant tables are a release blocker.
     */
    public function withoutScope(callable $callback): mixed
    {
        $this->scopeSuppressionDepth++;

        try {
            return $callback();
        } finally {
            $this->scopeSuppressionDepth--;
        }
    }

    public function scopeSuppressed(): bool
    {
        return $this->scopeSuppressionDepth > 0;
    }
}
