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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('printful_id')->unique(); // Printful product ID
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // t-shirt, hoodie, etc.
            $table->string('brand');
            $table->string('model');
            $table->json('sizes')->nullable(); // Available sizes
            $table->json('colors')->nullable(); // Available colors
            $table->decimal('base_price', 8, 2); // Base price from Printful
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
