<?php

namespace App\Domains\Notification\Private\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    protected $table = 'notification_reads';
    public $incrementing = false;
    protected $primaryKey = null; // composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'notification_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
