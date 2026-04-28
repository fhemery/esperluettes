<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscordPendingNotification extends Model
{
    protected $table = 'discord_pending_notifications';

    protected $fillable = [
        'notification_id',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(DiscordPendingRecipient::class, 'pending_notification_id');
    }
}
