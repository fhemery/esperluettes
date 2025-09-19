---
Title: Global Search (Stories + Profiles)
Status: Proposal
Updated: 2025-09-19
---

# Goal
Visibility-aware global search from the top bar. Returns Stories and Profiles and renders results live below the input using Blade partials. Desktop: panes side-by-side. Mobile: stacked, Stories first.

# Confirmed UX
- Debounce: 300 ms; min chars: 2.
- Result cap per type: 25 total, paginated internally 5 per page (max 5 pages).
- No full results page for now; users refine.
- Loading: inline spinner; empty states per pane.
- Keyboard: arrows to navigate, Enter open, Tab switches pane, Esc/blur closes.
- Highlight matches; accent- and case-insensitive.
- i18n: PHP namespaced translations.

# Architecture
New domain: `app/Domains/Search/` (follow Domain Structure; only create folders we need)
- `Private/Controllers/SearchController.php` (thin)
- `Private/Services/SearchService.php` (coordinates cross-domain calls)
- `Private/Resources/views/partials/` (one combined partial rendering both panes + cards with client-side pagination)
- `Public/Providers/SearchServiceProvider.php` (register routes, views, translations)
- `Tests/Feature/`

Cross-domain access via Shared Contracts (no direct calls into Story/Auth domains):
- Extend `app/Domains/Shared/Contracts/ProfilePublicApi.php` with `searchPublicProfiles(string $query, int $limit = 25): array{items: ProfileSearchResultDto[], total: int}`.
- Add `app/Domains/Story/Public/Api/StoryPublicApi.php` with `searchStories(string $query, int $limit = 25, ?int $viewerUserId = null): array{items: StorySearchResultDto[], total: int}`.
  - calls internal services and uses `ProfilePublicApi` to resolve author display names.

The SearchService depends on these Public APIs; the APIs enforce visibility and matching rules and return up to 25 items for client-side pagination.

# Endpoint (single partial)
- GET `/search/partial` → one Blade partial that includes BOTH panes (server returns up to 25 per type; Alpine paginates client-side into 5 blocks of 5)
  - Query params:
    - `q` (string, required, min 2)
    - `stories_page` (int, default 1)
    - `profiles_page` (int, default 1)
    - `per_page` (int, default 5, max 5)
  - The partial renders side-by-side (desktop) or stacked (mobile) panes, with client-side pagers that iterate pages without re-hitting the server.
  - The wrapping elements include `data-total-stories` and `data-total-profiles` (full counts); headers show “Stories (shown/total)” and “Profiles (shown/total)”.

The top bar Alpine component issues a single debounced GET and swaps the whole partial’s HTML. Pagination controls within either pane reload the same endpoint with updated `stories_page`/`profiles_page` while preserving the other pane’s current page.

# Visibility & Matching
Story visibility:
- Guest or role `user` (non-confirmed): only `public`.
- Role `user-confirmed`: `public` + `community` + `private` where user is a collaborator.

Profiles:
- Exclude suspended via soft deletes (`whereNull(deleted_at)`).

Fields & matching:
- Stories: `title` always; `summary` only when `strlen(q) > 4` (to reduce noise). Partial match.
- Profiles: `display_name` partial match.
- Production collation is `utf8mb4_unicode_ci`. If accent-insensitive behavior requires it, we will introduce normalized searchable columns (ascii-folded + lowercased) with indexes, and use them for LIKE queries.

# Highlighting
- Server-side: normalize text and query, find ranges on normalized, map to original, wrap with `<mark>` (or strong class). Escape content, only inject tags.
- Fields highlighted: story title and summary snippet (~120 chars), profile display_name.

# Rendering (single combined partial)
- Stories pane: header with counts; list of small story cards (cover fallback, highlighted title, authors comma-joined); pager prev/next.
- Profiles pane: header with counts; list of small profile cards (avatar fallback, highlighted display_name); pager.
- Semantic roles and aria labels for a11y.

# Routes
In `app/Domains/Search/Private/routes.php` registered by a `SearchServiceProvider`:
- `Route::get('/search/partial', [SearchController::class,'partial'])->name('search.partial');`
- Optional: `Route::get('/search/summary', [SearchController::class,'summary'])->name('search.summary');`

# Indexing & Migrations
- Index stories `title` (and `summary` as feasible, e.g., prefix index for TEXT) or index normalized searchable columns.
- Index profiles `display_name` (or its normalized column).
- Migrations live in their respective domains.

# Testing (Feature)
- Visibility: guest vs confirmed vs collaborator cases for stories.
- Matching: partial + accent-insensitive for titles, and summaries only when query length > 4; profiles by display_name.
- Pagination and 25-cap behavior; headers show correct counts; independent stories/profiles paging retained via query params.
- Combined partial contains both panes, highlight markup, and expected a11y attributes.

# Open Items
1) Confirm DB collation availability; otherwise we’ll ship normalized searchable columns + indexes.
2) Author relation naming and URL helpers to build links in partials.
3) Preferred tag for highlight: `<mark>` (default) vs `<strong>`.
