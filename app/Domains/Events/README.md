# Events Module

This module provides a domain events infrastructure to decouple domains while keeping a clear, auditable history of what happened in the system.

It follows the project’s “Public API-Only” architecture: domains expose only their Public APIs, and communicate cross-domain with well-defined domain events.

## Overview

- Core contracts:
  - `Contracts/DomainEvent` — logical name (`name()`), version (`version()`), payload (`toPayload()`), rehydration (`fromPayload()`), and a human `summary()`.
  - `Contracts/AuditableEvent` — marker for events triggered by users; auditing context is recorded when they occur.
- Runtime services:
  - `PublicApi/EventBus` — emits domain events (sync/async) and dispatches to subscribers.
  - `Services/EventService` — reads persisted events for internal usage.
  - `PublicApi/EventPublicApi` — maps stored records to DTOs for UIs/APIs.
  - `Services/DomainEventFactory` — rehydrates concrete events from logical name + payload (used by Admin to build summaries).

## Defining a new event

1. Create the event class implementing `DomainEvent` (and `AuditableEvent` if user-triggered`).

See an example implementation with: `app/Domains/Profile/Events/ProfileDisplayNameChanged.php`

2. Register the event mapping in your domain service provider (so the factory can rehydrate it):

```php
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Profile\Events\ProfileDisplayNameChanged;

public function boot(): void
{
    app(EventBus::class)->registerEvent(ProfileDisplayNameChanged::name(), ProfileDisplayNameChanged::class);
}
```

3. Emit the event when business logic succeeds (prefer services over controllers):

```php
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Profile\Events\ProfileDisplayNameChanged;

$this->eventBus->emit(new ProfileDisplayNameChanged($userId, $old, $new));
```

- If the event is user-triggered and `AuditableEvent` is implemented, the event bus will capture auditing context (current user id, IP, user agent, URL) and persist it alongside the event.

## Consuming events

- Subscribe to events in your domain provider via `EventBus::subscribe($logicalName, [ListenerClass::class, 'handle'])`.
- Listeners should be thin and delegate to services.

See an example implementation with: `app/Domains/Profile/Listeners/CreateProfileOnUserRegistered.php`

## Admin — viewing events

- Admin Filament resource: `Admin/Filament/Resources/Shared/DomainEventResource`
  - Reads from `events_domain` via `StoredDomainEvent`.
  - Computes summary using `DomainEventFactory` (rehydrates and calls `summary()`).
  - Filters: name contains, user id, occurred after/before (debounced/onBlur).

## Naming conventions and versioning

- Logical names are PascalCase with domain prefix: `Domain.EventName` (e.g., `Auth.UserRegistered`).
- Start with `version() = 1`. Bump when payload contract changes.
- Keep payloads minimal, stable primitives (int/string/bool/arrays).

## Testing helpers

- Prefer feature tests that exercise real flows, then assert persisted events:

```php
$event = latestEventOf('Profile.DisplayNameChanged', \App\Domains\Profile\Events\ProfileDisplayNameChanged::class);
expect($event)->not->toBeNull();
expect($event->summary())->toContain('→');
```
