# Calendar

This domain manages time-bound activities (writing challenges, contests, collaborative events) with a plugin-based activity-type registry.

**Not done.** `requires_subscription` and `max_participants` are stored on `calendar_activities` and editable in the admin form but **enforce nothing** — there is no enrolment logic, no cap and no participant list. The core announces nothing either: state is derived from dates, with no cron and no transition event to notify on. The quote contest broadcasts its own lifecycle from its own scheduled command and its own date columns — a first example a generic Calendar notification mechanism could be built from, not a mechanism itself.

**Images.** Activity images go through `MediaPublicApi` on the `activities` scope: new uploads land flat under `activities/`, the admin form uses `<x-media::image-field>` (reuse picker included) and both public views render `<x-media::image>`. Removing an image only clears `image_path` — the file is reclaimed by `media:gc`, which `ActivityMediaUsageProvider` keeps honest. Images uploaded before the migration still live under `activities/YYYY/MM/`; they render normally, are never swept, and do not show up in the picker.

## Overview

The Calendar domain has two layers of responsibility:

1. **Core** — owns the generic `Activity` record, its lifecycle state machine, the `CalendarRegistry` singleton, and the public-facing activity listing/detail pages.
2. **Activity types** — each type (Jardino, Secret Gift, Quote Contest) lives under `Private/Activities/<TypeName>/` and plugs into the core via `ActivityRegistrationInterface`. The core knows nothing about type-specific data or UI beyond the component keys returned by the registration.

## Core Concepts

### Activity Lifecycle (State Machine)

State is computed on the fly from four nullable timestamps — no `status` column, no cron job:

| State | Condition |
|-------|-----------|
| `draft` | `preview_starts_at` is null, or is in the future |
| `preview` | `preview_starts_at` is past; `active_starts_at` is null or in the future; not archived |
| `active` | `active_starts_at` is past; `active_ends_at` is null or in the future; not archived |
| `ended` | `active_ends_at` is past; `archived_at` is null or in the future |
| `archived` | `archived_at` is past |

Draft and Archived activities are hidden from all listings and from the detail page for non-admin users.

### Role Restrictions

Each activity carries a `role_restrictions` JSON array of allowed role slugs. The service enforces this at listing time (no admin bypass on the listing) and at detail page access. Admins and tech-admins can always reach Draft activities via the Public API.

### Slug Format

Slugs are generated as `{slugified-name}-{id}` on creation. On name update, the slug is regenerated with the same `{base}-{id}` suffix. This guarantees uniqueness without a separate lookup.

### Activity Type Registry

`CalendarRegistry` (a singleton) maps string type keys to `ActivityRegistrationInterface` implementations. Each registration provides:
- `displayComponentKey()` — the Blade component key used by the detail page to render the activity's main UI.
- `configComponentKey()` — an optional key for an admin configuration component, rendered inside the activity create/edit form. Backed by `configRules()` (validation rules merged into the activity request for that type only) and `persistConfig()`, run in the same transaction as the activity write. Only Quote Contest uses this half; the other two return `null` and no-op.

The registry is populated at boot time in `CalendarServiceProvider`.

| Type key | Registration class | Display component | Config component |
|----------|--------------------|-------------------|------------------|
| `jardino` | `JardinoRegistration` | `jardino::jardino-component` | — |
| `secret-gift` | `SecretGiftRegistration` | `secret-gift::secret-gift-component` | — |
| `quote-contest` | `QuoteContestRegistration` | `quote-contest::quote-contest-component` | `quote-contest::quote-contest-config` |

## Built-in Activity Types

Each type documents itself; the core knows nothing about what they do.

- [Jardino](Private/Activities/Jardino/README.md) — a word-count writing challenge with a shared garden map.
- [Secret Gift](Private/Activities/SecretGift/README.md) — a secret-santa-style gift exchange.
- [Quote Contest](Private/Activities/QuoteContest/README.md) — *Concours de citations*: readers enter passages from their quote book into admin-defined categories, then vote anonymously.

## Architecture Decisions

