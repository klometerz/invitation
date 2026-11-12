<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T1: lifecycle, package and commercial columns on invitations.
 *
 * Additive only - every column is nullable or has a default, so the
 * production-like rows keep working. NOT NULL tightening happens in P1-T10
 * (and only for the tenant columns, not these).
 *
 * `is_active` is kept but deprecated: nothing reads it after P1-T5/T6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('status')->default('draft')->index();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->json('feature_overrides')->nullable();
            $table->json('sections')->nullable();
            $table->dateTime('active_from')->nullable();
            $table->dateTime('active_until')->nullable();
            $table->date('event_date')->nullable();

            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();

            $table->unsignedBigInteger('price_agreed')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->text('internal_notes')->nullable();

            $table->boolean('is_demo')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'package_id',
                'feature_overrides',
                'sections',
                'active_from',
                'active_until',
                'event_date',
                'client_name',
                'client_phone',
                'client_email',
                'price_agreed',
                'payment_status',
                'amount_paid',
                'paid_at',
                'internal_notes',
                'is_demo',
            ]);
        });
    }
};
