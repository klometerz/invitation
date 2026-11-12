<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-T1: packages are the sellable feature/limit bundles.
 *
 * Prices and contents are NOT decided yet: seeders create the three
 * placeholder rows with price = null (see P1-T2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Null = "Hubungi kami" on the landing page.
            $table->unsignedBigInteger('price')->nullable();
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
