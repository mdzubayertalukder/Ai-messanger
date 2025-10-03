<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'user_id',
        'woo_store_id',
        'external_id',
        'name',
        'description',
        'sku',
        'price',
        'stock_quantity',
        'in_stock',
        'status',
        'permalink',
        'product_url',
        'raw',
        'total_inquiries',
        'total_sales',
        'total_revenue',
        'last_inquiry_at',
        'last_sale_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'in_stock' => 'boolean',
        'price' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'last_inquiry_at' => 'datetime',
        'last_sale_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wooStore(): BelongsTo
    {
        return $this->belongsTo(WooStore::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getFormattedRevenueAttribute(): string
    {
        return '$' . number_format($this->total_revenue, 2);
    }

    public function incrementInquiry(): void
    {
        $this->increment('total_inquiries');
        $this->update(['last_inquiry_at' => now()]);
    }

    public function incrementSale(float $amount = null): void
    {
        $this->increment('total_sales');
        if ($amount) {
            $this->increment('total_revenue', $amount);
        }
        $this->update(['last_sale_at' => now()]);
    }
}
