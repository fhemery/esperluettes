# StaticPage Domain

## Purpose and scope

The StaticPage domain manages manually authored pages (e.g. "About", "FAQ", "Legal") that site administrators create and publish through an internal admin panel. It owns the `static_pages` table and is solely responsible for the page lifecycle: creation, editing, publication, unpublication, and deletion.

Out of scope: dynamically generated content (news, stories), the global FAQ system (owned by the FAQ domain), and anything requiring user-facing creation.

---

## Key concepts

### Page lifecycle — draft and published

Every page starts as a `draft`. Admins explicitly publish or unpublish it. Only published pages appear in the public slug map and are accessible to regular visitors. Admins can preview draft pages by navigating directly to their slug URL.

`published_at` is set once when a page is first published and is never reset on subsequent republications. This preserves the original publication timestamp even after an unpublish/republish cycle.

### Catch-all slug routing

The public show route is a catch-all that matches any slug at the root path (`/{slug}`) — for example `/about` or `/terms`. It is registered last in the provider boot sequence so that it never intercepts routes owned by other domains (stories, news, auth, admin, etc.). A regex exclusion list in the route definition hard-codes known domain prefixes to prevent collisions.

Because this route is a catch-all, the order of service provider registration in `bootstrap/providers.php` is load-bearing: `StaticPageServiceProvider` must boot after all other domain providers that define root-level routes.

### Slug-map cache

To avoid a database query on every public page request, the domain maintains a slug-to-id map in the application cache (`static_pages:slug_map`, key constant on `StaticPageService`). This map contains only published pages. It is rebuilt eagerly on every save, publish, unpublish, and delete — so the cache is always in sync and never serves stale data for routing. The initial cache is built lazily with a 1-hour TTL; subsequent rebuilds use `Cache::forever`.

### Slug generation

Slugs are auto-generated from the page title by the `spatie/sluggable` package on creation only. Slugs are intentionally not regenerated on update (`doNotGenerateSlugsOnUpdate`) to avoid breaking published URLs. To rename a page's public URL, an admin must manually update the slug.

### HTML sanitization

Page content submitted through the admin form is sanitized using `Purifier::clean()` with the `admin-content` configuration, then post-processed by `HtmlLinkUtils::addTargetBlankToExternalLinks()` to ensure all external links open in a new tab. This happens in the service layer on both create and update.

### Header image management

Pages may carry an optional header image. The `Shared::ImageService` processes uploads into multiple responsive widths (400px and 800px variants) stored under `public/static-pages/{year}/{month}/`. When an image is replaced or removed, the previous file and all its responsive variants are deleted from storage.

### Creator attribution and user deletion

The `created_by` column records which admin created the page. It is intentionally nullable (see second migration). When an `Auth::UserDeleted` event fires, the domain nullifies `created_by` for all pages authored by that user — pages are preserved, only the attribution is removed. There is no foreign key constraint on `created_by` to comply with the architecture rule that cross-domain FK constraints to the `users` table are forbidden.

---

## Architecture decisions

- **Admin panel is domain-internal, not Filament-based.** Unlike some other domains, the admin UI for static pages lives inside this domain's own Private controllers and Blade views (`Private/Controllers/Admin/`, `Private/Resources/views/pages/admin/`), not in `app/Domains/Admin/`. The Administration domain's navigation registry is used only to surface the link in the shared admin sidebar.

- **Observer supplements the service for cache invalidation.** The `StaticPageObserver` ensures the slug-map cache is rebuilt even when pages are modified outside the `StaticPageService` (e.g. direct Eloquent calls in tests). This makes cache correctness robust rather than relying solely on disciplined service usage.

- **`StaticPageDeleted` is emitted twice in the service/observer.** The observer's `deleted` hook and `StaticPageService::delete()` both emit `StaticPageDeleted`. This is a known redundancy: the service emits before the observer fires. Other domains consuming this event should be idempotent if they receive it twice. Prefer triggering deletions through the service to control emission order.

---

## Cross-domain delegation map

| Concern | Delegated to | Why |
|---|---|---|
| HTML sanitization | `Shared::HtmlLinkUtils`, `Mews\Purifier` | Central sanitization config lives in Shared |
| Image upload and resizing | `Shared::ImageService` | Reusable responsive image pipeline |
| Admin sidebar navigation | `Administration::AdminNavigationRegistry` | Shared admin nav managed centrally |
| Domain event bus | `Events::EventBus` | Cross-domain event backbone |
| User role checks (admin preview) | `Auth::AuthPublicApi`, `Auth::Roles` | Auth domain owns role resolution |
