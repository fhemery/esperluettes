# Calendar and activities

**Status:** core DONE — 2026-07-27, two deferred features remain ·
**Domain(s):** `Calendar`

Read this before adding an activity type or touching activity visibility.

## What it does

Time-bound site activities — writing challenges, contests, seasonal events —
driven by a plugin registry. The Calendar domain owns the base activity (name,
description, image, dates, role restrictions) and an admin CRUD; each activity
*type* is a self-contained plugin under `Private/Activities/<Type>/` with its
own tables, controllers, views, services and event listeners.

Two types shipped at first: **Jardino** (a word-count challenge with a flower
garden) and **SecretGift**. A third, **Quote contest**, followed later (see
[`quote-contest`](./quote-contest.md)).

## Key behaviour

- **State is derived from dates, never stored, and there is no cron.**
  `draft → preview → active → ended → archived` is computed from
  `preview_starts_at`, `active_starts_at`, `active_ends_at`, `archived_at`,
  each nullable. This is a deliberate architectural decision — do not add a
  status column.
- **Visibility follows state:** draft = admin only; preview = visible to
  eligible users but participation disabled; active = visible and
  participatable; ended = visible to all with final state; archived = hidden
  from listings, reachable by direct URL.
- **Eligibility is a role array** on the activity (e.g. `['user-confirmed']`),
  enforced at query level.
- **An activity type registers its own event listeners** — Jardino listens to
  Story chapter events to track word counts. Calendar itself knows nothing about
  Story.
- **No central reward system.** Rewards are entirely the activity type's
  business, by design.
- **The dashboard widget** renders through `<x-calendar::activity-list-component />`
  in the Dashboard domain's index view.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API / registry | `Public/Api/{CalendarPublicApi,CalendarRegistry,ActivityRegistrationInterface}.php` |
| Contracts | `Public/Contracts/{ActivityDto,ActivityState,ActivityToCreateDto,ActivityToUpdateDto}.php` |
| Services | `Private/Services/{ActivityService,ActivityStateService}.php` |
| Admin CRUD | `Private/Controllers/Admin/ActivityController.php` |
| Activity types | `Private/Activities/{Jardino,SecretGift,QuoteContest}/` — each with its own provider, migrations, routes |
| Table | `activities` |

## Adding an activity type

Implement the registration interface, provide a main component, register your
listeners, and ship your own migrations and tables keyed by `activity_id`. Look
at `SecretGiftRegistration` for the smaller of the two original examples. Your
provider registers the type into `CalendarRegistry`.

## Not done

- **Subscription and participant limits.** `requires_subscription` and
  `max_participants` are stored on `activities` and editable in the admin form,
  but **enforce nothing** — there is no enrolment logic, no cap, no participant
  list. Existing types use implicit participation.
  → [`../calendar-subscription/`](../calendar-subscription/)
- **State-change notifications.** Nothing announces that an activity opened or
  is about to close. Deferred originally because the Notification domain did not
  exist; it does now.
  → [`../calendar-notifications/`](../calendar-notifications/)

## Stale in the old document

The pre-loop spec described the admin side as a Filament resource. Filament is
gone — Calendar's admin uses the custom `Administration` panel
(`Private/Controllers/Admin/`). Media handling is likewise out of date: this was
fixed by [`media-consumer-migration`](./media-consumer-migration.md), which
added a proper `activities` scope in place of the phantom `calendar` one.

The pre-loop planning document is in git history:
`git show f1d50704:docs/Feature_Planning/Calendar.md`.
