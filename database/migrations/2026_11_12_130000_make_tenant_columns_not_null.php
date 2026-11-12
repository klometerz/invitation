<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T10: tighten the tenant columns now that P1-T2 backfilled every row and
 * the fail-closed trait guarantees new rows always carry an invitation_id.
 *
 * Only run after the full suite is green. Each table is checked for NULLs
 * first, so this migration refuses to guess instead of failing halfway.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['guests', 'galleries', 'photobooth_photos'];

    public function up(): void
    {
        // SQLite cannot reliably ALTER an FK column in place; the test
        // database is recreated per run so NOT NULL adds nothing there.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->tables as $table) {
            $nulls = DB::table($table)->whereNull('invitation_id')->count();

            if ($nulls > 0) {
                throw new RuntimeException(
                    "Refusing to tighten {$table}.invitation_id: {$nulls} row(s) still have no invitation_id."
                );
            }
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('invitation_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('invitation_id')->nullable()->change();
            });
        }
    }
};
