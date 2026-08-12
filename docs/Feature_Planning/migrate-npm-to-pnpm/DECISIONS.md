# Migrate npm to pnpm — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-12 | REFINE | Mode | auto — tooling chore; functional question answered by the request | — |
| 2 | 2026-08-12 | REFINE | Package manager | Stay on pnpm as requested (not yarn/bun). Request invited a challenge; supply-chain delay + explicit ask win over “Sail already has yarn/bun”. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Document Corepack + `package.json` `packageManager` as the primary install path; Sail’s global pnpm is a fallback, not the story. | REFINE | Yes — docs-only |
| A2 | If pnpm’s 24h minimum release age is not on by default for the pinned version, enable it in project config and state that in the ADR. | REFINE | Yes |
| A3 | ADR lives under a new `docs/adr/` (no prior ADR home in the repo). | REFINE | Yes — path only |
| A4 | Do not rewrite `_done/` or other in-flight task folders just to rename npm→pnpm. | REFINE | Yes |
| A5 | Keep the npm script name `package`; prefer `pnpm run package` in docs if bare `pnpm package` is ambiguous. | REFINE | Yes |
| A6 | Replace hard-coded `npm`/`npx` in repo scripts, husky, composer `dev`, and CI with pnpm/`pnpm exec` equivalents; script *names* unchanged. | REFINE | Mostly no once CI lands |
| A7 | Leave yarn/bun installs in the Sail Dockerfile alone. | REFINE | Yes |
| A8 | No dual npm+pnpm support; one lockfile (`pnpm-lock.yaml`) only. | REFINE | Painful after merge |
