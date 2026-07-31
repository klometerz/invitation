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
       Schema::create('themes', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Nama Tema: 'Rustic Vintage', 'Modern Dark'
    $table->string('slug')->unique(); // 'rustic', 'modern'
    $table->string('preview_image'); // URL gambar buat katalog
    $table->string('svelte_path'); // Lokasi folder Svelte: 'Themes/Rustic/Index'
    $table->boolean('is_premium')->default(false);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme');
    }
};
