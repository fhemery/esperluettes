# News list without pagination

**Status:** DONE — 2026-11-01 · **Domain(s):** `News`

## What it does

The public news list (`news.index`) shows every published news item on one page,
below the pinned carousel. The previous `paginate(12)` never rendered pager
links, so news beyond the 12th were unreachable; this was a real fix.

## Key behaviour

- Everyone (guests included) sees all published news; drafts stay hidden.
- Order unchanged (`orderForListing()`); no cap, no `?page=` behaviour.
- Admin news list is untouched: still `paginate(20)` with `<x-shared::pagination>`.
- The view's `@forelse($news as $news)` shadowing works with a Collection.

## Where the code lives

| Concern | Path |
|---------|------|
| Controller | `app/Domains/News/Private/Controllers/NewsController.php` (`index`: `->get()`) |
| Tests | `app/Domains/News/Tests/Feature/NewsListTest.php` (`lists all published news beyond twelve items`) |

## Decisions worth remembering

- "News list" means the public list only; admin keeps pagination.
- No cap on item count (plan matched code; no deviation).

## Not done

- Non-goals: admin list pagination, lazy loading / infinite scroll, item cap.
- Risk: the full list is loaded; revisit if the news count reaches hundreds.
- No e2e spec written (render-only, covered by the feature test).
