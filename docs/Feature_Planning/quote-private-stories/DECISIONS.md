# Quotes — private stories — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-28 | REFINE | Mode | `auto` — bug fix / privacy hole on an existing surface; functional intent is already in `00-request.md` | — |
| 2 | 2026-07-28 | REFINE | Who may quote private stories? | Confirmed non-authors with Story read access, including beta readers | — |
| 3 | 2026-07-28 | REFINE | Who sees private-story quotes on another's Citations tab? | Only viewers who currently have Story access (beta readers / collaborators) | — |
| 4 | 2026-07-28 | REFINE | Community quotes for non-confirmed users? | Never visible | — |
| 5 | 2026-07-28 | REFINE | Notify authors on private-story quotes? | Yes, same notification as public | — |
| 6 | 2026-07-28 | REFINE | Moderator override to see inaccessible private quotes? | No | — |
| 7 | 2026-07-28 | DESIGN | Sequencing with `story-author-check/` | Already done on branch — this task only adds access to `canQuote` + tests | — |
| 8 | 2026-07-28 | DESIGN | Where to enforce story access on create? | Extend `QuotePolicy::canQuote` via `filterUsersWithAccessToStory` | — |
| 9 | 2026-07-28 | DESIGN | Change profile filtering? | Keep `filterVisibleForViewer` as-is | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Root cause of « quotes don't work on private stories » is that only collaborators can read private stories, and `canQuote` blocks all collaborators via misnamed `isAuthorOrCoAuthor` (beta readers included). Fixing create = treat beta readers as readers. | REFINE | Yes — if the user meant a different bug |
| 2 | Profile non-owner filtering via `filterUsersWithAccessToStory` is the intended mechanism for « visible to people who can also view the story »; keep it, pin with tests (incl. beta-reader viewer sees private entry). | REFINE | Yes |
| 3 | Create must also refuse when the actor lacks Story access (defense in depth), not only when they are an author. | REFINE | Yes |
| 4 | No new UI strings; reuse existing quote / Citations surfaces. | REFINE | Yes |
| 5 | Sequencing with `story-author-check/` left to DESIGN (API rename vs absorb `canQuote` behaviour here). | REFINE | Yes — **superseded by decision #7**: already landed |
| 6 | Unavailable-chapter treatment for owners unchanged; non-owners keep omit-not-placeholder for inaccessible stories. | REFINE | Yes |
| 7 | Notification payload stays as today (no note; existing fields OK for private stories). | REFINE | Yes |
| 8 | Single BUILD phase; VERIFY light / test-backed if no UI change | PLAN | Yes |
| 9 | Skip Playwright VERIFY — no UI change; checklist filled from feature tests | VERIFY | Yes |
