<?php

namespace App\Domains\Shared\Listeners;

use App\Domains\Shared\Services\DomainEventService;

class RecordAllDomainEvents
{
    public function __construct(private DomainEventService $auditor)
    {
    }

    /**
     * Handle any event via wildcard subscription.
     *
     * @param string $eventName
     * @param array<int, mixed> $data
     */
    public function handle(string $eventName, array $data): void
    {
        if (!config('shared.event_auditing_enabled', true)) {
            return;
        }

        // Filter to only our domain events classes: App\Domains\..\Events\..
        if (!str_starts_with($eventName, 'App\\Domains\\') || !str_contains($eventName, '\\Events\\')) {
            return;
        }

        $event = $data[0] ?? null;
        if (!is_object($event)) {
            return;
        }

        try {
            $this->auditor->record($eventName, $event);
        } catch (\Throwable $e) {
            // Never break the request because of auditing
            // Optionally log: logger()->warning('Domain event audit failed', ['error' => $e->getMessage()]);
        }
    }
}
