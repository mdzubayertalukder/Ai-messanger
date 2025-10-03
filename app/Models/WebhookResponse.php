<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookResponse extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'event_type',
        'verify_token',
        'challenge',
        'request_data',
        'response_data',
        'status',
        'ip_address',
        'user_agent',
        'verified_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
