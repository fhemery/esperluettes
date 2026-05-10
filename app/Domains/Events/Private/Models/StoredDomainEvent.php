<?php

namespace App\Domains\Events\Private\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

#[Table('events_domain', timestamps: false)]
#[Fillable(['name', 'payload', 'triggered_by_user_id', 'context_ip', 'context_user_agent', 'context_url', 'meta', 'occurred_at'])]
class StoredDomainEvent extends Model
{
    use Prunable;

    protected $casts = [
        'payload' => 'array',
        'meta' => 'array',
        'occurred_at' => 'datetime',
        'triggered_by_user_id' => 'integer',
    ];

    public function prunable()
    {
        $days = (int) config('shared.event_auditing_retention_days', 90);
        return static::where('occurred_at', '<', now()->subDays($days));
    }
}
