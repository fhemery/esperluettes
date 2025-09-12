<?php

namespace App\Domains\Events\PublicApi;

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Events\Models\StoredDomainEvent;
use App\Domains\Events\Services\EventService;
use App\Domains\Events\Services\DomainEventFactory;
use App\Domains\Events\Contracts\StoredDomainEventDto;

class EventPublicApi
{

    public function __construct(
        private readonly EventService $eventService,
        private readonly DomainEventFactory $eventFactory,
    ) {}

    /**
     * Return recent stored events (no pagination for now).
     *
     * @return array<StoredDomainEventDto>
     */
    public function list(): array {
        $events = $this->eventService->list();

        return collect($events)->map(function (StoredDomainEvent $e) {
            $domainEvent = null;
            try {
                $domainEvent = $this->eventFactory->make($e->name, $e->payload ?? []);
            } catch (\Throwable $ex) {
                // ignore rehydration failures; keep null
            }

            return new StoredDomainEventDto(
                id: (int) $e->id,
                name: (string) $e->name,
                payload: (array) ($e->payload ?? []),
                occurredAt: $e->occurred_at,
                domainEvent: $domainEvent,
                triggeredByUserId: $e->triggered_by_user_id,
                contextIp: $e->context_ip,
                contextUserAgent: $e->context_user_agent,
                contextUrl: $e->context_url,
                meta: $e->meta
            );
        })->all();
    }

    public function latest(string $name): ?DomainEvent {
        $event = $this->eventService->latest($name);
        return $event ? $this->eventFactory->make($event->name, $event->payload ?? []) : null;
    }
}
