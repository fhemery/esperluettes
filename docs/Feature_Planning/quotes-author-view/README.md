# Quotes — in-chapter author view

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** `01`–`03` remain as
> history.

**Status:** DONE — 2026-08-01 · **Domain(s):** `Quote` (+ `Story`, `Shared`) ·
**Spec:** [functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

On their own chapter, an author sees a « n citations » badge in the header's
metric row, next to an ink-highlighter toggle. The toggle turns on a **heat map**
over the prose: a tint that deepens with the number of quotes covering the text,
plus a right-margin marker (`md+` only) carrying each passage's count. Clicking a
tint or a marker opens a popover listing **who** quoted the passage and when —
never the reader's note. Clicking the badge opens a **chapter summary** popup:
one row per quoted passage with its count; clicking a row turns the heat on,
scrolls to the passage and opens its popover. Passages that no longer exist in
the chapter are listed there as stale — that is how the badge's count is
reconciled with what is tinted.

## Key behaviour

- **Visibility:** only the story's authors (`role = 'author'`, i.e. author and
  co-authors), and only on their own chapters. Not guests, readers, beta
  readers, moderators or admins. `QuotePolicy::canViewChapterAggregate()` gates
  the badge, the heat root **and** the endpoint, so they cannot disagree.
- **Notes never leave the reader.** `AggregateQuoteDto` has no note property and
  `getChapterAggregate()` does not select the column — structural, not
  conditional.
