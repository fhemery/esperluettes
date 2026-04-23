# StaticPage Domain — Agent Instructions

- README: [app/Domains/StaticPage/README.md](README.md)

---

## Events emitted

All events are registered with the `Events::EventBus` and implement `DomainEvent`.

- `StaticPage.Published` (`StaticPagePublished`) — when a page transitions from draft to published; carries `pageId`, `slug`, `title`, `publishedAt`
- `StaticPage.Unpublished` (`StaticPageUnpublished`) — when a page transitions from published to draft
- `StaticPage.Updated` (`StaticPageUpdated`) — fired by the observer on any save that changes fields; carries `changedFields` array
- `StaticPage.Deleted` (`StaticPageDeleted`) — on page deletion

---

## Listens to

- `Auth::UserDeleted` → nullifies `created_by` on all pages authored by that user (pages are kept, attribution is removed)

---

## Non-obvious invariants

**Slug-map cache must stay in sync.** The public show controller resolves pages via a cached slug-to-id map (`static_pages:slug_map`), not a live query. Any code path that creates, updates, publishes, unpublishes, or deletes a page must result in a cache rebuild. The observer handles this for direct Eloquent saves; the service handles it explicitly for publish/unpublish/delete. Do not bypass both.

**`StaticPageDeleted` is emitted twice on service-initiated deletion.** `StaticPageService::delete()` emits the event directly, and the observer's `deleted()` hook also emits it. If you add a listener for `StaticPageDeleted`, make the handler idempotent. Do not add a third emission path.

**Slug is never auto-regenerated on update.** `doNotGenerateSlugsOnUpdate` is set intentionally. Changing a page's title does not update its public URL. Only explicit slug edits do. This preserves live URLs on content edits.

**`published_at` is never reset on republication.** The service only sets `published_at` when it is null (first publish). Do not overwrite it on subsequent publishes.

**Catch-all route must boot last.** The public `/{slug}` route uses a regex exclusion list to avoid collisions, but relies on provider boot order. `StaticPageServiceProvider` must remain registered after all domain providers that define root-level web routes. If adding new root-level routes to another domain, verify the exclusion regex in `routes.php` does not need updating.

**`created_by` has no FK constraint.** This is intentional — cross-domain FK constraints to `users` are forbidden by architecture rules. Do not add one.

**Admin preview of drafts.** The public show controller allows `ADMIN` and `TECH_ADMIN` roles to view draft pages by direct slug lookup, bypassing the slug-map cache. This is intentional for preview purposes. Do not remove this branch.

---

## Admin panel

The admin UI lives inside this domain (`Private/Controllers/Admin/`, `Private/Resources/views/pages/admin/`). It is not a Filament resource. The domain registers a navigation entry in the `Administration::AdminNavigationRegistry` to surface the link in the shared admin sidebar. Admin routes require `auth`, `compliant`, and `role:admin,tech-admin` middleware.
