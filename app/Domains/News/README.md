# News Domain

The News domain owns news article creation, the publish/unpublish lifecycle, and a pinned carousel displayed on the home page. All article management is restricted to administrators; published articles are publicly readable.

Explicitly out of scope: generic user notifications (delegated to [Notification](../Notification/README.md)), home page layout (delegated to [Home](../Home/README.md)), and audit logging (delegated to [Events](../Events/README.md)).

---

## Key Concepts

### Article status

Each article carries a `status` field with two values: `draft` and `published`. Only published articles are visible to regular users. Admins can preview draft articles via the public `show` route.

`published_at` is set the first time an article transitions to `published`; it is never overwritten by subsequent publish/unpublish cycles. This means `published_at` records when the article was first released, not the most recent re-publish.

### Pinned carousel

Published articles can be individually pinned (`is_pinned = true`) and assigned a `display_order`. The carousel shown on the home page is composed exclusively of pinned, published articles, ordered by `display_order` ascending then `published_at` descending as a tiebreaker.

The carousel result set is cached under the key `news.carousel` for 5 minutes. Any state change that affects carousel membership (pin status, display order, publish status) busts this cache. The `NewsObserver` handles automatic cache invalidation on model events; `NewsService::bustCarouselCache()` is called explicitly after bulk reordering.

### Slug generation

Slugs are auto-generated from the article title using `spatie/laravel-sluggable` with a 60-character limit. The slug is generated **only on creation** (`doNotGenerateSlugsOnUpdate`). Renaming an article does not change its URL, preserving any existing links.

### User deletion safety

`created_by` is intentionally nullable and carries no database foreign key constraint to `users`. When a user is deleted, the `RemoveCreatorOnUserDeleted` listener nullifies `created_by` for all articles they authored. This follows the architecture rule prohibiting cross-domain FK constraints to the `users` table.

---

## Architecture Decisions

**Admin panel lives inside this domain, not in `app/Domains/Admin/`.** News administration uses a classic blade-based admin controller (`Private/Controllers/Admin/NewsController`, `PinnedNewsController`) rather than a Filament resource. The admin navigation entries (news management and carousel ordering) are registered with `AdminNavigationRegistry` from the service provider so they appear in the shared admin sidebar.

**Carousel cache is busted by the observer and the service, not just one place.** The `NewsObserver` busts the cache on any relevant model event; `NewsService::bustCarouselCache()` is also called after explicit actions (publish, unpublish, pin, unpin) and after the reorder endpoint. This double-coverage is intentional — the observer covers model-level changes while the service covers workflow-level actions.

**HTML content is sanitized server-side using HTMLPurifier.** The `NewsService::sanitizeContent()` method runs content through `Purifier::clean()` with an `admin-content` profile and then adds `target="_blank"` to external links. This is applied on both create and update so that any tightening of the purifier config is retroactively applied on save.

**Publish and unpublish are explicit service actions, not just field writes.** Setting `status = published` in the admin form triggers `NewsService::publish()`, which also records `published_at`, emits the `NewsPublished` domain event, and sends a broadcast notification to all users. Setting status back to draft calls `NewsService::unpublish()`, which emits `NewsUnpublished`. These transitions must go through the service layer — writing `status` directly bypasses the event pipeline.

---

## Cross-Domain Delegation

| Concern | Delegated to | Why |
|---------|-------------|-----|
| Carousel data for home page | [Home](../Home/README.md) via `NewsPublicApi::getPinnedForCarousel()` | Home assembles the page; News supplies the data through a typed DTO |
| User notifications on publish | [Notification](../Notification/README.md) via `NotificationPublicApi::createBroadcastNotification()` | Central delivery pipeline shared across all domains |
| Domain event bus | [Events](../Events/README.md) via `EventBus::emit()` | Cross-domain audit log and event routing |
| Admin sidebar registration | [Administration](../Administration/README.md) via `AdminNavigationRegistry` | Shared admin layout owns navigation state |
| Image processing and variants | [Shared](../Shared/README.md) via `ImageService` | Shared image resize/storage utility used across domains |
