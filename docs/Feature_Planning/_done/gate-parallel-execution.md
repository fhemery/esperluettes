# Gate parallel execution

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.

**Status:** DONE — 2026-08-12 · **Domain(s):** dev tooling (`scripts/`,
`app/Console/Commands`) · **Spec:** archived, see git history at
`docs/Feature_Planning/gate-parallel-execution/` before this task wrapped.

## What it does

`pnpm run gate`'s selected steps (`docs`, `deptrac`, `php`, `js`, `build`) now
run concurrently instead of one after another. `docs`/`deptrac`/`php`/`js`
all start at once; `build` waits specifically for `php` to settle (see below)
but still overlaps the others. Wall-clock for `--all` dropped from ~59s
(sequential baseline) to ~54–56s, close to the slowest single step (`php`,
~50s) rather than the sum of all five. `--only=<step>` and pass/fail
semantics (per-step PASS/FAIL/SKIP, exit code, "run everything, report all
failures at the end") are unchanged.

A second track of this task — making the PHP step itself run multiple
domains' ParaTest pools concurrently — was built, verified, and then
**reverted**; see "Not done" below. `ParallelTestCommand.php` is therefore
byte-identical to `main` in the final diff.

## Key behaviour

- `scripts/gate.js`'s `main()` starts every non-skipped step's `runCmdAsync`
  call immediately, except `build`, which is chained onto `php`'s promise
  (`build` starts right after `php` settles, or immediately if `php` isn't
  selected/skipped). All pending promises are then awaited together.
- Each step prints its buffered stdout+stderr as **one labelled block as soon
  as that step finishes** — order is completion order, not declaration order.
  No line-by-line interleaving between steps.
- **`build` must not run concurrently with `php`.** `vite build` runs with
  `emptyOutDir: true` and rewrites `public/build/manifest.json` only near the
  end of its run; `php`'s Feature tests render Blade `@vite` directives while
  running. Running both at once transiently throws
  `ViteManifestNotFoundException` (reproduced in 2 of 3 clean `--all` runs
  before the fix). This is the one exception to "steps are independent" —
  don't relax it without re-verifying the manifest race is actually gone
  (e.g. an atomic vite write) first.
- `scripts/utils.js` gained `runCmdAsync(cmd, args, opts)` — `spawn`-based,
  returns `Promise<{ ok, output }>`, same output shape as
  `runCmdWithOutput`. `runCmd`/`runCmdWithOutput` are untouched; Husky hooks
  and `launch_staged_tests.js` still use the synchronous versions.
- No automated test suite for `gate.js` (pre-existing gap, not created by
  this task — verified manually instead, matching the prior
  `gate-scoped-test-paths` precedent).

## Where the code lives

| Concern | Path |
|---------|------|
| Step scheduling | `scripts/gate.js` — `main()` |
| Async command runner | `scripts/utils.js` — `runCmdAsync` |
| PHP multi-dir command (unchanged) | `app/Console/Commands/ParallelTestCommand.php` |

## Extension points used

None.

## Decisions worth remembering

- **`build` sequenced after `php` only**, not after every step and not via an
  atomic-build fix — smallest change that makes the manifest race
  structurally impossible while keeping almost all of the wall-clock win
  (`php` is normally the long pole anyway).
- **Buffered-on-completion output, not live line-prefixed streaming** —
  nobody watches gate output step-by-step today; a docker-compose-style
  multiplexer would have been meaningfully more code for no requested UX.
- **`runCmdAsync` is additive**, not a replacement for `runCmd`/
  `runCmdWithOutput` — other callers (Husky, `launch_staged_tests.js`) were
  deliberately left untouched to keep this a scoped, low-risk change.

## Not done

- **Multi-domain `ParallelTestCommand` concurrency (Phase 1) — built, then
  reverted.** The original plan also had `test:parallel` run one ParaTest
  pool per impacted domain concurrently (via `Symfony\Process`) instead of
  sequentially, to cut repeated worker-pool bootstrap cost. It shipped,
  passed its own tests, and passed an initial VERIFY pass — but that pass
  only ever exercised trivial no-DB fixtures. A manual benchmark against
  real domain Feature tests then found: (1) the spawned process inherited
  the parent's real `.env` instead of the test environment — fixed by
  forcing `phpunit.xml`'s `<env>` block onto the child; (2) a second,
  unresolved issue — `ConfigParameterService`'s DB-backed parameter cache
  (`app/Domains/Config/Public/Services/ConfigParameterService.php`) resolved
  differently through a freshly-spawned process vs. the original in-process
  `$this->call()`, reproducible with a single domain and no concurrency at
  all. This class of flakiness had never appeared in "hundreds of test
  runs" under the original design. Reverted to `main`'s sequential
  `$this->call()` loop rather than keep chasing it, since Phase 2 alone
  already delivered a real, independently-verified win.
  **Possible future investigation, not filed as a backlog item:** if
  someone wants multi-domain PHP-step parallelism again, the blocker is
  root-causing why `ConfigParameterService`'s cache disagrees across a
  freshly-spawned process — start there, not with the process-spawning
  mechanics (those worked).
- No feature e2e specs added or retired — this is dev tooling with no
  browser-reachable behaviour (unchanged from `gate-scoped-test-paths`).
- No domain docs updated — no `app/Domains/*` file changed, and neither
  `scripts/` nor `app/Console` carry a README/AGENTS.md of their own to
  update.
