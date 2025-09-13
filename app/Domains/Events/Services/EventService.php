<?php

namespace App\Domains\Events\Services;

use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Events\Models\StoredDomainEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventService
{
    /**
     * @return array<StoredDomainEvent>
     */
    public function list(): array
    {
        // No filtering/pagination for now; cap the result size defensively
        $events = StoredDomainEvent::query()
            // We could have used occured_at, but we would risk collision. And actually, using the primary key
            // is more performant.
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get([
                'id',
                'name',
                'payload',
                'triggered_by_user_id',
                'context_ip',
                'context_user_agent',
                'context_url',
                'meta',
                'occurred_at',
            ]);

        return $events->all();
    }

    public function latest(string $name): ?StoredDomainEvent {
        return StoredDomainEvent::query()
            ->where('name', $name)
            // We could have used occured_at, but we would risk collision. And actually, using the primary key
            // is more performant.
            ->orderBy('id', 'desc')
            ->first();
    }

    public function store(DomainEvent $event): void {
        // Build base record
        $record = [
            'name' => $event::name(),
            'payload' => $event->toPayload(),
            'occurred_at' => now(),
            'meta' => null,
            'context_ip' => null,
            'context_user_agent' => null,
            'context_url' => null,
            'triggered_by_user_id' => Auth::id(),
        ];

        // If auditable, enrich with request/user context
        if ($event instanceof AuditableEvent) {
            // Request facade may be unavailable in some contexts (CLI). Guard accordingly.
            try {
                $record['context_ip'] = Request::ip();
                $record['context_user_agent'] = Request::userAgent();
                $record['context_url'] = Request::fullUrl();
            } catch (\Throwable $e) {
                // leave as null if no HTTP context
            }
        }

        // Create stored event
        StoredDomainEvent::create($record);
    }
}
