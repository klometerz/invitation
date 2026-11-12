<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T1 (decision C1): the `themes` table already exists with
 * `slug` / `svelte_path` / `is_premium`.
 *
 * We extend it additively instead of replacing those columns, because
 * `invitations.theme_id` is NOT NULL and FK-restricted against live data.
 * `slug` stays the identity for the existing `/v/{slug}` route; `key` becomes
 * the stable code-side identifier used by `resources/js/Themes/{key}/`
 * (P2-T6). P1-T2 backfills `key` from `slug`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // Nullable first: existing row has no key until the P1-T2 backfill.
            $table->string('key')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn(['key', 'description', 'is_active']);
        });
    }
};
