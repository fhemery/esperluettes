# Story — one author check, not two — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads one phase at a time.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Policy + API + tests + docs | S | — | DONE |

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

## Phase 1 — Policy + API + tests + docs

**Goal.** Beta readers can quote; authors/co-authors still cannot; the misnamed public method is gone.

**Deliverables.**
- `app/Domains/Quote/Tests/Feature/QuoteControllerTest.php` (or adjacent feature test) — add beta-reader-can-quote and co-author-cannot-quote cases; keep existing author-cannot-quote.
- `app/Domains/Quote/Private/Services/QuotePolicy.php` — `isAuthor($userId, $storyId)`.
- `app/Domains/Story/Public/Api/StoryPublicApi.php` — remove `isAuthorOrCoAuthor()`.
- `app/Domains/Quote/AGENTS.md` — `canQuote` documents `isAuthor`, authors blocked, beta readers not.

**Tests.**
- `author cannot quote their own story` (existing) — still passes.
- `beta reader can quote a story they beta-read` — POST succeeds (201/200 as peers).
- `co-author cannot quote a story they co-author` — 403.

**Acceptance.**
- ✅ `isAuthorOrCoAuthor` does not exist on `StoryPublicApi`.
- ✅ `QuotePolicy::canQuote` uses `isAuthor` only.
- ✅ Confirmed beta reader can create a quote on that story.
- ✅ Author and co-author cannot.
- ✅ `Quote/AGENTS.md` no longer mentions `isAuthorOrCoAuthor`.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. Backend-aligned UI (chapter quote button) — light smoke only.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter show as beta reader (`user-confirmed`) | "Citer" visible; creating a quote succeeds (no 403) | |
| Chapter show as author | Quote action hidden / cannot create | |
| N/A — no new UI chrome | — | — |

## Open items

None — `isAuthor`, collaborator helpers, and the single caller are confirmed in code.
