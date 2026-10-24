# Loop improvements — request

*Standing collector. WRAP's retro (`wrap-task` §0) appends numbered
suggestions here; run the task when the list is worth a pass. Once wrapped, the
next retro starts a fresh one.*

## What I want

Improvements to the loop protocol (`.agents/loop/`), the step skills, the
agent shims, the docs and the dev scripts, surfaced by finished tasks.

*(the previous 22 suggestions were triaged and applied or dropped)*

1. **BUILD marked an e2e acceptance criterion DONE without running it.**
   *(admin-statistics-quotes, phase 5)* The plan's acceptance said "`pnpm run
   e2e` core admin-statistics spec green". The gate only type-checks e2e, so
   the new Citations step had never run. VERIFY found its hover landing one
   slot off and had to rework `AdminStatisticsPage.hoverSlot` (`1527ed0e`).
   Fix: the build skill says that an acceptance line naming `pnpm run e2e` is
   run before the phase goes `DONE`, or reported as "not run" so the phase
   stays `WIP`.
2. **`<x-shared::tabs>` clips on mobile unless `scrollable`.**
   *(admin-statistics-quotes, VERIFY)* A fourth tab on `/admin/statistics`
   was cut off at 375px and scrolled the page sideways (fix `b0cfa03a`).
   PLAN's visual checklist expected the risk, but no phase said to pass the
   prop. Fix: a line in `app/Domains/Shared/AGENTS.md`: "tab strips with more
   than three tabs, or long labels, pass `scrollable`". Or make it the
   default.
3. **Domain events can ship without `registerEvent()`.**
   *(admin-statistics-quotes, DESIGN)* `Quote.ChapterPassageQuoted` had been
   emitted and stored for months but never registered, so stored copies could
   not be rehydrated, replayed or summarised. `Events/AGENTS.md` already warns
   about this, and it still slipped through. Fix: a gate check (or an Events
   test) that every class implementing `DomainEvent` resolves through
   `EventBus::resolveDomainEventClass(X::name())`.

## Why

So that what one task learns the hard way is fixed for the next, instead of
being rediscovered.

## Constraints or ideas I already have

Each item names the task it came from. Skip or merge items freely at REFINE.

## Explicitly out of scope

Findings about a feature itself — those go to the backlog as their own tasks.
