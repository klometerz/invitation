<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Quota jumlah tamu yang boleh dibawa (default 1)
            $table->integer('quota')->default(1)->after('guest_code');

            // Check-in / Guest Book fields
            $table->enum('checkin_status', ['not_arrived', 'arrived'])
                  ->default('not_arrived')
                  ->after('attendance');
            $table->timestamp('checked_in_at')->nullable()->after('checkin_status');
            $table->integer('actual_guest_count')->nullable()->after('checked_in_at');
            $table->string('checked_in_by')->nullable()->after('actual_guest_count');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn([
                'quota',
                'checkin_status',
                'checked_in_at',
                'actual_guest_count',
                'checked_in_by',
            ]);
        });
    }
};