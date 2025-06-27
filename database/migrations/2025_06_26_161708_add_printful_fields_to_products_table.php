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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('printful_product_id')->nullable()->after('printful_id');
            $table->unsignedBigInteger('category_id')->nullable()->after('model');
            $table->unsignedBigInteger('clothes_type_id')->nullable()->after('category_id');
            $table->json('metadata')->nullable()->after('is_active');
            
            // Add foreign key constraints
            $table->foreign('category_id')->references('id')->on('clothes_categories')->onDelete('set null');
            $table->foreign('clothes_type_id')->references('id')->on('clothes_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['clothes_type_id']);
            $table->dropColumn(['printful_product_id', 'category_id', 'clothes_type_id', 'metadata']);
        });
    }
};
