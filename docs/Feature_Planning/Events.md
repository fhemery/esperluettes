# Event System — Planning and Design

Last updated: 2025-09-11

## 1) Problem statement and goals

We need a robust, domain-oriented Event system that:

- Enables clear decoupling between domains via domain events.
- Allows Domains to communicate in directions that are not planned ahead of time (e.g., `Auth` → `Profile` via `UserRegistered`).
- Enables pluggable domains (outside the main system) with zero coupling. Example: future `Notifications` and `Activity` modules only listen to events and are never addressed directly.
- Audits everything significant that happens in the system for observability (who/what/when/where) without breaking requests.
- Enables replay: the ability to access past events and “replay” them to build new statistics or projections in the future. Replays are never dispatched globally to the whole app, and never imply rollback.
- Be done in-process for now. We might want async/queued handling later.
- Preserves Domain Oriented Architecture boundaries (no DB access from controllers; events flow through services).
- Has no retention policy for now.
- Is visible from the Admin (Filament), which filtering on event name, event emitter, timespan available
- Keeps performance predictable with guardrails (after-commit listeners, batching, and queue usage when appropriate).
- Is testable and predictable (deterministic contracts, clear event names and payloads).

What we do not want:

- Cross-service/event bus out of process (Kafka, NATS, SNS). This is a community app, not a SaaS.
- Full event sourcing. The events will not capture the whole data, for volume sake.
- Events to be a way for domains to communicate with themselves:
    - Changing an Admin parameter should not require a domain event to bust the cache, it should bust it directly.
    - ChapterCreated should never be listened by Story Domain. Only other domains should listen.

## 2) Examples

- User lifecycle
  - UserRegistered → Create profile (already implemented).
  - EmailVerified, PasswordResetRequested: audit.
- Story & chapters
  - Story or Chapter Created/Updated/Deleted → notify followers, statistics
  - Statistics/projections (via targeted replays): average characters per story, totals per author, daily publishes, etc.
- Comments
  - CommentPosted/Edited/Deleted → Notify story authors, moderate pipeline, update counters.
- Admin actions
  - RoleGranted/Revoked → Security audit.
  - FeatureToggled → Audit.

## 3) Current state — what exists today

- `RecordAllDomainEvents.php` listener is registered in `SharedServiceProvider`, recording any Event in the `App\Domains` namespace
- Storage model: `DomainEvent` with JSON `payload`, contextual fields, pruning by `occurred_at` using `config('shared.event_auditing_retention_days', 90)` and a computed `summary` (supports `SummarizableDomainEvent`).
- Profile domain listener: `CreateProfileOnUserRegistered` listens to `UserRegistered` and implements `ShouldHandleEventsAfterCommit`.
- Admin visibility: Filament resources exist for `DomainEvent` (list/view) under `App\Domains\Admin\Filament\Resources\Event\DomainEventResource*`.

Gaps we should address:

- Ensure all domain events live under `App\Domains/<Domain>/Events/*` to be picked up by the auditor.
- Standardize event naming and payload shape guidelines.
- Decide default listener behavior (after-commit) and exceptions.
- Provide testing utilities and docs for faking and asserting events.
- Performance safeguards (rate/size limits for auditing; skip noisy framework/vendor events—already filtered by namespace, but confirm all our domains follow the pattern).

## 4) Architectural rules

- Baseline: Laravel native events/listeners with domain-oriented event classes
  - Pros: Familiar, first-class in Laravel, easy testing (`Event::fake()`), flexible sync/async, after-commit.
  - Cons: In-process only; needs discipline for domain boundaries and naming.

- Emitted events
  - Past tense naming
  - do NOT use `ShouldQueue` yet. We will add queueing later where needed.
  - Event (class) → immutable data (public readonly properties in PHP 8.2+ style) and a clear name: `UserRegistered`, `StoryCreated`, `ChapterPublished`.
  - Events should be small, serializable DTOs (scalars, arrays, IDs, simple value objects). No Eloquent models.

- Storing events
  - A wildcard auditor to persist all domain events to `domain_events`.
  - Add `SummarizableDomainEvent` implementations for key events to improve admin UX.

- Listening to events
  - Non-critical listeners: use `ShouldHandleEventsAfterCommit`.
  - Critical listeners should handle within transaction. For now, only `app/Domains/Auth/Events/UserRegistered.php` is considered critical.

