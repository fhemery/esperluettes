# Gate — scoped test paths — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-02 | REFINE | Mode | `auto` — bugfix; request already answers the functional question | — |
| 2 | 2026-08-02 | DESIGN | Fix site | `ParallelTestCommand` loops one ParaTest `path` per dir; `gate.js` unchanged | — |
| 3 | 2026-08-02 | DESIGN | Multi-dir scheduling | Sequential parallel invocations, not merged PHPUnit config / non-parallel fallback | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | *(superseded by decision #2)* Prefer fixing `ParallelTestCommand` over a gate.js-only loop | REFINE | — |
| A2 | All selected dirs must still be covered in one gate run (union of tests), not “first dir only” | REFINE | No (would violate the request) |
| A3 | No new end-user behaviour; VERIFY can skip browser QA | REFINE | Yes |
| A4 | Husky staged path stays as non-parallel multi-dir `artisan test` — out of scope to change | REFINE | Yes |
| A5 | Multi-dir exit code: run every listed dir, return the first non-zero (else 0) — architecture left the choice to PLAN | PLAN | Yes |
| A6 | Regression lives in `tests/Feature/Console/` + add that directory to `phpunit.xml` Feature suite (Pest already had `uses()->in('Feature')`) | PLAN | Yes |
| A7 | Nested parallel smoke uses two tiny fixture suites under `tests/fixtures/parallel-paths/{a,b}/` with `TEST_PROCESSES=1`, not full domain dirs | PLAN | Yes |
| A8 | Phase-1 commit used `SKIP_FORBIDDEN=1` so husky allows new files under `tests/` — placement matches A6/A7; not a `--no-verify` of other hooks | BUILD | Yes |
