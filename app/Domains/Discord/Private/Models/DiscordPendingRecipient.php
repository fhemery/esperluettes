<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordPendingRecipient extends Model
{
    protected $table = 'discord_pending_recipients';

    protected $fillable = [
        'pending_notification_id',
        'user_id',
        'discord_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function pendingNotification(): BelongsTo
    {
        return $this->belongsTo(DiscordPendingNotification::class, 'pending_notification_id');
    }
}
