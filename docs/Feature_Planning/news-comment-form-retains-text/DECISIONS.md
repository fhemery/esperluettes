# News comment form retains text after submit — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-08 | REFINE | Mode | `auto` — bug fix; no interview | — |
| 2 | 2026-08-08 | REFINE | Post-submit form state on news | Form stays available; body must be empty | — |
| 3 | 2026-08-08 | REFINE | Scope | Fix shared Comment compose/draft clear; not News policy | — |
| 4 | 2026-08-08 | DESIGN | Where to fix | Comment draft consume-before-restore; not News policy, not drop submit flush | — |
| 5 | 2026-08-08 | DESIGN | Clear vs restore ordering | Dependency-free consumed marker; draft init clears + skips restore before wiring | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Root cause is Comment draft restore racing ahead of `draft_consumed` clear (not Laravel `old()`). | REFINE | Confirmed at DESIGN |
| 2 | Reply composers get the same clear-after-success rule as roots. | REFINE | Yes |
| 3 | Unfinished mid-compose drafts keep restoring; only successful submit clears. | REFINE | Yes |
| 4 | Chapter one-root hide stays as-is; no News one-root cap. | REFINE | Locked DESIGN #1 |
| 5 | Validation failure still preserves input. | REFINE | No (existing contract) |
| 6 | No PHP/API/schema change; vitest is the primary proof. | DESIGN | Yes |
