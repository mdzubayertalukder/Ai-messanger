<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'facebook_page_id',
        'woo_store_id',
        'sender_id',
        'recipient_id',
        'direction',
        'message_text',
        'attachments',
        'ai_response',
        'responded_by_ai',
        'ai_confidence',
        'product_suggestions',
        'external_message_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'product_suggestions' => 'array',
        'responded_by_ai' => 'boolean',
        'ai_confidence' => 'float',
    ];
}

