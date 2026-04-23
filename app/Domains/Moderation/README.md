# Moderation Domain

## Purpose and scope

The Moderation domain owns the user-reporting pipeline and the tools moderators use to review those reports. It provides:

- A pluggable topic registry so any domain can declare a reportable entity type without modifying the Moderation domain.
- A reusable report-button Blade component that other domains drop into their UI.
- An admin panel page (accessible to `moderator`, `admin`, and `tech-admin` roles) for reviewing, approving, and rejecting reports.

What this domain explicitly does **not** do: it does not enforce any content action (takedown, suspension, hiding) — that is the responsibility of each consuming domain, which listens to `ReportApproved` or `ReportRejected` and acts accordingly.

---

## Key concepts

### Topics

A "topic" is a named, registerable entity type (e.g. `story`, `chapter`, `comment`, `profile`). Topics are stored **in memory only** (a singleton `ModerationRegistry`), not in the database. Domains register their topics at boot time in their service provider.

Each topic is identified by a short string key that must be unique across the application. The key appears in `moderation_reasons.topic_key` and `moderation_reports.topic_key`, so it must never change once data exists.

### Reasons

Moderation reasons are stored in the database (`moderation_reasons`) and are scoped per topic via `topic_key`. Reasons can be activated or deactivated; deactivated reasons are hidden from the report form but preserved so historical reports keep their reason label. Only active reasons are offered to users at report time.

### Report lifecycle

A report transitions through three statuses:

| Status | Meaning |
|--------|---------|
| `pending` | Submitted, awaiting moderator review |
| `confirmed` | Approved by a moderator — content was indeed problematic |
| `dismissed` | Rejected by a moderator — report was unfounded |

Transitioning to `confirmed` or `dismissed` emits a domain event that other domains can react to.

### Snapshot formatter (optional but recommended)

Because reported content can be edited or deleted after a report is filed, a domain can supply a **snapshot formatter** that:

1. Captures a JSON snapshot of the entity at report time (`capture(int $entityId): array`).
2. Renders that snapshot as HTML for the admin review panel (`render(array $snapshot): string`).
3. Returns the content owner's user ID (`getReportedUserId(int $entityId): int`), used to attribute the `reported_user_id` column.
4. Returns a URL to the content (`getContentUrl(int $entityId): string`).

Without a formatter, `reported_user_id` is `null` and no snapshot is stored — reports are still valid but lack attribution and history.

### Report button component

The Blade component `<x-moderation::report-button :topic="'story'" :entity-id="$story->id" />` handles AJAX form loading and report submission. The form is fetched lazily via `GET moderation/report-form/{topicKey}/{entityId}` and submitted via `POST moderation/report`.

---

## Architecture decisions and rationale

- **Topics in memory, not the database.** Keeping topics in a singleton avoids a DB round-trip per request and makes domain registration entirely code-driven (version-controlled, no migration required for new topics). The trade-off is that no topic can be created at runtime without a deploy.

- **No FK from `moderation_reports` to `users`.** Per project architecture rules, no domain may add a foreign key constraint to the `users` table from outside the Auth domain. `reported_user_id` and `reported_by_user_id` are plain `unsignedBigInteger` columns with no constraint.

- **Pending count is cache-invalidated, not event-sourced.** `ModerationService` caches the pending report count under `moderation.pending_reports_count` and invalidates it on every create/approve/reject/delete. This avoids a COUNT query on each admin page render.

- **Content actions are intentionally absent.** The Moderation domain only records and surfaces reports. Automated or manual content actions (e.g. hiding a story, banning a user) belong to the domain that owns the content. This prevents circular dependencies and keeps the moderation pipeline domain-agnostic.

- **Admin panel in the Moderation domain itself, not in `app/Domains/Admin/`.** The admin user-management page and search live under `Private/Controllers/ModerationAdminController` and are registered through the `AdminNavigationRegistry`, keeping Moderation self-contained.

---

## Cross-domain delegation

| Concern | Delegated to | Why |
|---------|-------------|-----|
| Authentication and role checks | Auth domain (`AuthPublicApi`, `Roles`) | Centralised role model |
| Event persistence and dispatch | Events domain (`EventBus`) | All domain events flow through the shared bus |
| Admin navigation registration | Administration domain (`AdminNavigationRegistry`) | Shared admin sidebar contract |
| Content actions on approval/rejection | Each consuming domain | Moderation does not own the content |

---

## Registering a new topic (integration guide)

In the `boot()` method of the consuming domain's service provider:

```php
use App\Domains\Moderation\Public\Services\ModerationRegistry;

$registry = app(ModerationRegistry::class);
$registry->register(
    key: 'story',
    displayName: 'story::moderation.topic_name',
    formatterClass: StorySnapshotFormatter::class, // optional
);
```

Then:

1. Add active reasons for the topic via the admin panel (or a seeder).
2. Drop `<x-moderation::report-button :topic="'story'" :entity-id="$story->id" />` where needed.
3. If a formatter is supplied, implement `SnapshotFormatterInterface` (`capture`, `render`, `getReportedUserId`, `getContentUrl`).
4. Listen to `ReportApproved` or `ReportRejected` in your domain if you need to act on the decision.
