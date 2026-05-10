<?php

namespace App\Domains\Moderation\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['topic_key', 'label', 'sort_order', 'is_active'])]
class ModerationReason extends Model
{

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
