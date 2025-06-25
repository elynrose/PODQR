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
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wall_post_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45); // IPv6 compatible
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // Prevent multiple views from same IP for same post
            $table->unique(['wall_post_id', 'ip_address']);
            
            // Index for performance
            $table->index(['wall_post_id', 'created_at']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
