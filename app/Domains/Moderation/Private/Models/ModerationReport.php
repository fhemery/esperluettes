<?php

namespace App\Domains\Moderation\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationReport extends Model
{
    protected $fillable = [
        'topic_key',
        'entity_id',
        'reported_user_id',
        'reported_by_user_id',
        'reason_id',
        'description',
        'content_snapshot',
        'content_url',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_comment',
    ];

    protected $casts = [
        'content_snapshot' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function reason(): BelongsTo
    {
        return $this->belongsTo(ModerationReason::class, 'reason_id');
    }
}
