# StoryRef Domain — Agent Instructions

- README: [app/Domains/StoryRef/README.md](README.md)

## Public API

- [StoryRefPublicApi](Public/Api/StoryRefPublicApi.php) — full CRUD for all seven reference kinds (types, genres, audiences, statuses, trigger warnings, feedbacks, copyrights); slug-to-ID resolution helpers; bulk fetch via `getAllStoryReferentials()`; cache flush via `clearUiCache()`

Write operations (`create*`, `update*`, `delete*`) enforce `ADMIN` or `TECH_ADMIN` role internally and throw `AuthorizationException` if the current user lacks it.

## Events emitted

| Event | `refKind` values | When |
|-------|-----------------|------|
| `StoryRefAdded` | `type`, `genre`, `audience`, `status`, `trigger_warning`, `feedback`, `copyright` | Reference entry created |
| `StoryRefUpdated` | same | Reference entry updated (carries `changedFields` list) |
| `StoryRefRemoved` | same | Reference entry deleted |

All three events carry `refKind`, `refId`, `refSlug`, `refName`. They are registered with the `EventBus` in the service provider boot.

## Non-obvious invariants

**Slug generation does NOT append ID.** The `HasSlugAndOrder` trait generates slugs as `{base}`, `{base}-1`, `{base}-2`, ... resolving collisions incrementally. This is different from the Story domain's `{base}-{id}` pattern. Never assume a slug encodes an ID for StoryRef entities.

**`activeOnly: true` is the default filter.** All `getAll*()` methods on the Public API default to `StoryRefFilterDto(activeOnly: true)`. Inactive entries are silently excluded. Admin consumers must explicitly pass `new StoryRefFilterDto(activeOnly: false)` to see all entries.

**Cache must be cleared after any write via a controller.** The individual services call `clearCache()` automatically after writes. However, if a controller bypasses the Public API and calls a service directly, it must also trigger `clearUiCache()` on `StoryRefPublicApi` (or call the service's own `clearCache()`). Prefer always going through the Public API for writes.

**`AudienceDto` has extra fields.** `AudienceDto` carries `threshold_age` (nullable int) and `is_mature_audience` (bool) in addition to the common shape. Code that handles DTOs generically (e.g. a common listing component) must not assume all DTOs are structurally identical.

**No FK constraints to users.** Reference tables have no user columns and no FK to the `users` table; this is intentional per architecture rules.

## Listens to

This domain does not subscribe to events from other domains.

## Registry integrations

- **AdminNavigationRegistry** (`Administration` domain) — registers all seven reference admin pages under the `story` navigation section on boot. Each registration uses `AdminRegistryTarget::route(...)` pointing to the domain's own controllers.
