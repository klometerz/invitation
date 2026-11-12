<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T1: photobooth photos currently only relate to a guest.
 *
 * Audit note (C5): the direct tenant column is added here, but the P1-T2
 * backfill must derive it *through* `guest_id`, because existing rows have
 * no other link to an invitation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photobooth_photos', function (Blueprint $table) {
            $table->foreignId('invitation_id')->nullable()->constrained('invitations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('photobooth_photos', function (Blueprint $table) {
            $table->dropForeign(['invitation_id']);
            $table->dropColumn('invitation_id');
        });
    }
};
