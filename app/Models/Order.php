<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'woo_store_id',
        'customer_id',
        'external_id',
        'order_number',
        'status',
        'currency',
        'total',
        'subtotal',
        'tax_total',
        'shipping_total',
        'discount_total',
        'payment_method',
        'payment_method_title',
        'paid',
        'date_created',
        'date_modified',
        'date_completed',
        'billing_address',
        'shipping_address',
        'line_items',
        'customer_note',
        'meta_data',
        'raw',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'date_created' => 'datetime',
        'date_modified' => 'datetime',
        'date_completed' => 'datetime',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'line_items' => 'array',
        'meta_data' => 'array',
        'raw' => 'array',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wooStore(): BelongsTo
    {
        return $this->belongsTo(WooStore::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total, 2);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }
}
