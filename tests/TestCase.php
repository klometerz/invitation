<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** The real, production-like database. Tests must never touch it. */
    private const REAL_DATABASE = 'invitation';

    protected function setUp(): void
    {
        parent::setUp();

        // The settings reader caches per process; reset it so no test can
        // observe values written by another test (or by a previous request).
        \App\Support\Settings::flush();

        $this->assertTestingDatabaseIsIsolated();
    }

    /**
     * Fail fast if the test suite is ever pointed at the real database.
     *
     * The real DB holds the owner's wedding data and is wiped by
     * RefreshDatabase. phpunit.xml isolates tests on sqlite :memory:, so this
     * guard exists to catch a future misconfiguration (a stray .env.testing,
     * a changed phpunit.xml, or a forced --env).
     *
     * @throws RuntimeException
     */
    protected function assertTestingDatabaseIsIsolated(): void
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (app()->environment() !== 'testing') {
            throw new RuntimeException(
                "Tests must run in the 'testing' environment, got '".app()->environment()."'."
            );
        }

        if ($connection !== 'sqlite' && $database === self::REAL_DATABASE) {
            throw new RuntimeException(
                "Refusing to run tests against the real database '{$database}' "
                ."on connection '{$connection}'. Use sqlite :memory: instead."
            );
        }
    }
}
