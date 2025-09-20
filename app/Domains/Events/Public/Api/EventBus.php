<?php

namespace App\Domains\Events\Public\Api;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Private\Services\DomainEventFactory;
use App\Domains\Events\Private\Services\EventService;
use Illuminate\Support\Facades\Event as LaravelEvent;

class EventBus
{
    public function __construct(
        private readonly DomainEventFactory $factory,
        private readonly EventService $eventService,
    ) {}

    public function emit(DomainEvent $event): void
    {
        // Persist then dispatch. Non-critical timing (after-commit) is currently controlled by listeners
        // implementing ShouldHandleEventsAfterCommit. We may later move after-commit semantics into the Bus.
        $this->persist($event);
        LaravelEvent::dispatch($event);
    }

    public function emitSync(DomainEvent $event): void
    {
        // Intended semantics: critical events handled within the request.
        // For now, we persist and dispatch via Laravel; listeners decide timing. We will refine to enforce
        // strict synchronous handling as needed when we control transactions in the Bus.
        $this->persist($event);
        LaravelEvent::dispatch($event);
    }

    public function subscribe(string|array $eventNames, callable|array $listener): void
    {
        foreach ((array) $eventNames as $name) {
            $resolved = $this->factory->resolve($name) ?? $name; // allow either logical name or FQCN
            LaravelEvent::listen($resolved, $listener);
        }
    }

    public function registerEvent(string $name, string $dtoClass): void
    {
        $this->factory->register($name, $dtoClass);
    }

    public function resolveDomainEventClass(string $name): ?string
    {
        return $this->factory->resolve($name);
    }

    private function persist(DomainEvent $event): void
    {
        $this->eventService->store($event);
    }
}
