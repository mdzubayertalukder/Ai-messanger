<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'woo_store_id',
        'external_id',
        'email',
        'first_name',
        'last_name',
        'username',
        'role',
        'date_created',
        'date_modified',
        'last_order_date',
        'orders_count',
        'total_spent',
        'avatar_url',
        'billing_address',
        'shipping_address',
        'meta_data',
        'raw',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'date_modified' => 'datetime',
        'last_order_date' => 'datetime',
        'total_spent' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'meta_data' => 'array',
        'raw' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wooStore(): BelongsTo
    {
        return $this->belongsTo(WooStore::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFormattedTotalSpentAttribute(): string
    {
        return '$' . number_format($this->total_spent, 2);
    }

    public function isActive(): bool
    {
        return $this->last_order_date && $this->last_order_date->gt(now()->subMonths(6));
    }
}
