# News — pin to carousel white screen — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| — | — | — | (none — auto mode) | — | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Bug is frontend-only: clicking the Shared toggle focuses an `sr-only` checkbox and the browser scrolls it into view inside Administration’s `overflow-y-auto` main pane, so the form leaves the viewport. No server round-trip on click. | REFINE | Yes — if VERIFY shows another cause |
| 2 | Success = toggle works and form stays visible; pin still saves only on Enregistrer. | REFINE | Yes |
| 3 | No new copy, roles, events, notifications, or schema. | REFINE | Yes |
| 4 | Fix lives in Shared’s `<x-shared::toggle>` (overlay/positioned checkbox), not News-local and not Administration layout. | DESIGN | Yes |
| 5 | Markup/CSS only — same props and form semantics; no Alpine/JS. | DESIGN | Yes |
