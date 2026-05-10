<?php

namespace App\Domains\Notification\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('notification_preferences')]
#[Fillable(['user_id', 'type', 'channel', 'enabled'])]
class NotificationPreference extends Model
{

    protected $casts = ['enabled' => 'boolean'];
}
