<?php

namespace Tests\Unit;

use App\Exceptions\TenantContextMissingException;
use App\Support\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * P1-T3: the context object itself, without a database.
 */
class TenantContextTest extends TestCase
{
    public function test_id_is_null_until_set(): void
    {
        $context = new TenantContext;

        $this->assertNull($context->id());
        $this->assertFalse($context->has());

        $context->set(7);

        $this->assertSame(7, $context->id());
        $this->assertTrue($context->has());
    }

    public function test_require_throws_without_a_tenant(): void
    {
        $context = new TenantContext;

        $this->expectException(TenantContextMissingException::class);

        $context->require();
    }

    public function test_run_as_restores_the_previous_tenant(): void
    {
        $context = new TenantContext;
        $context->set(1);

        $observed = $context->runAs(2, fn () => $context->id());

        $this->assertSame(2, $observed);
        $this->assertSame(1, $context->id());
    }

    public function test_run_as_restores_the_previous_tenant_when_it_throws(): void
    {
        $context = new TenantContext;
        $context->set(1);

        try {
            $context->runAs(2, function (): void {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(1, $context->id());
    }

    public function test_without_scope_is_nesting_safe(): void
    {
        $context = new TenantContext;

        $this->assertFalse($context->scopeSuppressed());

        $context->withoutScope(function () use ($context): void {
            $this->assertTrue($context->scopeSuppressed());

            $context->withoutScope(function () use ($context): void {
                $this->assertTrue($context->scopeSuppressed());
            });

            $this->assertTrue($context->scopeSuppressed());
        });

        $this->assertFalse($context->scopeSuppressed());
    }

    public function test_without_scope_restores_state_after_an_exception(): void
    {
        $context = new TenantContext;

        try {
            $context->withoutScope(function (): void {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertFalse($context->scopeSuppressed());
    }

    public function test_forget_clears_the_tenant(): void
    {
        $context = new TenantContext;
        $context->set(5);
        $context->forget();

        $this->assertNull($context->id());
    }
}
