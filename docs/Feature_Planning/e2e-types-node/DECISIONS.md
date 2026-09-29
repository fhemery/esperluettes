# Make the e2e specs type-check (@types/node) — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-29 | REFINE | Mode | `auto` — single dev-dependency chore, the request answers the functional question | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | `@types/node` pinned to the major CI uses (24), per the request's own suggestion | REFINE | Yes — one line in `package.json` |
| A2 | The request's "consider adding the type-check to the gate" is taken as **yes**: a new gate step | REFINE | Yes — drop the step |
| A3 | That gate step is scoped like the others: runs only when `e2e/`, `playwright.config.ts`, `package.json` or the lockfile changed on the branch, always with `--all` | REFINE | Yes |
| A4 | Genuine type errors revealed once Node types resolve are fixed minimally in the specs (the request's out-of-scope line allows "what the type-check requires") | REFINE | Yes |
