<?php

namespace App\Domains\Notification\Private\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = ['user_id', 'type', 'channel', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
