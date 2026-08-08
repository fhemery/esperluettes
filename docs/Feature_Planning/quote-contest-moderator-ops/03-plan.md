# Quote contest — moderator operations — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Moderator category route access | S | — | DONE |

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

## Phase 1 — Moderator category route access

**Goal.** Let moderators call the three quote-contest category CRUD routes, matching Calendar activity admin and News management parity.

**Architecture sections.** `02-architecture.md` §3.3 (policy / authorization), §3.5 (routes), §6 (testing strategy).

**Deliverables.**
- `app/Domains/Calendar/Private/Activities/QuoteContest/Http/routes.php` — widen the category route group middleware from `'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN` to `'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN . ',' . Roles::MODERATOR` (same construction style as `app/Domains/Calendar/Private/routes.php` line 16). Add or adjust the comment above the group so it documents the three staff roles; do not leave wording that implies categories are admin/tech-admin only.
- `app/Domains/Calendar/Private/Activities/QuoteContest/Http/Controllers/QuoteContestModerationController.php` — update the docblock on `ROLES` (lines 24–28) that still says *"Calendar's `[admin, tech-admin]` gates *configuration*"*; category configuration now includes moderators while content moderation continues to use this constant unchanged.
- `app/Domains/Calendar/Tests/Feature/QuoteContest/AdminCategoryTest.php` — split the combined denial test so moderators are no longer grouped with intruders.

No changes to controllers, form requests, Blade, services, deptrac, or domain README unless BUILD discovers another route still on the old two-role middleware (none found at plan time).

**Tests.**
- **First (red):** refactor `it('denies every category route to a confirmed user and to a moderator')` into:
  - `it('denies every category route to a confirmed user')` — `alice($this)` only; POST store, PUT update, DELETE destroy each `assertRedirect(route('dashboard'))`; category row unchanged.
  - `it('lets a moderator add, edit and delete an empty category')` — `moderator($this)`; POST store succeeds (`assertSessionHasNoErrors`), PUT update succeeds, DELETE destroy on an empty category succeeds (`assertSessionHas('success')`); assert persisted title/count as the existing admin happy-path tests do.
- **Regression (must stay green, no edits expected):** `it('lets an admin add, edit and reorder categories')`, `it('deletes an empty category')`, `it('refuses to delete a category holding an entry, with a message')`, `ModerationDeleteTest`, `ResultsTabTest`, Calendar `ActivityControllerTest` moderator cases.

Run scoped gate during iteration:

```bash
./vendor/bin/sail artisan test app/Domains/Calendar/Tests/Feature/QuoteContest/AdminCategoryTest.php
```

**Acceptance.**
- ✅ A moderator POSTing to `calendar.admin.quote-contest.categories.store` persists the category and does not redirect to the dashboard.
- ✅ A moderator PUTting `calendar.admin.quote-contest.categories.update` updates the category.
- ✅ A moderator DELETEing an empty category via `calendar.admin.quote-contest.categories.destroy` succeeds with the existing success flash.
- ✅ A confirmed user without staff roles (`alice($this)`) is still redirected to the dashboard on all three category routes; no category rows created or mutated.
- ✅ Destroy-non-empty category behaviour unchanged (existing admin tests still pass).
- ✅ Content moderation routes and `QuoteContestModerationController::ROLES` unchanged.
- ✅ Stale “admin/tech-admin only configuration” comment on the category route group or `QuoteContestModerationController` docblock is corrected.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Activity edit — categories panel (moderator) | Log in as moderator, open an existing Concours de citations edit page; categories block is visible; add a category with a title; submit succeeds and the new row appears without redirect to dashboard. | |
| Activity edit — categories panel (moderator, empty contest) | On a contest with no categories yet, empty list plus add form works; first category saves. | |
| Activity edit — edit/delete category (moderator) | Edit an existing category title; delete an empty category; both succeed with today’s flash behaviour. | |
| Activity edit — delete blocked (moderator) | Category holding an entry (even withdrawn) refuses destroy with the existing French error flash — same as admin today. | |
| Activity edit — categories panel (admin) | Admin category CRUD still works (regression spot-check). | |
| Calendar admin (confirmed user, non-staff) | Confirmed user without moderator/admin/tech-admin cannot reach category write routes (dashboard redirect if forced). | |
| Mobile width | Categories block on activity edit remains usable at a narrow viewport (forms submit, no layout break). | |

## Open items

| Item | Phase | Notes |
|------|-------|-------|
| No other quote-contest write routes on old middleware | 1 | Verified at plan time: activity CRUD uses `Calendar/Private/routes.php` (already includes `MODERATOR`); contest dates persist via activity form through `CalendarPublicApi` (same three roles); content moderation uses `QuoteContestModerationController::ROLES` in the controller. BUILD should re-scan `QuoteContest/Http/routes.php` only. |
| Domain README category-role wording | — | `QuoteContest/README.md` does not state admin-only category routes; no README edit required for acceptance. WRAP may note moderator parity when updating Calendar domain docs if desired. |
| E2E moderator category flow | VERIFY | Optional sanity in real browser; not a gate requirement. Functional spec §4.1 is covered by feature tests. |
