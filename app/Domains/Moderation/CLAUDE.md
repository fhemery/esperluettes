# Moderation Domain — Agent Instructions

- README: [app/Domains/Moderation/README.md](README.md)

---

## Public API

- `ModerationPublicApi` — `approveReport`, `rejectReport`, `deleteReport`, `getReportCountsByUserIds`. All methods enforce role checks internally (admin / tech-admin / moderator); callers do not need to add their own gate.
- `ModerationRegistry` — singleton. Other domains call `register(key, displayName, formatterClass)` in their service provider `boot()`. Never call `register()` outside of a boot method — the registry is in-memory and must be populated before any request hits a moderation route.

---

## Events emitted

- `Moderation.ReportSubmitted` — on successful report creation; emitted **synchronously** (`emitSync`).
- `Moderation.ReportApproved` — when a report transitions to `confirmed`.
- `Moderation.ReportRejected` — when a report transitions to `dismissed`.

All three are `AuditableEvent` and are persisted to the `domain_events` table via the Events domain.

---

## Non-obvious invariants

- **Topic keys are permanent.** `topic_key` is stored as a plain string in both `moderation_reasons` and `moderation_reports`. Renaming a key after data exists will orphan all existing records — never rename a registered topic key.

- **`ModerationRegistry` is a singleton; register only in `boot()`.** The registry holds topics in a plain PHP array. If `register()` is called after the singleton is resolved in a request, the registration will persist for that process but may be absent in another worker. Always register in the service provider `boot()` method.

- **Pending count cache must be invalidated on every status change.** The cache key `moderation.pending_reports_count` is set to `rememberForever`. `ModerationService` calls `Cache::forget()` in `approveReport`, `dismissReport`, `deleteReport`, and `createReport`. Any new code path that changes report count or status must also call `Cache::forget('moderation.pending_reports_count')`.

- **No FK to `users` table.** `reported_user_id`, `reported_by_user_id`, and `reviewed_by_user_id` are plain `unsignedBigInteger` columns with no constraint. Do not add a foreign key. This is intentional per project architecture rules.

- **`reported_user_id` is only populated when a formatter is registered.** Without a formatter, the column is `null`. Code that queries by `reported_user_id` must handle nulls.

- **`SnapshotFormatterInterface` must implement all four methods.** `capture`, `render`, `getReportedUserId`, `getContentUrl`. The registry validates the interface at registration time but does not validate method return types. A formatter that returns an incorrect type will fail silently at report time.

---

## Listens to

The Moderation domain does not subscribe to any other domain's events. Reactions to moderation decisions are the responsibility of the domains that own the reported content.
