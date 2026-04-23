# FAQ Domain

## Purpose and scope

This domain owns the site FAQ: categories of questions and answers, their display on the public `/faq` page, and the admin interface that lets staff manage them. It does not handle search across FAQ content, user-submitted questions, or moderation workflows — those concerns belong to other domains.

## Key concepts

### Categories and questions

FAQ content is organised in a two-level hierarchy: categories contain questions. Both have an `is_active` flag that controls visibility on the public page and an integer `sort_order` that determines display order (lower values first). The admin panel in Filament supports drag-and-drop reordering via the `reorderable('sort_order')` behaviour, which persists directly to `sort_order`.

Deleting a category cascades to its questions (enforced at the service layer, not by a database FK to `users` — see architecture note below).

### Active-only public view

The public FAQ page only shows active categories and their active questions. Inactive categories are hidden from the tab list entirely. An inactive question is hidden even if its category is active. Only admins, tech-admins, and moderators can toggle visibility.

### Slug auto-generation

Category slugs are derived from the category name; question slugs are derived from the question text. Both use `spatie/laravel-sluggable`. Slugs can be overridden explicitly when creating or updating via the Public API or the Filament resource (which auto-populates the slug field from the name on blur but allows manual override).

### Answer HTML sanitisation

Question answers are stored as HTML and are sanitised through `Purifier::clean($answer, 'admin-content')` on every create or update. The sanitisation profile is `admin-content`, which is permissive enough for formatted content but still protects against XSS.

### Caching

The public page data (active categories list and questions per category) is cached for one hour via `FaqCache`. Any mutation (create, update, delete, activate, deactivate) flushes the entire application cache via `Cache::flush()`. This is intentional for simplicity but means that any high-frequency cache sharing across domains will also be flushed on FAQ changes.

## Architecture decisions

**Filament resources live in the Admin domain, not here.** `FaqCategoryResource` and `FaqQuestionResource` reside in `app/Domains/Admin/Filament/Resources/FAQ/`. This is the project-wide pattern: Filament UI lives in Admin; the FAQ domain provides the data and business logic. The Admin resources call `FaqPublicApi` for write operations.

**Public API enforces RBAC; the service layer does not.** Role checks (admin, tech-admin, moderator) are applied in `FaqPublicApi` before delegating to `FaqService`. `FaqService` itself has no auth checks. This means calling `FaqService` directly bypasses authorisation — always go through the Public API.

**No FK constraint to `users`.** The `created_by_user_id` and `updated_by_user_id` columns record audit trail data but have no database foreign key to the `users` table, in line with the cross-domain FK policy of this project.

**Tab-based navigation uses URL segments, not query strings.** The public FAQ page is reachable at `/faq` (defaults to the first active category) and `/faq/{categorySlug}` (opens the named category tab). If the requested slug does not match any active category, the page falls back to the first active category silently rather than returning 404.

## Cross-domain delegation

| Concern | Handled by |
|---------|-----------|
| Authentication and role checks | Auth domain via `AuthPublicApi::hasAnyRole()` |
| Admin UI rendering | Admin domain Filament resources |

## Admin panel

The Filament resources for FAQ categories and questions are located at:
- `app/Domains/Admin/Filament/Resources/FAQ/FaqCategoryResource.php`
- `app/Domains/Admin/Filament/Resources/FAQ/FaqQuestionResource.php`

Access to these resources is restricted to users with the `admin` or `tech-admin` role (the `canAccess()` override excludes moderators from admin panel access, even though the Public API also permits moderators).
