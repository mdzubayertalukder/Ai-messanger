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
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('has_attachments')->default(false)->after('message_text');
            $table->json('raw_data')->nullable()->after('has_attachments');
            $table->json('product_recommendations')->nullable()->after('ai_response');
            $table->timestamp('processed_at')->nullable()->after('product_recommendations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['has_attachments', 'raw_data', 'product_recommendations', 'processed_at']);
        });
    }
};
