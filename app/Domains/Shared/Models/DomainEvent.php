<?php

namespace App\Domains\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use App\Domains\Shared\Contracts\SummarizableDomainEvent;

class DomainEvent extends Model
{
    use Prunable;

    protected $table = 'domain_events';

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
    ];

    public function prunable()
    {
        $days = (int) config('shared.event_auditing_retention_days', 90);
        return static::where('occurred_at', '<', now()->subDays($days));
    }

    /**
     * Computed, human-readable summary of the event for admin display.
     */
    public function getSummaryAttribute(): string
    {
        $eventClass = $this->name;
        $payload = (array) ($this->payload ?? []);

        if (is_string($eventClass)
            && class_exists($eventClass)
            && is_subclass_of($eventClass, SummarizableDomainEvent::class)
        ) {
            try {
                /** @var class-string<SummarizableDomainEvent> $eventClass */
                return $eventClass::summarizePayload($payload);
            } catch (\Throwable $e) {
                // fall through to generic
            }
        }

        // Generic fallback: short class name with key details
        $short = strrchr($eventClass, '\\');
        $short = $short ? substr($short, 1) : (string) $eventClass;
        $parts = [];
        foreach (['userId', 'id', 'slug', 'newName', 'oldName'] as $k) {
            if (array_key_exists($k, $payload) && is_scalar($payload[$k])) {
                $parts[] = $k.'='.$payload[$k];
            }
        }
        $suffix = $parts ? ' ('.implode(', ', $parts).')' : '';
        return $short.$suffix;
    }
}
