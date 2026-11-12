<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T1 (K4): galleries had no tenant column at all.
 * Nullable now; backfilled in P1-T2 and tightened in P1-T10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->foreignId('invitation_id')->nullable()->constrained('invitations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropForeign(['invitation_id']);
            $table->dropColumn('invitation_id');
        });
    }
};
