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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
             $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('woo_store_id')->constrained('woo_stores')->cascadeOnDelete();
            $table->string('external_id')->index(); // WooCommerce customer ID
            $table->string('email')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('role')->default('customer');
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_modified')->nullable();
            $table->timestamp('last_order_date')->nullable();
            $table->integer('orders_count')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->string('avatar_url')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('meta_data')->nullable();
            $table->json('raw')->nullable(); // Full WooCommerce customer data
            $table->timestamps();
            $table->unique(['woo_store_id', 'external_id']);
            $table->index(['woo_store_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
