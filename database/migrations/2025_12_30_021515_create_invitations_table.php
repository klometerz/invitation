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
       Schema::create('invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('theme_id')->constrained();
    $table->string('slug')->unique(); // URL unik: maulia-kahfi
    $table->string('title'); // Judul: The Wedding of Maulia & Kahfi
    $table->json('content'); // SEMUA DATA (Tgl, Lokasi, Musik) masuk sini
    $table->boolean('is_active')->default(false); // Aktif setelah bayar
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
