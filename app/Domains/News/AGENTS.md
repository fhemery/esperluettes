# News Domain — Agent Instructions

- README: [app/Domains/News/README.md](README.md)

## Public API

- [NewsPublicApi](Public/Api/NewsPublicApi.php) — exposes `getPinnedForCarousel(): array<NewsCarouselItemDto>` for the Home domain. Do not reach into `NewsService` directly from other domains; use this class.

## Events emitted

| Event | When |
|-------|------|
| `NewsPublished` | Article transitions from draft to published (carries `newsId`, `slug`, `title`, `publishedAt`) |
| `NewsUnpublished` | Article transitions from published back to draft |
| `NewsUpdated` | Any field on an article changes (emitted by `NewsObserver`; carries `changedFields` array) |
| `NewsDeleted` | Article is deleted (emitted by `NewsObserver`) |

All four events are registered on the `EventBus` in `NewsServiceProvider`.

## Listens to

| Event | Action |
|-------|--------|
| `Auth::UserDeleted` | Nullifies `created_by` on all articles authored by that user (`RemoveCreatorOnUserDeleted`) |

## Non-obvious invariants

**Publish and unpublish must go through the service, not direct field writes.** `NewsService::publish()` sets `published_at` (first time only), emits `NewsPublished`, and fires a broadcast notification. `NewsService::unpublish()` emits `NewsUnpublished`. Writing `status` directly skips all of this.

**`published_at` is write-once.** It is set on the first publish transition and never overwritten. Subsequent unpublish/republish cycles leave it intact. Check `!$news->published_at` before assigning.

**Slug is generated on creation only.** `doNotGenerateSlugsOnUpdate()` is set deliberately. Updating the title does not change the slug. Never regenerate slugs on update — this would break existing URLs.

**Carousel cache key is `news.carousel`, TTL 5 minutes.** Both `NewsObserver` (on model events) and `NewsService` (on explicit workflow actions) bust this cache. When adding a new action that affects pinned/published state, call `NewsService::bustCarouselCache()` explicitly — do not rely solely on the observer.

**`created_by` is nullable; no FK to `users` exists.** This is intentional per architecture rules. User deletion nullifies the field via event listener, not a cascade. Do not add a FK constraint.

**Admin access check in the public `show` route.** Draft articles are accessible to `ADMIN` and `TECH_ADMIN` roles via the regular `/news/{slug}` route for preview. This check uses `AuthPublicApi::hasAnyRole()` — do not duplicate this logic in middleware.

## Registry integrations

- **NotificationFactory** (`Notification` domain) — registers `NewsPublishedNotification` under type `news.published`. This is required for the notification system to reconstruct the notification content from stored data.
- **AdminNavigationRegistry** (`Administration` domain) — registers two admin nav entries: news management (`news.admin.index`) and carousel ordering (`news.admin.pinned.index`), both restricted to `ADMIN` and `TECH_ADMIN` roles.
