<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooStore extends Model
{
    protected $fillable = [
        'user_id',
        'store_name',
        'store_url',
        'consumer_key',
        'consumer_secret',
        'wp_api',
        'version',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'wp_api' => 'boolean',
    ];
}