- Replayability
  - Targeted replays only: events are NOT re-dispatched into the app’s event bus. They are streamed from storage and applied to a single projection/listener to compute stats or rebuild derived data. No rollback semantics.
  - Provide a `Replayer` service that selects events by type/time window, streams in batches, and calls a single projection’s method.


- Projections & Replayer
  - `Projection` interface (e.g., `apply(object $event, array $payload, \DateTimeInterface $occurredAt): void`) implemented by stat builders. Must be idempotent.
  - `Replayer` service: `replay(types: array, from?: datetime, to?: datetime, projection: Projection)` streams events without re-dispatching to the bus. Supports batching and dry-run.
  - Example projections: `ComputeAverageCharsPerStory`, `RebuildDailyStoryStats`.

## 5) Contracts and Public API (architecture only)

- EventDTO (interface)
  - Purpose: a base contract for emitted domain events. Keeps Eloquent inside services and exposes a minimal, serializable contract outside.
  - Shape (conceptual):
    - Static: `public static function name(): string` → logical event name used for storage and routing (not necessarily the FQCN).
    - Instance: `public function toPayload(): array` → returns a JSON-serializable array (scalars/arrays/IDs only; no models).
    - Static: `public static function fromPayload(array $payload): static` → reconstructs the DTO from stored payload.
    - Optional: `public static function version(): int` → default 1; we can also version by class name (V2) instead of a method.
  - Conventions:
    - DTOs are immutable in practice (readonly properties, no setters).

- EventBus (Public API) and implementation
  - Purpose: single entry point to emit and subscribe to events; fully hides Laravel’s events from domains.
  - Emit methods:
    - `emit(EventDTO $event): void` → default path. If event is non-critical, emit after-commit; if critical, emit immediately.
    - `emitSync(EventDTO $event): void` → explicit sync (for rare, critical cases like `UserRegistered`).
  - Subscribe methods (for listeners):
    - `subscribe(string|array $eventNames, callable|string $listener): void` → register interest in given event names; under the hood maps to Laravel listeners.
    - Domains call subscribe from their own ServiceProvider `boot()` to listen to other domains’ events, without referencing Laravel.
  - Storage policy: EventBus stores to `domain_events` directly, then dispatches to listeners; `RecordAllDomainEvents` can be disabled to avoid double-write.
  - Rule: No direct `Event::dispatch()` or `Event::listen()` usage outside the EventBus Public API.

- Auditable marker/trait
  - A marker (interface or trait) that indicates the event should capture extra context when stored (url, user_agent, ip, triggered_by_user_id, etc.).
  - Rationale: save space for non-auditable events (we currently audit all; marker gives us a future opt-out).

- Critical marker (optional)
  - A marker or method on the DTO (e.g., `public static function isCritical(): bool`) to signal that handlers must run synchronously within the request. For now, only `UserRegistered` is critical.

- EventFactory / registry
  - Purpose: map stored `(name, version)` to the right DTO class to reconstruct events for projections/replay.
  - Registration model:
    - Programmatic registration in each domain’s `ServiceProvider::boot()` via the Public API:
      - `EventBus::registerEvent(string $name, class-string<EventDTO> $dtoClass): void`
    - The EventBus maintains an internal in-memory registry (and may cache it) to support rehydration via `fromPayload()`.
    - This keeps each domain responsible for declaring the events it emits, and avoids global config coupling.

- Placement
  - Dedicated domain recommended: `app/Domains/Events/` to centralize contracts and API.
  - Contracts (including DTO interface) under `app/Domains/Events/Contracts/` (e.g., `EventDTO.php`, `EventBus.php`, `Projection.php`).
  - Public API under `app/Domains/Events/PublicApi/` (e.g., `EventBus.php` facade/contract exposure for other domains; implemented in Services).
  - Services under `app/Domains/Events/Services/` (e.g., `LaravelEventBus.php`). The EventBus holds the registry internally (no global config required).
  - Provider: `app/Domains/Events/Providers/EventsServiceProvider.php` binds the contracts and exposes the Public API.

- Validation and testing
  - DTOs validate their payload in constructors/factories; `toPayload()` must return only JSON-serializable primitives.
  - Tests use `EventBus` fakes to assert emissions; no direct `Event::fake()` in producers.

