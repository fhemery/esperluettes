# Events Domain

This domain provides the domain event bus and audit log infrastructure. It is the cross-domain communication backbone: every significant action in the system is emitted as a `DomainEvent`, persisted to the `events_domain` table, and dispatched to subscribers across domains.

It follows the project's "Public API-Only" architecture: domains communicate exclusively through well-defined domain events and never call each other's internals.

## Overview

### Public contracts (consumed by other domains)

| Class | Role |
|-------|------|
| `DomainEvent` | Interface all events must implement: `name()`, `version()`, `toPayload()`, `fromPayload()`, `summary()` |
| `AuditableEvent` | Marker interface; when implemented, the bus captures request context (IP, user agent, URL) on emit |
| `StoredDomainEventDto` | Read-only DTO returned by `EventPublicApi`; wraps the persisted record and a rehydrated `DomainEvent` instance |

### Public API (singletons, bound by `EventsServiceProvider`)

| Class | Role |
|-------|------|
| `EventBus` | Emits events (`emit`, `emitSync`), registers event class mappings (`registerEvent`), and manages subscriptions (`subscribe`) |
| `EventPublicApi` | Reads persisted events as `StoredDomainEventDto` collections: `list()`, `latest(name)`, `getEventsByName(name)` |

### Private services (internal)

| Class | Role |
|-------|------|
| `EventService` | Stores events to the database and queries `StoredDomainEvent` records |
| `DomainEventFactory` | Registry mapping logical event names to concrete PHP classes; used to rehydrate events from stored payloads |

## Database

### `events_domain`

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | Auto-increment |
| `name` | `varchar(255)` | Logical event name, indexed (e.g., `Auth.UserRegistered`) |
| `payload` | `json` | Serialized event payload |
| `triggered_by_user_id` | `bigint` nullable | Authenticated user at emit time, indexed; no FK constraint (cross-domain) |
| `context_ip` | `varchar(45)` nullable | Set only when event implements `AuditableEvent` |
| `context_user_agent` | `varchar(512)` nullable | Set only when event implements `AuditableEvent` |
| `context_url` | `varchar(2048)` nullable | Set only when event implements `AuditableEvent` |
| `meta` | `json` nullable | Reserved for future metadata |
| `occurred_at` | `datetime` | Indexed; set to `now()` on emit |

Records are pruned automatically via Laravel's `Prunable` trait. Retention is controlled by `shared.event_auditing_retention_days` (default: 90 days).

## Defining a new event

### 1. Create the event class

```php
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Contracts\AuditableEvent; // optional

class UserRegistered implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}

    public static function name(): string { return 'Auth.UserRegistered'; }
    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return ['userId' => $this->userId, 'email' => $this->email];
    }

    public function summary(): string
    {
        return "User {$this->userId} registered as {$this->email}";
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            email: (string) ($payload['email'] ?? ''),
        );
    }
}
```

Place the class under your domain's `Public/Events/` directory.

Implement `AuditableEvent` when the action is user-triggered and you want IP, user agent, and URL captured alongside the payload.

### 2. Register the event mapping in your service provider

```php
use App\Domains\Events\Public\Api\EventBus;

public function boot(): void
{
    app(EventBus::class)->registerEvent(UserRegistered::name(), UserRegistered::class);
}
```

This registration is required before the bus can route subscriptions and before `DomainEventFactory` can rehydrate the event for display in the Admin panel.

### 3. Emit the event from a service

```php
use App\Domains\Events\Public\Api\EventBus;

public function register(string $email, string $password): User
{
    $user = User::create([...]);
    $this->eventBus->emit(new UserRegistered($user->id, $email));
    return $user;
}
```

Prefer emitting from services, not controllers.

## Consuming events (subscribing)

```php
use App\Domains\Events\Public\Api\EventBus;

public function boot(): void
{
    app(EventBus::class)->subscribe(
        'Auth.UserRegistered',
        [CreateProfileOnUserRegistered::class, 'handle']
    );
}
```

- `subscribe` accepts a single logical name or an array of names.
- Listeners should be thin and delegate to services.
- If the event class is not yet registered when `subscribe` is called, the subscription is queued and wired as soon as `registerEvent` is called for that name.

See `app/Domains/Profile/Private/Listeners/CreateProfileOnUserRegistered.php` for a complete listener example.

## Naming conventions and versioning

- Logical names use `Domain.EventName` format in PascalCase (e.g., `Auth.UserRegistered`, `Story.ChapterPublished`).
- Start at `version() = 1`. Increment the version when the payload schema changes in a breaking way.
- Keep payloads as minimal, stable primitives (int, string, bool, scalar arrays). Never include Eloquent models.

## Admin panel integration

The Filament resource `Admin/Filament/Resources/Shared/DomainEventResource` reads from `events_domain` via `StoredDomainEvent` and uses `DomainEventFactory` to rehydrate events and compute their `summary()` for display. Filters: name contains, user id, occurred after/before.

## Testing

### Helpers (available in all domain feature tests)

```php
// Retrieve the most recent event of a given name, typed to the expected class
$event = latestEventOf('Profile.DisplayNameChanged', ProfileDisplayNameChanged::class);
expect($event)->not->toBeNull();
expect($event->summary())->toContain('→');

// Emit an event directly in a test
dispatchEvent(new ProfileDisplayNameChanged($userId, 'old', 'new'));

// Count persisted events of a given name
expect(countEvents('Auth.UserRegistered'))->toBe(1);
```

### Test structure

- `Tests/Feature/EventBusListenersTest.php` — verifies routing: correct listeners fire for their event, not for unrelated events.
- `Tests/Feature/EventBusPersistenceTest.php` — verifies that `emit()` persists records to `events_domain`, including audit context fields for `AuditableEvent` implementations.
- `Tests/Feature/EventPublicApiGetEventsByNameTest.php` — verifies `EventPublicApi::getEventsByName()` filtering, ordering, rehydration, and error tolerance for corrupted payloads.
