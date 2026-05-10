<?php

namespace App\Domains\Discord\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('discord_pending_notifications')]
#[Fillable(['notification_id'])]
class DiscordPendingNotification extends Model
{

    public function recipients(): HasMany
    {
        return $this->hasMany(DiscordPendingRecipient::class, 'pending_notification_id');
    }
}