### Domain registration pattern

- Each domain registers, in its own `ServiceProvider::boot()`:
  - Its events into the registry via `EventBus::registerEvent('Domain.EventName', DTO::class)`.
  - Subscriptions to other domains’ event names via `EventBus::subscribe(...)` using the Public API.
- This ensures domains never touch Laravel events directly and keeps wiring declarative and local to each domain.

## 5) Decisions (V1)

- Domain placement
  - Given the added complexity (DTO contract, Public API, subscription routing, registry), we will use a dedicated `Events` domain now: `app/Domains/Events/*`.
  - We will migrate the current cross-cutting pieces (recorder, model, service) from `Shared` into `Events` incrementally, keeping namespaces stable for other domains.

- Table and listener names
  - Table stays `domain_events` (we are not doing full event sourcing; the intent is audit + targeted replays).
  - Wildcard listener is named `RecordAllDomainEvents` and is registered in `SharedServiceProvider`.

- Versioning strategy
  - No separate version column for now. If an event evolves incompatibly, create a new class (e.g., `StoryCreatedV2`).

- User display in admin
  - Canonical field is `triggered_by_user_id`.

## 6) Implementation plan (architecture-only)

Phase 0 — Prep and registry pattern
- Programmatic registry: each domain will call `EventBus::registerEvent(name, dtoClass)` from its own `ServiceProvider::boot()` to declare events it emits. The EventBus maintains the registry internally.
- Why programmatic vs config:
  - Keeps ownership within each domain (the domain that emits declares it), avoids a central config bottleneck, and stays explicit and testable.

Phase 1 — Create Events domain skeleton
- Create `app/Domains/Events/` with:
  - `Contracts/` — `EventDTO.php` interface, `EventBus.php`, `Projection.php`, `EventRegistry.php` (optional).
  - `PublicApi/` — `EventBus` facade/contract exposure for other domains.
  - `Services/` — `LaravelEventBus.php` (stores to DB then dispatches; holds in-memory registry).
  - `Models/` — `Event.php` Eloquent model (mimics `App\Domains\Shared\Models\DomainEvent`), table: `events_domain`.
  - `Providers/EventsServiceProvider.php` — binds contracts, loads config, registers any internal listeners if needed.
  - `Database/Migrations/` — create `events_domain` table (columns: name, payload JSON, triggered_by_user_id, context_ip, context_user_agent, context_url, meta JSON, occurred_at datetime; plus indexes on name, occurred_at).

Phase 2 — Public API wiring (no Laravel Event facade in domains)
- Bind `EventBus` in `EventsServiceProvider` and expose it via `PublicApi`.
- Enforce rule: producers use `EventBus::emit()`/`emitSync()`; domains subscribe via `EventBus::subscribe()` from their own `ServiceProvider::boot()`.

- DTO: copy `@[/app/Domains/Auth/Events/UserRegistered.php]` into the new DTO interface style (minimal payload, static name/fromPayload) and register it via `EventBus::registerEvent('Auth.UserRegistered', DTO::class)` in Auth’s provider.
- Listener: create a listener equivalent to `@[/app/Domains/Profile/Listeners/CreateProfileOnUserRegistered.php]` but subscribed via `EventBus::subscribe('Auth.UserRegistered', [Listener::class, 'handle'])` in the Profile domain provider.
- Storage: ensure `LaravelEventBus` stores to `events_domain` at emit time, then dispatches listeners.
- Disable the wildcard recorder temporarily for this path to avoid double-write (only for the event under test).

Phase 4 — Validation and admin visibility
- Add Filament resource for `Events\Models\Event` (list/view) mirroring the current DomainEvent resource.
- Optionally add `SummarizableDomainEvent` support via DTO static summarization method or a separate summary contract.

Phase 5 — Cleanup and migration plan
- Decide whether to:
  - Keep both `domain_events` and `events_domain` during transition, or
  - Migrate existing data from `domain_events` to `events_domain` and deprecate the Shared model/listener.
- Remove `RecordAllDomainEvents` once EventBus covers storage for all producers.
- Update documentation to reflect Events domain as the source of truth.

Notes
- This plan remains architecture-only for now. We will implement once conventions are fully locked.
