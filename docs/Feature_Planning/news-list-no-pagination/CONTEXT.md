# News list without pagination — context

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### DESIGN+PLAN → BUILD (2026-11-01)

REFINE ran with no question (auto mode); design and plan written inline. One
phase. Trap found: the public view never rendered pagination links, so with
`paginate(12)` news beyond the 12th were unreachable — the change is a real fix.
Assumption likeliest to be reversed: scope is the public list only (admin list
keeps its `paginate(20)` and `<x-shared::pagination>`). Check the view's
`@forelse($news as $news)` shadowing still works with a Collection (it does).

### BUILD phase 1 -> VERIFY (2026-11-01)

Done as planned, no deviation. Only visual QA of `/news` remains.

### VERIFY -> WRAP (2026-11-01)

PASS. Row is essentially covered by the PHP feature test; one browser look at
`/news` (admin, dev DB) rendered grid, no pager. No spec written. Only known
noise: pre-existing 404 on storage/news webp. Nothing flaky or manual.

## 1. Functional spec

### 1.1 Overview

The public news list (`news.index`) shows every published news item on one
page, with no page limit and no pagination controls. Value: no news is hidden
behind a page that cannot be reached.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Liste des actualités | Public page `news.index`, grid below the pinned carousel |

### 1.3 Roles & visibility

Unchanged: everyone (guests included) sees all published news; drafts stay hidden.

### 1.4 Functional requirements

1. Visiting `news.index` lists all published news, in the current listing order.
2. No pagination controls and no `?page=` behaviour.

### 1.5 Lifecycle

N/A — display only; nothing stored.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles / visibility | Unchanged |
| Settings, notifications, events, statistics, moderation, media, search | N/A — no change |
| Lifecycle / cascade | N/A |
| i18n | N/A — no new text |
| Mobile / accessibility | Unchanged grid |

### 1.7 Out of scope

- Admin news list pagination (stays `paginate(20)`).
- Lazy loading, infinite scroll, or a cap on the number of items.

### 1.8 Open questions

None blocking.

## 2. Architecture

Single change in `News` domain: `NewsController::index` fetches
`News::query()->published()->orderForListing()->get()` instead of `paginate(12)`.
View already renders without paginator links. No data, deptrac or API change.
Testing: one integration test in `NewsListTest` (more than 12 published items all
appear). Risk: very large lists would load fully; revisit if the news count reaches
hundreds.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Remove pagination from public news list | S | — | DONE |

### Phase 1 — Remove pagination from public news list

**Goal.** Public list shows all published news. **Reads.** §2.

**Deliverables.**
- `app/Domains/News/Private/Controllers/NewsController.php`: `paginate(12)` -> `get()`.
- `app/Domains/News/Tests/Feature/NewsListTest.php`: new test.

**Tests.** `it('lists all published news beyond twelve items')` — create 15
published items, assert every slug link is present on `news.index`. Written first
(fails with 12 cap).

**Acceptance.**
- ✅ New test passes; existing News tests pass.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| `/news` | All published items shown, no pager | ✅ (dev DB has only 2 items, so no-cap proof is the PHP test; browser confirms render + 0 pager elements) |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | "News list" means the public list; admin list keeps pagination | REFINE | Yes |
| 2 | No cap on item count | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE: `paginate(12)` -> `get()`; test `lists all published news beyond twelve items` added (failed first, now passes).
- VERIFY (2026-11-01): PASS. `/news` driven in browser: renders, 0 pager elements; screenshot viewed. No e2e spec (render-only, covered by feature test).
