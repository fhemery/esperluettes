<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Stored record of a domain event (persistence model).
 * Table: events_domain
 */
class StoredDomainEvent extends Model
{
    use Prunable;

    protected $table = 'events_domain';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'payload',
        'triggered_by_user_id',
        'context_ip',
        'context_user_agent',
        'context_url',
        'meta',
        'occurred_at',
    ];

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
