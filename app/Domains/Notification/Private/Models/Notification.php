<?php

namespace App\Domains\Notification\Private\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'source_user_id',
        'content_key',
        'content_data',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'content_data' => 'array',
    ];
}