- **State is computed, not stored.** This avoids stale state bugs from missed cron jobs and keeps the data model simple. The trade-off is that any query requiring state must load all activities in memory and filter in PHP (see `getAllActivitiesSortedByState()`). Index `ca_type_active_idx` partially mitigates this.
- **Activity type is immutable after creation.** Enforced in `CalendarPublicApi::update()` — changing the type would orphan type-specific data rows referencing the activity.
- **No FK to `users`.** `created_by_user_id` is stored as a plain integer column per the project's cross-domain FK rule. Type-specific tables (Jardino goals, Secret Gift participants/assignments, Quote Contest entries/votes) follow the same rule for their user references.
- **A type's configuration is written in the activity's own transaction.** `ActivityController::store()`/`update()` run the activity write and the registration's `persistConfig()` inside one `DB::transaction()`, so a type can never exist without its config row and throwing from `persistConfig()` rolls the activity back. On create the type is chosen in the same form, so every declared config panel is rendered and toggled client-side — only the submitted type's `configRules()` are applied server-side.
- **Quote Contest privacy is enforced by query shape, not by templates.** Submitter identities and vote counts exist in one family of view models, built by one method, for three roles. The tab that shows them is absent from the tabs array for everyone else rather than hidden in Blade. See the [Quote Contest README](Private/Activities/QuoteContest/README.md).
- **Each activity type is its own sub-module, not its own domain.** Views, translations, migrations, routes, models, and services for a type live entirely under `Private/Activities/<TypeName>/`. Promoting them to domains was rejected: an activity is not independently useful, it always hangs off an `Activity` row, and it would need a Public API nobody would call. Living under Calendar's `Private/` also means Deptrac lets a type reach Calendar's own models and services directly. Revisit only if a type ever needs to be consumed from outside Calendar.
- **Secret Gift shuffle is an Artisan command, not an automated trigger.** An admin runs it manually after the registration phase closes, allowing them to review participant count before committing.

## Cross-Domain Delegation

| What | Delegated to | Why |
|------|-------------|-----|
| Role and authentication checks | Auth (`AuthPublicApi`, `Roles`) | Single source of truth for user roles |
| Story word counts | Story (`StoryPublicApi`, chapter events) | Jardino reacts to story writes it does not own |
| Story visibility and eligibility | Story (`StoryPublicApi`, visibility events) | Quote Contest snapshots stories it does not own, and must withdraw entries when one stops being readable |
| A reader's saved quotes | Quote (`QuotePublicApi`) | Quote Contest reads the submitter's own quote book; the private note never crosses |
| Display names and profile links | Profile (`ProfilePublicApi`) | Author and submitter names are resolved live, never frozen into a row |
| User notifications | Notification (`NotificationPublicApi`, `NotificationFactory`) | The contest's five notifications; the `calendar` group is domain-wide so a second activity reuses it |
| Event bus subscription | Events (`EventBus`) | Cross-domain event wiring uses the shared event bus |

## Admin Panel

Activity CRUD lives in this domain: `Private/Controllers/Admin/ActivityController.php`, routed under the `admin/calendar` prefix (`calendar.admin.*`) with views in `Private/Resources/views/pages/admin/activities/`. It uses the custom `Administration` panel — there is no Filament here. `CalendarPublicApi` exposes the same operations programmatically. A type may add its own fields to that form through `configComponentKey()`; Quote Contest does, for its two dates, and pushes its category editor to the `activity-config-extras` stack the pages render after `</form>` (each category row needs its own form, and nested forms are illegal HTML).

Two Artisan commands live here: `secret-gift:shuffle` (manual) and `calendar:quote-contest-notify`, scheduled every five minutes from `bootstrap/app.php`.

## Adding a New Activity Type (Checklist)

1. Create `Private/Activities/<TypeName>/` with the sub-folders you need (Models, Services, Http/Controllers, Resources/views, Database/Migrations).
2. Implement `ActivityRegistrationInterface` — provide `displayComponentKey()`, and optionally the config triple `configComponentKey()` + `configRules()` + `persistConfig()` (see `QuoteContestRegistration`).
3. Create a `ServiceProvider` for the type; register migrations, routes, views, and any event listeners.
4. Register the ServiceProvider in `CalendarServiceProvider::register()`.
5. Register the type key + registration instance in `CalendarServiceProvider::boot()` via `$registry->register(TypeName::ACTIVITY_TYPE, new TypeRegistration())`.
6. Use `activity_id` as the FK to `calendar_activities` in your type-specific migrations (no FK constraint — cross-table within the same domain is acceptable but the `users` FK rule still applies to user references).
7. Write feature tests under `app/Domains/Calendar/Tests/Feature/<TypeName>/`.
