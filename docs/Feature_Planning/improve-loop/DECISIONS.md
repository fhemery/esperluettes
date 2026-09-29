# Improve loop — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-29 | REFINE | Agent memory policy (`memory: project` on all 5 agents) | Drop agent memory; fold still-true notes into skills/docs | — |
| 2 | 2026-09-29 | REFINE | Where RETRO sits | First section of WRAP (task-wrapper), from artifacts; loop stays six steps | — |
| 3 | 2026-09-29 | REFINE | Where retro findings land | One collector backlog entry `loop-improvements/00-request.md`, created if missing | — |
| 4 | 2026-09-29 | REFINE | When PLAN inserts a checkpoint row | Required after any phase reshaping code with existing consumers; optional elsewhere | — |
| 5 | 2026-09-29 | REFINE | Stale compiled assets | Gate builds on `*.blade.php` changes AND VERIFY/checkpoints build first | — |
| 6 | 2026-09-29 | REFINE | pnpm 12 forwards a literal `--` | Drop `--` in docs only; scripts unchanged | — |
| 7 | 2026-09-29 | REFINE | Run DESIGN / PLAN / VERIFY for a docs-and-one-script task? | No — skip them; edit directly in the orchestrator thread, commits by theme, light WRAP | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| a | A checkpoint counts in the BUILD counter `(n/m)` (replayed, not vetoed) | REFINE | yes |
| b | A FAIL checkpoint auto-dispatches the fix of the preceding phase, without stopping, even in interactive mode; two same-reason FAILs = stop (replayed, not vetoed) | REFINE | yes |
| c | Existing local agent-memory notes are reviewed; still-true ones folded, the rest dropped (replayed, not vetoed) | REFINE | yes |
| d | Stale `npm run gate` in the 03-plan template fixed along the way (replayed, not vetoed) | REFINE | yes |
| e | Suggestion-5 know-how goes into `e2e/README.md`, `verify-visually`, `run-app` (replayed, not vetoed) | REFINE | yes |
| f | continue-task's "shots/ empty ⇒ VERIFY pending" check adjusted for checkpoint shots (replayed, not vetoed) | REFINE | yes |
