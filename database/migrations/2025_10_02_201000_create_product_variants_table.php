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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('external_id')->index();
            $table->string('sku')->nullable()->index();
            $table->json('attributes')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
