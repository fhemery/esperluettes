# Gate — scoped test paths

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.
>
> Target: under ~120 lines. If it grows past that, cut prose, not facts.

**Status:** DONE — 2026-08-02 · **Domain(s):** `dev` (console / gate tooling) ·
**Spec:** [request](./00-request.md) · [functional](./01-functional.md) ·
[architecture](./02-architecture.md) · [plan](./03-plan.md) ·
[decisions](./DECISIONS.md)

## What it does

`scripts/gate.js` already passes every impacted domain’s `Tests` directory to
`artisan test:parallel`. ParaTest accepts only one optional `path`, so any
multi-domain scoped gate died with Symfony’s `Too many arguments, expected
arguments "path"`. `ParallelTestCommand` now loops: one parallel invocation per
directory argument, pathless full-suite behaviour unchanged. `gate.js` was not
modified.

## Key behaviour

- **Zero dirs** → one `artisan test --parallel --processes=N` (CI / `npm run gate
  -- --all`). Still forwards leftover argv the old way.
- **One or more dirs** → for each dir, rebuild `$_SERVER['argv']` with exactly
  that one path and call `test --parallel`. All dirs run even after a failure;
  exit code is the **first** non-zero (else `0`).
- **With dirs, extra argv is not forwarded** — only `test`, `--parallel`,
  `--processes`, and the single path. Gate never needed flags there; do not
  invent multi-dir flag syntax without revisiting this.
- Root `tests/Feature` is now in `phpunit.xml`’s Feature suite so the console
  regression is discovered. Fixtures under `tests/fixtures/` are **not** in that
  suite.
- VERIFY skipped the browser (tooling only). Multi-dir fixture smoke and
  `npm run gate -- --all` were green.

## Where the code lives

| Concern | Path |
|---------|------|
| Command | `app/Console/Commands/ParallelTestCommand.php` |
| Gate caller (unchanged) | `scripts/gate.js` — still spreads `phpPlan.dirs` into one `test:parallel` argv |
| PHPUnit discovery | `phpunit.xml` — Feature suite includes `tests/Feature` |
| Regression | `tests/Feature/Console/ParallelTestCommandTest.php` |
| Fixtures (not discovered) | `tests/fixtures/parallel-paths/{a,b}/ExampleTest.php` |

Commit: `f26f1876`. No domain, schema, routes, JS bundle, notifications, or deptrac
change.

## Extension points used

None.

## Decisions worth remembering

- **Fix the command, not the gate** (#2). Signature already claimed `{dirs?*}`;
  looping in `gate.js` would leave every other caller broken.
- **Sequential parallel runs** (#3), not a merged PHPUnit config and not a
  non-parallel fallback. Accepts more wall-clock for large N; revisit if scoped
  multi-domain gates routinely beat `-- --all`.
- **Exit code = first failure** (A5), after still running every listed dir.
- Nested regression uses tiny fixtures + `TEST_PROCESSES=1` (A7), not full
  domain suites.
- Husky’s staged multi-dir path stays non-parallel `artisan test` (A4 / §8) —
  out of scope.

## Plan vs. code

Phase 1 shipped as planned. No drift: `gate.js` untouched, loop in
`ParallelTestCommand`, fixture-backed Pest cases, `phpunit.xml` Feature line
added. Architecture §3.3’s “flags keep working for the one-dir case” is weaker
in code — one-dir now uses the same rebuilt argv as multi-dir (no leftover
forwarding); only the pathless branch still spreads extra argv.

## Not done

- Deliberate non-goals (§8): domain selection / `maxDirs`, vitest/deptrac/docs
  scoping, husky→`test:parallel`, ParaTest upstream, automated `gate.js` suite.
- Nothing cut mid-build.
- No open questions; no new backlog rows.
- No feature e2e specs to retire (none added).
