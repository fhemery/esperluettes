# StoryRef Domain

## Purpose and scope

StoryRef owns all curated reference data used by the Story domain: story types, genres, audiences, publication statuses, trigger warnings, feedback preferences, and copyrights. It exposes this data through a single Public API and provides admin CRUD interfaces for each reference kind. StoryRef does not render any user-facing story pages; it is purely a data-management and data-access domain.

Out of scope: story content, chapter management, and any user-facing display — those belong to the Story domain.

---

## Key concepts

### Reference entity structure

Every reference entity (type, genre, audience, status, trigger warning, feedback, copyright) shares a common shape:
- `slug` — a human-readable, URL-safe identifier generated automatically from `name` at creation time. Slugs are unique within each reference table. They are not suffixed with an ID (unlike story/chapter slugs in the Story domain).
- `is_active` — controls whether the entry is returned by default. Callers that pass `StoryRefFilterDto(activeOnly: true)` (the default) only receive active records.
- `order` (optional) — most entities support ordered display. Order is auto-assigned as `max(order) + 1` on creation. Reordering is available via dedicated admin routes.

The `HasSlugAndOrder` trait handles both slug generation and order assignment at the Eloquent model level.

### Audience maturity fields

Audiences carry two extra fields beyond the common shape: `threshold_age` (optional integer) and `is_mature_audience` (boolean). These allow the platform to distinguish age-restricted audiences (e.g. adults-only) from general ones. These fields are included in `AudienceDto` and must be handled by any consumer that renders audience information.

### Caching

All reference collections are cached for 24 hours (one cache key per reference kind). The cache is invalidated immediately after any create, update, or delete operation. Callers that need up-to-date data after a write do not need to do anything — services handle invalidation transparently. The `clearUiCache()` method on `StoryRefPublicApi` allows external callers (e.g. admin panels) to flush all reference caches at once.

### Active-only filtering

`StoryRefPublicApi` accepts a `StoryRefFilterDto` on all collection methods. The default is `activeOnly: true`, meaning inactive references are hidden from all consumers by default. Admin views that need to show all entries (including inactive ones) must explicitly pass `new StoryRefFilterDto(activeOnly: false)`.

---

## Architecture decisions

**Admin panel lives inside the domain, not in Admin/Filament.** Unlike some other domains, StoryRef hosts its own admin controllers and Blade views in `Private/Controllers/Admin/` and `Private/Resources/views/`. These pages are registered into the `Administration` domain's `AdminNavigationRegistry` via the service provider, so they appear in the shared admin navigation without depending on Filament.

**Slugs are generated from name, not suffixed with ID.** The `HasSlugAndOrder` trait generates collision-safe slugs using a numeric suffix (`-1`, `-2`, ...) only when a collision is detected, not by default. This differs from the Story domain's `{base}-{id}` pattern. Consumers should not assume slug format — always fetch by slug via the Public API.

**Domain events are emitted for every mutation.** Every create, update, and delete operation emits a typed domain event (`StoryRefAdded`, `StoryRefUpdated`, `StoryRefRemoved`) carrying the `refKind` string (e.g. `"genre"`, `"audience"`). This allows other domains to react to reference data changes via the event bus.

**No foreign keys to users.** Reference tables contain no user references; they are platform-level configuration, not user-generated content.

---

## Cross-domain delegation map

| What | Delegated to | Why |
|------|-------------|-----|
| Admin page navigation registration | `Administration` domain (`AdminNavigationRegistry`) | Centralised admin nav; StoryRef registers its own pages into the shared registry |
| Event persistence and routing | `Events` domain (`EventBus`) | Cross-domain event bus; all three StoryRef events are registered with the bus on boot |
| Authentication and role checks | `Auth` domain (`AuthPublicApi`, `Roles`) | Write operations on the Public API require `ADMIN` or `TECH_ADMIN` role |

---

## Admin panel notes

All seven reference types have dedicated admin CRUD pages served at `/admin/story-ref/{kind}`. These routes require the `auth` middleware and the `ADMIN` or `TECH_ADMIN` role. Each resource controller additionally exposes:
- `PUT /reorder` — update the display order of all items
- `GET /export` — export the reference list

The pages are registered in the `Administration` domain's navigation registry under the `story` section, so they appear in the shared admin sidebar without being part of the Filament panel.
