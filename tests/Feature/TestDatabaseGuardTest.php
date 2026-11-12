<?php

namespace Tests\Feature;

use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * P0-T3: proves the safety guard refuses to run against the real database.
 *
 * This test never connects to "invitation": it only mutates the in-memory
 * config so the guard sees the real database name, then calls the guard
 * directly.
 */
class TestDatabaseGuardTest extends TestCase
{
    public function test_guard_rejects_the_real_database(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'invitation',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("real database 'invitation'");

        $this->callGuard();
    }

    public function test_guard_rejects_a_non_testing_environment(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("must run in the 'testing' environment");

        $this->callGuard();
    }

    public function test_guard_allows_the_isolated_test_database(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->callGuard();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_guard_allows_a_separate_mysql_test_database(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'invitation_test',
        ]);

        $this->callGuard();

        $this->assertSame('invitation_test', config('database.connections.mysql.database'));
    }

    private function callGuard(): void
    {
        $method = new ReflectionMethod(TestCase::class, 'assertTestingDatabaseIsIsolated');
        $method->setAccessible(true);
        $method->invoke($this);
    }
}
