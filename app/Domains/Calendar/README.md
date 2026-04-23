# Calendar

This domain manages time-bound activities (writing challenges, contests, collaborative events) with a plugin-based activity-type registry.

See `docs/Feature_Planning/Calendar.md` for the full specification (may be partially outdated).

## Overview

The Calendar domain has two layers of responsibility:

1. **Core** — owns the generic `Activity` record, its lifecycle state machine, the `CalendarRegistry` singleton, and the public-facing activity listing/detail pages.
2. **Activity types** — each type (e.g. Jardino, Secret Gift) lives under `Private/Activities/<TypeName>/` and plugs into the core via `ActivityRegistrationInterface`. The core knows nothing about type-specific data or UI beyond the component keys returned by the registration.

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
- `configComponentKey()` — an optional key for an admin configuration component (currently unused by both built-in types).

The registry is populated at boot time in `CalendarServiceProvider`.

## Built-in Activity Types

### Jardino

A word-count challenge where participants set a writing goal, select stories to track, and watch their progress grow on a virtual garden map.

- Users create a **goal** (target word count) and link it to one or more stories.
- Progress is tracked via **story snapshots**: when a goal-linked story gets a chapter event (created, updated, deleted), a listener computes the word delta and updates the snapshot's `current_word_count`.
- The garden UI maps progress onto a grid of garden cells (`JardinoGardenCell`), where flowers bloom as words are written.
- Word count deltas come from the Story domain's `ChapterCreated`, `ChapterUpdated`, and `ChapterDeleted` events.

### Secret Gift

A secret-santa-style exchange where participants sign up, and an admin runs a shuffle to assign each participant a recipient.

- Participants enroll by visiting the activity page while it is Active.
- An admin manually triggers the shuffle via the Artisan command `secret-gift:shuffle {activity_id}`.
- After shuffle, each giver can prepare a gift (text, image, optionally a sound file) stored on the local disk.
- Gift assets (image, sound) are revealed to recipients only once the activity transitions to Ended or Archived state.
- Files are stored on the `local` disk under `calendar/secret-gift/{activity_id}/`.

## Architecture Decisions

- **State is computed, not stored.** This avoids stale state bugs from missed cron jobs and keeps the data model simple. The trade-off is that any query requiring state must load all activities in memory and filter in PHP (see `getAllActivitiesSortedByState()`). Index `ca_type_active_idx` partially mitigates this.
- **Activity type is immutable after creation.** Enforced in `CalendarPublicApi::update()` — changing the type would orphan type-specific data rows referencing the activity.
- **No FK to `users`.** `created_by_user_id` is stored as a plain integer column per the project's cross-domain FK rule. Type-specific tables (Jardino goals, Secret Gift participants/assignments) follow the same rule for their user references.
- **Each activity type is its own sub-module.** Views, translations, migrations, routes, models, and services for a type live entirely under `Private/Activities/<TypeName>/`. This keeps the core domain stable when adding new types.
- **Secret Gift shuffle is an Artisan command, not an automated trigger.** An admin runs it manually after the registration phase closes, allowing them to review participant count before committing.

## Cross-Domain Delegation

| What | Delegated to | Why |
|------|-------------|-----|
| Role and authentication checks | Auth (`AuthPublicApi`, `Roles`) | Single source of truth for user roles |
| Story word counts | Story (`StoryPublicApi`, chapter events) | Jardino reacts to story writes it does not own |
| Event bus subscription | Events (`EventBus`) | Cross-domain event wiring uses the shared event bus |

## Admin Panel

There is currently no Filament resource for Calendar activities in `app/Domains/Admin/`. Activity CRUD is exposed through `CalendarPublicApi` for programmatic use. The Secret Gift shuffle is triggered via the Artisan command.

## Adding a New Activity Type (Checklist)

1. Create `Private/Activities/<TypeName>/` with the sub-folders you need (Models, Services, Http/Controllers, Resources/views, Database/Migrations).
2. Implement `ActivityRegistrationInterface` — provide `displayComponentKey()` and optionally `configComponentKey()`.
3. Create a `ServiceProvider` for the type; register migrations, routes, views, and any event listeners.
4. Register the ServiceProvider in `CalendarServiceProvider::register()`.
5. Register the type key + registration instance in `CalendarServiceProvider::boot()` via `$registry->register(TypeName::ACTIVITY_TYPE, new TypeRegistration())`.
6. Use `activity_id` as the FK to `calendar_activities` in your type-specific migrations (no FK constraint — cross-table within the same domain is acceptable but the `users` FK rule still applies to user references).
7. Write feature tests under `app/Domains/Calendar/Tests/Feature/<TypeName>/`.
