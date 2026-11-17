<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * History of push notifications sent (weekly reminder, dashboard sends).
 * audience: "all" (topic all_users) or "selected" (recipients = user ids).
 */
class Notification extends Model
{
    protected $fillable = [
        'topic',
        'audience',
        'recipients',
        'title',
        'body',
        'response',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'response' => 'array',
        'recipients' => 'array',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];
}
