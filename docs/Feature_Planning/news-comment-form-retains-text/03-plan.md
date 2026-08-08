# News comment form retains text after submit — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Draft consume-before-restore | S | — | DONE |

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

## Phase 1 — Draft consume-before-restore

**Goal.** After a successful comment create, the matching localStorage draft is
cleared and the compose form does not restore that body — independent of Vite /
`DOMContentLoaded` ordering (architecture §4).

**Architecture.** `02-architecture.md` §4 (Frontend), §6 (Testing), §7 #1–#3.

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/comment-list.blade.php`
  — when `comment.draft_consumed` is in session, emit a dependency-free inline
  marker on `window` (e.g. `window.__commentDraftConsumed = payload`) that does
  not wait on `window.commentDrafts`. Keep or simplify the existing clear call
  so it remains a best-effort secondary path; correctness must not depend on it
  beating restore.
- `app/Domains/Comment/Resources/js/comment-draft/index.js` — at the start of
  form init / bootstrap path: if the marker matches the form's
  `(userId, entityType, entityId)` and scope, call `clearRoot` / `clearReply`,
  skip restore for that scope, then wire autosave as today. Mid-compose restore
  without a marker unchanged. Do not stop flushing drafts on submit.
- `app/Domains/Comment/Resources/js/comment-draft/index.test.js` — new vitest
  colocated with the module (same pattern as Shared/Quote `*.test.js`).

**Tests.**
- Vitest `comment-draft/index.test.js`:
  - With a matching `__commentDraftConsumed` marker for root: after bootstrap,
    `localStorage` root slot is null and the root form textarea stays empty even
    when a prior root draft existed.
  - Same for reply scope / `clearReply`.
  - Without a marker: existing draft still restores into an empty textarea.
  - Marker for a different entityId does not clear or block restore on this form.
- PHP: no new feature tests required; existing
  `CreateCommentControllerTest` `comment.draft_consumed` cases stay green.

**Acceptance.**
- ✅ Matching consumed marker → draft slot cleared and no restore into the form.
- ✅ No marker → unfinished draft still restores.
- ✅ Successful-submit flash still sets `comment.draft_consumed` (existing PHP tests).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| News article, confirmed user | Post a root comment (≥20 chars) → after redirect, root compose form is empty | ✅ |
| News article, confirmed user | Start typing a root, leave without submit, return → draft still restores | ✅ |
| News article, confirmed user | Post a reply → reply composer does not reopen with the submitted body | ✅ |
| Chapter (smoke) | After author's first root, root form still hidden (unchanged) | ✅ |
| Mobile news article | Post root → form empty (same as desktop) | n/a — same client code path; covered by vitest + desktop e2e |

## Open items

None — draft module API (`clearRoot`, `clearReply`, `bootstrap`, `load`) and
flash payload shape verified in current code.
