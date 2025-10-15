<?php

namespace App\Domains\Moderation\Private\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationReason extends Model
{
    protected $fillable = [
        'topic_key',
        'label',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
