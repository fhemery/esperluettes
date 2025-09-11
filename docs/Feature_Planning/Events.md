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
- Admin visibility: Filament resources exist for `DomainEvent` (list/view) under `App\Domains\Admin\Filament\Resources\Shared\DomainEventResource*`.

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

## 5) Decisions (V1)

- Domain placement
  - Keep event infrastructure in `App\Domains\Shared\*` for now. If it grows significantly, we can revisit a dedicated `Events` domain later.

- Table and listener names
  - Table stays `domain_events` (we are not doing full event sourcing; the intent is audit + targeted replays).
  - Wildcard listener is named `RecordAllDomainEvents` and is registered in `SharedServiceProvider`.

- Versioning strategy
  - No separate version column for now. If an event evolves incompatibly, create a new class (e.g., `StoryCreatedV2`).

- User display in admin
  - Canonical field is `triggered_by_user_id`.
