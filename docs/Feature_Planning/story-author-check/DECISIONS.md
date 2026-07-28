# Story — one author check, not two — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-28 | REFINE | Mode | `auto` — request already answers the functional question (remove misnamed API, pin beta-reader-can-quote) | — |
| 2 | 2026-07-28 | REFINE | Remove `isAuthorOrCoAuthor`? | Yes; `isAuthor` is the single public author check (from `00-request.md`) | — |
| 3 | 2026-07-28 | REFINE | May beta readers quote? | Yes — intentional; they are readers (from `00-request.md`) | — |
| 4 | 2026-07-28 | REFINE | Authors / co-authors quoting own story? | Still blocked (product rule unchanged) | — |
| 5 | 2026-07-28 | DESIGN | Leave `getCollaboratorIds` on StoryService? | Keep private; only remove the public lie | — |
| 6 | 2026-07-28 | DESIGN | Add public `isCollaborator()` now? | No | — |
| 7 | 2026-07-28 | DESIGN | Co-author regression test? | Yes, add it | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | No Blade / UI change is required — chapter show already gates the quote button with author-only `$vm->isAuthor` | REFINE | Yes — only if we want extra UI copy for beta readers |
| 2 | No Story domain README/AGENTS update is required for the removed method — it was undocumented there; only Quote/AGENTS.md must change | REFINE | Yes |
| 3 | Add a regression test that a co-author cannot quote, plus the required beta-reader-can-quote pin | REFINE | Yes — co-author case is redundant with author if roles stay identical |
| 4 | Confirmed-user gate for `canQuote` stays exactly as today | REFINE | Yes |
| 5 | No new public `isCollaborator()` in this task | REFINE | Yes — deferred until a real caller exists |
| 6 | Single BUILD phase is enough (API + policy + tests + docs) | PLAN | Yes |
| 7 | VERIFY is a light smoke (beta reader can quote); no new screenshots folder required if run-app is heavy — still attempt checklist | PLAN | Yes |
| 8 | Skip Playwright for VERIFY — no UI change; checklist filled from feature tests + existing Blade author-only gate | VERIFY | Yes |
