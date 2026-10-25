# Prevent copying chapter text — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Mode | auto (user ran the six tasks sequentially, available for questions) | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Blocking `copy`, `cut` and `dragstart` on the chapter body; `contextmenu` and `user-select` left alone so mobile long-press selection keeps working | REFINE | yes |
| 2 | Only authors/co-authors exempt; moderators and admins are restricted | REFINE | yes |
| 3 | No message shown to the reader | REFINE | yes |
| 5 | Implement with inline Alpine listeners on the article, no JS module | DESIGN | yes |
| 4 | Branch: stay on `fix/misc-small-fixes`, commit there (user instruction) | REFINE | yes |
