<?php

namespace App\Domains\Notification\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

#[Table('notification_reads', keyType: 'string')]
#[WithoutIncrementing]
#[Fillable(['notification_id', 'user_id', 'read_at'])]
class NotificationRead extends Model
{
    protected $primaryKey = null; // composite key

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
