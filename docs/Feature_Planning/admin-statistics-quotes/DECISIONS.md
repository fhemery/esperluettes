# Admin statistics — quotes section — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-24 | REFINE | How are quote deletions counted in the evolution curve? | Net total: the curve falls on deletions (needs new events) | — |
| 2 | 2026-10-24 | REFINE | Where does the quotes section go? | New « Citations » tab, plus a summary tile | — |
| 3 | 2026-10-24 | REFINE | Quotes of deactivated users? | Excluded while deactivated, restored on reactivation | — |
| 4 | 2026-10-24 | DESIGN | How does the total learn of deactivation / reactivation / user deletion? | Quote emits count-carrying events (`UserQuotesRemoved`, `UserQuotesRestored`) plus `ChapterPassageQuoteDeleted`; Statistics listens only to Quote | — |
| 5 | 2026-10-24 | DESIGN | Where does the curve's history come from? | `recompute` rebuilds from the `quotes` table through a new `QuotePublicApi` method, not from stored events | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Quotes on deleted chapters/stories still count (rows remain, shown as unavailable) | REFINE | Yes |
| 2 | Access rule is whatever the existing statistics page enforces | REFINE | Yes |
