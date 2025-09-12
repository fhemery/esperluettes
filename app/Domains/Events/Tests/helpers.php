<?php
// Events domain test helpers

use App\Domains\Events\Contracts\DomainEvent;
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
