# Gate — scoped test paths — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Multi-dir `test:parallel` + regression | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Multi-dir `test:parallel` + regression

**Goal.** `artisan test:parallel` with several directory arguments runs each dir
as its own one-path ParaTest invocation (sequentially) and exits without the
Symfony “Too many arguments… path” abort.

Depends on architecture §3.1 (command contract), §3.3 (zero / one / many path
behaviour; `gate.js` unchanged), §6 (console regression), §7 tradeoff #1–#2
(loop in `ParallelTestCommand`; sequential parallel runs).

**Deliverables.**
- `app/Console/Commands/ParallelTestCommand.php`
  - Keep process resolution as today (`TEST_PROCESSES` or ~80% of cores).
  - **Zero path args:** unchanged — one delegated
    `artisan test --parallel --processes=N` via the existing `$_SERVER['argv']`
    rewrite pattern (architecture §3.3 / §9).
  - **One or more path args:** read dirs from `$this->argument('dirs')`; for
    **each** directory, rebuild argv so ParaTest receives **exactly one**
    `path`, then `$this->call('test', ['--parallel' => true])`. Do not pass
    multiple paths in a single delegated call.
  - **Exit code:** run every listed dir (do not stop early); return `0` only if
    all invocations succeed; otherwise return the **first** non-zero code seen
    (architecture §3.1 left this to PLAN).
  - Do not change `scripts/gate.js`.
- `phpunit.xml` — add `<directory suffix="Test.php">tests/Feature</directory>`
  under the Feature testsuite (Pest already configures `uses(…)->in('Feature')`;
  discovery was missing).
- `tests/Feature/Console/ParallelTestCommandTest.php` — regression suite (see
  Tests).
- Fixture suites used only by that regression (not discovered by the main
  suite):
  - `tests/fixtures/parallel-paths/a/ExampleTest.php` — one trivial passing test
  - `tests/fixtures/parallel-paths/b/ExampleTest.php` — one trivial passing test

**Tests.**
- `ParallelTestCommandTest` → `it accepts multiple directory arguments without Too many arguments`
  - Arrange: set `TEST_PROCESSES=1` for the nested run.
  - Act: `$this->artisan('test:parallel', ['dirs' => ['tests/fixtures/parallel-paths/a', 'tests/fixtures/parallel-paths/b']])`.
  - Assert: exit code `0`; command output / exception text does **not** contain
    `Too many arguments` (or `expected arguments "path"`).
- `ParallelTestCommandTest` → `it still runs a single directory argument`
  - Same fixture `a` alone; assert exit `0`.
- Do **not** add an automated `gate.js` test (functional §8 / architecture §6).
- Manual acceptance smoke (same phase, not a Pest case): on a tree whose scoped
  plan would yield ≥2 dirs, `npm run gate` must not abort on path arity; and
  `npm run gate -- --all` still green when the suite is.

**Acceptance.**
- ✅ `artisan test:parallel <dirA> <dirB>` with the two fixture dirs exits `0`
  and never surfaces ParaTest’s single-`path` arity error.
- ✅ Single-dir and pathless (`-- --all` / bare `test:parallel`) behaviour
  remain usable; pathless covered by the gate smoke above.
- ✅ `scripts/gate.js` is unmodified for the PHP step.
- ✅ `npm run gate` green.

---

## Visual QA checklist

VERIFY skips the browser (assumption A3 / architecture §6) — tooling only.
No end-user surface. Checklist is documentary; mark rows N/A at VERIFY.

| Surface | Check | OK? |
|---------|-------|-----|
| N/A — no UI | Scoped multi-dir PHP step / direct `test:parallel` with ≥2 dirs | n/a — skip browser; multi-dir fixture smoke exit 0 ✅ |
| N/A — no UI | Full gate `-- --all` still green | n/a — skip browser; `npm run gate -- --all` PASS ✅ |

## Open items

None blocking. Confirmed while planning:

- `ParallelTestCommand` still forwards **all** post-command argv into one
  ParaTest call — root cause matches architecture §1 / §3.3.
- phpunit discovers only `app/Domains/*/Tests/{Feature,Unit}`; root
  `tests/Feature` needs the `phpunit.xml` line above (Pest already prepared).
- Nested `artisan test` inside the regression is acceptable if limited to the
  two fixture files and `TEST_PROCESSES=1`.
