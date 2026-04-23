# Events Domain — Agent Instructions

- README: [app/Domains/Events/README.md](README.md)

## Public API

| Class | Purpose |
|-------|---------|
| `EventBus` | `emit(DomainEvent)`, `emitSync(DomainEvent)`, `registerEvent(name, class)`, `subscribe(name|names, listener)`, `resolveDomainEventClass(name)` |
| `EventPublicApi` | `list()`, `latest(name)`, `getEventsByName(name)` — all return `StoredDomainEventDto` or `DomainEvent` |

Both are singletons. Always resolve via the container (`app(EventBus::class)`), never instantiate directly.

## Contracts

| Interface/Class | Role |
|-----------------|------|
| `DomainEvent` | All events must implement this. Required: `name()`, `version()`, `toPayload()`, `fromPayload()`, `summary()` |
| `AuditableEvent` | Marker interface. Add it to user-triggered events to capture IP, user agent, and URL in the stored record |
| `StoredDomainEventDto` | Read-only DTO wrapping a persisted record. Accessors: `id()`, `name()`, `payload()`, `occurredAt()`, `domainEvent()`, `triggeredByUserId()`, `contextIp()`, `contextUserAgent()`, `contextUrl()`, `meta()`, `summary()` |

## Events emitted

This domain emits no events of its own. It is infrastructure only.

## Listens to

This domain registers no event listeners. It is a passive infrastructure provider.

## Non-obvious invariants

**`EventBus`, `EventService`, and `DomainEventFactory` are singletons.** The factory holds the in-memory event class registry. Instantiating these with `new` bypasses the registry and breaks routing and rehydration. Always resolve from the container.

**`triggered_by_user_id` is captured for all events, not just `AuditableEvent`.** `EventService::store()` always writes `Auth::id()` to `triggered_by_user_id`. The `AuditableEvent` marker only adds the HTTP request fields (`context_ip`, `context_user_agent`, `context_url`).

**Pending subscriptions.** If `subscribe()` is called before `registerEvent()` for the same logical name, the subscription is queued internally and wired once the event class is registered. Subscription order between service providers therefore matters. Register events before other domains subscribe to them.

**No FK constraint on `triggered_by_user_id`.** The `events_domain` table has no foreign key to `users`. This is intentional: the Events domain must not create a hard dependency on the Auth domain's table.

**Ordering by `id`, not `occurred_at`.** All queries order by primary key (`id desc`) rather than `occurred_at` to avoid timestamp collisions and for index performance. Do not change this to `occurred_at` ordering.

**Pruning retention is configurable.** The `StoredDomainEvent` model uses Laravel's `Prunable` trait with a cut-off read from `shared.event_auditing_retention_days` (default 90). Run `artisan model:prune` on a schedule to apply it.

**Rehydration failures are silent.** `EventPublicApi::list()` and `getEventsByName()` catch all `Throwable` exceptions during `DomainEventFactory::make()` and set `domainEvent` to `null` on the DTO. Code consuming these methods must always guard against a `null` `domainEvent()`.

**Event class must be registered for Admin summaries to work.** If a domain emits an event but never calls `registerEvent()` in its service provider, the `DomainEventFactory` cannot rehydrate it, and the Admin panel will show no summary for those records.

## Testing helpers

The file `Tests/helpers.php` provides three global functions available in all feature tests:

```php
latestEventOf(string $name, string $class): ?DomainEvent
dispatchEvent(DomainEvent $event): void
countEvents(string $name): int
```

Use `latestEventOf` to assert that an event was emitted and typed correctly. Use `countEvents` to assert emission count. Use `dispatchEvent` to seed events in listener tests.
