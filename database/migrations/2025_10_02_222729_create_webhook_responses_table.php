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
        Schema::create('webhook_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('platform')->default('facebook'); // facebook, woocommerce, etc.
            $table->string('event_type'); // verification, message, order, etc.
            $table->string('verify_token')->nullable();
            $table->string('challenge')->nullable();
            $table->json('request_data')->nullable(); // Store full request data
            $table->json('response_data')->nullable(); // Store response sent back
            $table->string('status')->default('received'); // received, processed, failed
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'platform']);
            $table->index(['event_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_responses');
    }
};
