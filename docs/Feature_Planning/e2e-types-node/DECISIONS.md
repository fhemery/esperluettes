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
| A5 | Fix via a direct `@types/node` dev dep (not a pnpm hoist pattern, not dropping `"node"` from `types`) — T1 in `02-architecture.md` | DESIGN | Yes |
| A6 | Gate step id `e2e-types`, not skipped by `--quick`, triggers also on `package.json`/`pnpm-lock.yaml` — T3/T4 | DESIGN | Yes |
| A7 | Spec fixes never loosen `e2e/tsconfig.json`; if errors are numerous or need behavioural rewrites, BUILD stops for the user | DESIGN | Yes |
| A8 | `pnpm add -D @types/node@^24` also re-resolved the optional `@types/node` peer of vite/vitest/commitlint to 24.19.0 (the 26.6.2 entry stays in the lockfile only where still pulled otherwise). Accepted as-is: gate green, and it aligns the tooling's Node types with CI | BUILD 1 | Yes — revert lockfile |
| A9 | `hoverSlot` fix uses `xs[index] ?? evenSplit`; only an out-of-range `index` (previously `NaN`) changes at runtime, so the statistics e2e spec was not re-run | BUILD 1 | Yes |
| A4 | Genuine type errors revealed once Node types resolve are fixed minimally in the specs (the request's out-of-scope line allows "what the type-check requires") | REFINE | Yes |
