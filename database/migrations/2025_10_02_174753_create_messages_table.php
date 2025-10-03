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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_page_id')->nullable()->constrained('facebook_pages')->nullOnDelete();
            $table->foreignId('woo_store_id')->nullable()->constrained('woo_stores')->nullOnDelete();
            $table->string('sender_id')->index();
            $table->string('recipient_id')->index();
            $table->string('direction'); // inbound or outbound
            $table->text('message_text')->nullable();
            $table->json('attachments')->nullable();
            $table->text('ai_response')->nullable();
            $table->boolean('responded_by_ai')->default(false);
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->json('product_suggestions')->nullable();
            $table->string('external_message_id')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
