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
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            
            // Clothes and size information
            $table->foreignId('clothes_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('shirt_size_id')->constrained()->onDelete('cascade');
            $table->string('color_code');
            
            // QR Code information
            $table->foreignId('qr_code_id')->nullable()->constrained()->onDelete('set null');
            $table->json('qr_code_position')->nullable(); // {x: 100, y: 100, scale: 1.0, rotation: 0}
            
            // Design elements (JSON arrays for multiple items)
            $table->json('photos')->nullable(); // Array of {url, position: {x, y, scale, rotation}, id}
            $table->json('texts')->nullable(); // Array of {text, font, size, color, position: {x, y, scale, rotation}, id}
            
            // Canvas data for front and back
            $table->longText('front_canvas_data')->nullable(); // Fabric.js canvas JSON
            $table->longText('back_canvas_data')->nullable(); // Fabric.js canvas JSON
            
            // Generated images
            $table->string('front_image_path')->nullable();
            $table->string('back_image_path')->nullable();
            
            // Status and timestamps
            $table->enum('status', ['draft', 'saved', 'published'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
