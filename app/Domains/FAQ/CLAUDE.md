# FAQ Domain — Agent Instructions

- README: [app/Domains/FAQ/README.md](README.md)

## Public API

- Public API: [FaqPublicApi](Public/Api/FaqPublicApi.php) — CRUD for categories and questions, activate/deactivate questions; all write operations require admin, tech-admin, or moderator role (checked inside the API, not the service)

## Events emitted

This domain emits no domain events.

## Listens to

This domain registers no cross-domain event listeners.

## Non-obvious invariants

**Role checks are in the Public API, not the service.** `FaqService` has no auth checks. Calling it directly bypasses RBAC. All mutations from outside this domain must go through `FaqPublicApi`.

**The Filament `canAccess()` access control is stricter than the Public API.** The Public API allows `admin`, `tech-admin`, and `moderator` to write. The Filament resources in the Admin domain restrict `canAccess()` to `admin` and `tech-admin` only — moderators are excluded from the Filament UI even though they can call the API. Do not make these consistent without understanding which is intentional.

**Any mutation clears the full application cache.** `FaqCache::clear()` calls `Cache::flush()`, not a targeted invalidation. Every service-layer write (create, update, delete, activate, deactivate) triggers this. This will also evict cache entries from other domains.

**Deleting a category cascades to its questions at the service layer.** There is no database-level cascade. If you bypass the service and delete a category via Eloquent directly, orphaned questions will remain.

**Answer HTML is sanitised on every save.** `FaqService` calls `Purifier::clean($data['answer'], 'admin-content')` on both create and update. Unsanitised HTML passed directly to the model will not be cleaned automatically.

**Slug auto-generation uses `spatie/laravel-sluggable`.** Category slugs source from `name`; question slugs source from `question`. Slugs can be provided explicitly (the service and API accept them). The Filament resource auto-populates the slug field on blur but allows override. If you change the `name` or `question` of an existing record without specifying a slug, the slug will not update automatically (Spatie's `doNotGenerateSlugsOnUpdate` is the default unless configured otherwise — verify in `getSlugOptions()` before relying on auto-update).

**The public FAQ page falls back silently on an invalid category slug.** A request to `/faq/{badSlug}` does not return 404; it renders the first active category's questions instead. This is by design in `FaqController::index()`.
