<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('guests', function (Blueprint $table) {
        // Kita taruh setelah kolom 'id' biar rapi
        // Kita kasih nullable() dulu biar data lama lo nggak error
        $table->foreignId('invitation_id')->after('id')->nullable()->constrained()->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::table('guests', function (Blueprint $table) {
        $table->dropForeign(['invitation_id']);
        $table->dropColumn('invitation_id');
    });
}
};
