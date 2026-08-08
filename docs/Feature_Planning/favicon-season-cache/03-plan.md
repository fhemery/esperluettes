# Favicon — stays the same when season changes — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Season query on favicon links | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Season query on favicon links

**Goal.** Favicon `<link>` hrefs in the shared head carry `?theme=<resolved season>` so a season change yields a distinct URL browsers will refetch.

**Architecture.** `02-architecture.md` §4 (Frontend), §6 (Testing), §7 tradeoffs 1–3.

**Deliverables.**
- `app/Domains/Shared/Resources/views/layouts/partials/head.blade.php` — for each favicon `<link>`, use `$theme->asset('favicons/…')` with a clean path (no query inside `asset()`), then append `?theme={{ $theme->value }}`. Remove hardcoded `?v=20260425`.
- `app/Domains/Shared/Tests/Feature/FaviconThemeQueryTest.php` — new feature test.

**Tests.**
- `FaviconThemeQueryTest` — guest (or auth user) GET a layout page; with user theme preference fixed to `spring` (via existing settings helpers / `ThemePreferenceTest` patterns), assert response HTML contains favicon hrefs for ico + PNG sizes ending with `?theme=spring` (path still under `/images/themes/spring/favicons/`), and does **not** contain `?v=20260425`.
- Same file: second case with preference `winter` → `?theme=winter`.

**Acceptance.**
- ✅ All five favicon links use `?theme=<season>` matching the resolved theme.
- ✅ Static `?v=20260425` is gone from the head partial.
- ✅ Query is appended outside `Theme::asset()`, not inside the path argument.
- ✅ `npm run gate` green.

---

## Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Any page (guest) | View-source / network: favicon URLs include `?theme=` for current seasonal theme | |
| Settings → change theme → reload | Tab icon matches the chosen season (may need hard refresh once) | |
| Mobile viewport | Same favicon links present in head (no separate mobile chrome) | |

## Open items

None — `Theme::asset()`, `$theme` View share, and settings helpers are confirmed in existing Shared tests.
