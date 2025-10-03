<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'external_id',
        'sku',
        'attributes',
        'price',
        'stock_quantity',
        'in_stock',
    ];

    protected $casts = [
        'attributes' => 'array',
        'in_stock' => 'boolean',
    ];
}
