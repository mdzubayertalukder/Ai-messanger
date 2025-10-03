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
            $table->integer('total_inquiries')->default(0)->after('raw');
            $table->integer('total_sales')->default(0)->after('total_inquiries');
            $table->decimal('total_revenue', 12, 2)->default(0)->after('total_sales');
            $table->timestamp('last_inquiry_at')->nullable()->after('total_revenue');
            $table->timestamp('last_sale_at')->nullable()->after('last_inquiry_at');
            $table->string('product_url')->nullable()->after('permalink'); // Direct product URL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'total_inquiries',
                'total_sales', 
                'total_revenue',
                'last_inquiry_at',
                'last_sale_at',
                'product_url'
            ]);
        });
    }
};
