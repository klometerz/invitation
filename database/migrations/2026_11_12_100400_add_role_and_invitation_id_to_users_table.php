<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T1: roles and tenant binding for admin/staff accounts.
 *
 * `staff` is the default so any account created accidentally has least
 * privilege; P1-T2 backfills the existing owner accounts to `admin`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->index();
            $table->foreignId('invitation_id')->nullable()->constrained('invitations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['invitation_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'invitation_id']);
        });
    }
};
