<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * P2-T7: seed the second theme so the contract can be proven end to end and
 * the owner can select it in the invitation form.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('themes')->where('key', 'minimal-light')->exists()) {
            return;
        }

        $now = now();

        DB::table('themes')->insert([
            'key' => 'minimal-light',
            'name' => 'Minimal Light',
            'slug' => 'minimal-light',
            'preview_image' => null,
            'svelte_path' => 'Invitation/Show',
            'is_premium' => false,
            'description' => 'Light, single-scroll layout. Proves the theme contract; visual polish is TODO(owner).',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('themes')->where('key', 'minimal-light')->delete();
    }
};
