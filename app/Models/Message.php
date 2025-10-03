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
        'has_attachments',
        'raw_data',
        'attachments',
        'ai_response',
        'responded_by_ai',
        'ai_confidence',
        'product_suggestions',
        'product_recommendations',
        'processed_at',
        'external_message_id',
    ];

    protected $casts = [
        'has_attachments' => 'boolean',
        'raw_data' => 'array',
        'attachments' => 'array',
        'product_suggestions' => 'array',
        'product_recommendations' => 'array',
        'responded_by_ai' => 'boolean',
        'ai_confidence' => 'float',
        'processed_at' => 'datetime',
    ];
}

