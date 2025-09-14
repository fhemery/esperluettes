<?php
// Events domain test helpers

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Events\Contracts\StoredDomainEventDto;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Events\PublicApi\EventPublicApi;

/**
 * Retrieve the most recent stored event by name and ensure its DomainEvent instance matches the given class.
 *
 * @param class-string $domainEventClass
 */
function latestEventOf(string $name, string $domainEventClass): ?DomainEvent
{
    $event = app(EventPublicApi::class)->latest($name);
    if ($event && ($event instanceof $domainEventClass)) {
        return $event;
    }
    return null;
}

function dispatchEvent(DomainEvent $event): void
{
    app(EventBus::class)->emit($event);
}

function countEvents(string $name): int
{
    $events = app(EventPublicApi::class)->list();
    $events = array_filter($events, fn(StoredDomainEventDto $e) => $e->name() == $name);
    return count($events);
}
