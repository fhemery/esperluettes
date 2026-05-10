<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('discord_pending_recipients')]
#[Fillable(['pending_notification_id', 'user_id', 'discord_id', 'sent_at'])]
class DiscordPendingRecipient extends Model
{

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function pendingNotification(): BelongsTo
    {
        return $this->belongsTo(DiscordPendingNotification::class, 'pending_notification_id');
    }
}