- **Toggle is never persisted.** Off on every page load, every chapter. No
  `localStorage`, no user setting (decision #14).
- **Lifecycle reversal:** a user deleting their account now **hard-deletes** their
  quotes (`DeleteQuotesOnUserDeleted`, raw `DELETE` bypassing the soft-delete
  scope) instead of v1's nullify-and-keep. Deactivate/reactivate still
  soft-delete/restore. No orphan row can exist (decision #5).
- **A quote may not span two blocks.** The mini-form refuses a selection whose
  start and end are in different blocks, with an inline error, and disables save.
  Reason: the canonical text inserts a synthetic boundary space per block that
  maps to no text node, leaving a 1-char untinted hole (decisions #22/#23).
- **Client-side aggregation.** The badge count is a server `COUNT` (correct at
  first paint); the rows are fetched once, on the first toggle or summary open,
  and grouped in the browser. Staleness is only knowable after re-anchoring,
  which is client-side by v1 design.
- **Counter-intuitive:** the tint is built by wrapping depth segments **right to
  left** — `surroundContents()` splits the text node and the original keeps the
  head, so left-to-right would invalidate later `nodeMap` offsets (D8). And one
  canonical segment can span several text nodes: re-split per `nodeMap` entry,
  never one `surroundContents()` per segment.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Quote/Public/Api/QuotePublicApi.php` — `countForChapter`, `getChapterAggregate`, `canViewChapterAggregate` |
| DTOs | `app/Domains/Quote/Public/Api/Contracts/{AggregateQuoteDto,ChapterAggregateDto}.php` |
| Service / policy | `Quote/Private/Services/QuoteService.php`, `QuotePolicy.php` |
| Listener | `Quote/Private/Listeners/DeleteQuotesOnUserDeleted.php` (replaces `NullifyUserOnUserDeleted`) |
| Route / controller | `GET /quotes/chapter-aggregate?chapter_id=` → `Quote/Private/Controllers/ChapterAggregateController.php`; joins the existing `role:user-confirmed` group in `Quote/Private/routes.php` |
| View components | `Quote/Private/View/Components/{AuthorBadge,AuthorHeat}.php` + `Private/Resources/views/components/author-{badge,heat,passage-panel}.blade.php` |
| Host page | `Story/Private/Resources/views/chapters/show.blade.php` — `<x-quote::author-badge>` in the metric row, `<x-quote::author-heat>` wrapping `<article data-quote-article>` |
| JS | `Quote/Resources/js/quote/`: `stores/aggregate-store.js`, `ui/author-heat.js` (heat + passage panel), `ui/author-summary.js` (pure `groupPassages`/`segmentByDepth`), `ui/author-summary-panel.js`, `ui/author-anchoring.js`, `ui/mini-form.js` (multi-block guard) |
| Shared | `Shared/Resources/js/anchoring/block-elements.js` (new), `Shared/Resources/js/tooltip.js` + `views/components/popover.blade.php` (keyboard fix) |
| Story | `StoryPublicApi::getStoryIdByChapterId()` → `ChapterService::getStoryIdByChapterId()` |
| Tests | `Quote/Tests/Feature/{ChapterAggregateTest,AuthorHeatViewTest,QuoteLifecycleTest}.php`; `Story/Tests/Feature/StoryPublicApiTest.php`; vitest beside each JS module |
| Migrations | none — no schema change |

## Extension points used

None. No new notification, domain event, statistics counter, setting, profile
tab or moderation topic (assumption A6 held). The badge composes
`<x-shared::popover>` / `<x-shared::badge>` rather than `<x-shared::metric-badge>`,
whose panel is text-only and cannot host the summary.

## Decisions worth remembering

- **#5** — quotes are hard-deleted with their owner. Reverses the v1 rule
  documented in `app/Domains/Quote/AGENTS.md`; `quotes.user_id` stays nullable
  in the schema but is never null in practice.
- **#15/#16** — raw rows shipped, aggregated client-side; badge count computed
  server-side. Do not "optimise" into a server-side grouping: it cannot mark a
  passage stale.
- **#19/#20** — a beta reader is a *collaborator*, not an author. The policy uses
  `getAuthorIds()`. The misnamed `isAuthorOrCoAuthor()` was removed by the
  prequel task `story-author-check/`, which also made beta readers able to quote.
- **#22/#23** — cross-block quotes are prevented at capture rather than handled
  at render. No backfill: MultiEdit was not deployed, so no legacy cross-block
  row exists. A 1-char seam remains theoretically possible on an old Quill row
  spanning two `<p>`; the fallback (skip boundary chars in segment→DOM) stays
  available.
- **D5** — the summary's grouping key is trimmed + whitespace-collapsed but
  **case-sensitive**. One line of `normalise()` to reverse.

## Plan vs. code

- `03-plan.md` phases 1–8 (plus 5b) are all built. No phase was dropped.
- Decision #17 says a demoted (non-confirmed) author loses the view, and the
  **endpoint** does enforce it via the `role:user-confirmed` route group.
  `canViewChapterAggregate()` itself checks authorship only, so a demoted author
  would still be *rendered* the badge and heat root and get a 403 on fetch.
  Judged acceptable at DESIGN (story creation already requires `user-confirmed`),
  but it is a real asymmetry — do not assume the two gates are identical.
- `ChapterAggregateDto::totalCount` is `count($items)` after dropping rows whose
  profile cannot be resolved, whereas the badge uses a separate `COUNT(*)`. They
  agree only because hard-delete guarantees no unresolvable row.

## Not done

- **Deliberate non-goals** (§8 of the spec): any exposure of the reader's note,
  moderator/admin access, statistics on quotes received, a story-level view,
  export/sort/filter, mark-as-seen, and any reader-visible aggregate or
  indication that the author sees one.
- **Assumptions never arbitrated** (all cheap to reverse): A1 co-authors see the
  same view; A4 reader list newest-first with avatar/name/relative date linking
  to the profile page; A5 badge counts stale quotes too; A6 no new extension
  point; A7 zero-quote badge is inert; A8 summary ordered by count, stale last.
- **e2e specs retired at WRAP** (decision #24): `quotes-author-view.spec.ts` and
  its page objects `AuthorHeat.ts` / `QuoteCapture.ts` are **deleted** per
  `e2e/tests/features/README.md`. The seeding infrastructure is **kept and
  committed** — `E2eQuotesSeeder`, the beta-reader account, the illustrated
  chapter, and the `QUOTES` / `illustratedChapter` fixture blocks — because
  seeding runs once at global setup and `quotes-moderation/` will want exactly
  this data. The `QUOTES` fixtures therefore have no consumer for now; that is
  intended, keep the doc comments.
- **Backlog rows carried out of this task:** `gate-scoped-test-paths/` (the
  scoped gate run dies on branches touching ≥2 domains — this task was gated with
  `--all` throughout) and `quotes-moderation/` (already queued).
