# News — pin to carousel as first

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** `News`

## What it does

When an article becomes pinned without a `display_order`, it is inserted at
carousel position **1**. Other pinned rows are shifted +1 via a query-builder
`increment` (no per-sibling `NewsUpdated`). Manual reorder on the pinned-news
admin page is unchanged. Public carousel sort remains `display_order` ASC.

## Key behaviour

- **Newly pinned** = create with pin, or edit that turns pin on with empty
  order (same rule; request named creation).
- **Already pinned + save** does not reshuffle to first.
- **Unpin** clears `display_order` (existing).
- **Roles / public membership** unchanged — carousel is still pinned + published.
- **Unsigned `display_order`** — insert-first uses shift+set-1, not min−1.

## Where the code lives

| Concern | Path |
|---------|------|
| Order assignment | `News/Private/Observers/NewsObserver.php` (`assignFirstDisplayOrder`) |
| Create/update entry | Admin store/update → model save → observer |
| Public carousel | `NewsPublicApi::getPinnedForCarousel()` (unchanged) |
| Manual reorder | `PinnedNewsController::reorder` (unchanged) |
| Tests | `News/Tests/Feature/Admin/PinToCarouselOrderTest.php` |
| Migrations | none |

## Extension points used

None.

## Decisions worth remembering

1. **Shift others +1, set new = 1** — fits unsigned column; matches reorder’s 1…N habit.
2. **Keep logic in `NewsObserver`** — create/update already assigned order there.
3. **Query-builder increment** — siblings must not each emit `NewsUpdated`.
4. **Edit first-time pin = same as create** (assumption A1).

## Not done

- Deliberate non-goals: white-screen pin checkbox UI (`news-pin-carousel-white-screen/`); role/UI/schema changes; calling unused `NewsService::pin()`/`unpin()` from controllers.
- VERIFY / full `npm run gate` skipped at user request after targeted PHP tests + pre-commit deptrac/vitest.
- No new backlog leftovers.
