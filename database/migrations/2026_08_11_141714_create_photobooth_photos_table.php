<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photobooth_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->onDelete('cascade');
            $table->string('image_path');
            $table->text('message')->nullable();
            $table->string('voice_note_path')->nullable(); // buat Fase 5 nanti
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photobooth_photos');
    }
};