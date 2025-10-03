<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
        'in_stock' => 'boolean',
    ];
}
