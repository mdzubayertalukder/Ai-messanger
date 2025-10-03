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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('woo_store_id')->constrained('woo_stores')->cascadeOnDelete();
            $table->string('external_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable()->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->string('status')->nullable();
            $table->string('permalink')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->unique(['woo_store_id', 'external_id']);
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
