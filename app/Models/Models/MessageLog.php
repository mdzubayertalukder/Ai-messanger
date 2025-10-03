<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $fillable = [
        'message_id',
        'event',
        'payload',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
