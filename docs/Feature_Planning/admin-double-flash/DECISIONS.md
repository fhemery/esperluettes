# Admin pages — flash message shown twice — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | The admin layout is the single owner of the flash block; the 23 page-level blocks are removed (layout kept, not the reverse) | REFINE | yes — trivial |
| 2 | No admin page needs a page-specific flash placement (component takes no props, is `position: fixed`) | REFINE | yes |
| 3 | Regression coverage = one Config render test (message appears once) + one Administration file-scan guard forbidding `x-shared::flash-block` under `*/Private/Resources/views/pages/admin` | DESIGN | yes — drop the guard if unwanted |
