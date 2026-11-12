<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Read-only access to the `settings` key/value table.
 *
 * The admin GUI is P3-T5; until then every getter returns null so callers fall
 * back to "hide the block" rather than inventing platform data.
 *
 * Guarded against a missing table so unit tests and fresh installs cannot
 * explode before the migration runs.
 */
class Settings
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return self::$cache = [];
            }

            $rows = \Illuminate\Support\Facades\DB::table('settings')->get();

            $values = [];

            foreach ($rows as $row) {
                $decoded = json_decode((string) $row->value, true);

                $values[$row->key] = json_last_error() === JSON_ERROR_NONE
                    ? $decoded
                    : $row->value;
            }

            return self::$cache = $values;
        } catch (\Throwable) {
            // Never fail a page render because settings are unreadable.
            return self::$cache = [];
        }
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
