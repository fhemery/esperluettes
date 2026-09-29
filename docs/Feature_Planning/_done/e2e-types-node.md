# Make the e2e specs type-check (@types/node)

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-09-29 · **Domain(s):** dev tooling (`e2e/`, `scripts/gate.js`) — no `app/Domains` change

## What it does

`pnpm exec tsc -p e2e` now passes (it failed with `TS2688: Cannot find type
definition file for 'node'`: `e2e/tsconfig.json` lists `"node"` in `types`, but
`@types/node` was only transitive and pnpm does not hoist it). `@types/node` is
now a direct dev dependency on major 24 (CI's Node), one spec type error was
fixed, and `pnpm run gate` runs the type-check as a new scoped step `e2e-types`.

## Key behaviour

- Gate step `e2e-types` = `pnpm exec tsc -p e2e` (`noEmit` comes from `e2e/tsconfig.json`).
- Runs only if the branch changed `e2e/`, `playwright.config.{js,ts,mjs,cjs,mts,cts}`,
  `package.json` or `pnpm-lock.yaml` — or with `--all`. Otherwise `SKIP` with a reason.
- **Not** skipped by `--quick` (only `build` is). `--only=e2e-types` is valid.
- `e2e/tsconfig.json` untouched: `strict` + `noUncheckedIndexedAccess` stay on.
  Fixes go in the specs, never by loosening the config or `!`/`any`/`@ts-ignore`.
- Lockfile side effect (A8): the optional `@types/node` peer of vite, vitest and
  commitlint re-resolved from 26.6.2 to 24.19.0. 26.6.2 remains in the lockfile
  only for `@types/ws`, `buffer-image-size` and `happy-dom`.

## Where the code lives

| Concern | Path |
|---------|------|
| Dependency | `package.json` (`"@types/node": "^24.19.0"`), `pnpm-lock.yaml` |
| Gate step + trigger | `scripts/gate.js` — `E2E_TYPES_FILE`, `touchesE2eTypes()`, `e2e-types` entry after `js` |
| Spec type fix | `e2e/pages/AdminStatisticsPage.ts` `hoverSlot()` — `xs[index] ?? evenSplit` |
| Docs listing gate steps | `AGENTS.md` (Definition of done), `docs/Working_With_Agents.md`, `.agents/loop/README.md`, `scripts/gate.js` header |
| e2e author note | `e2e/README.md` (one line under the commands) |
| Tests | none — the compiler is the test; `scripts/` has no unit harness |

## Extension points used

None — tooling only.

## Decisions worth remembering

All made without asking (`auto` mode); each is reversible.

| # | Assumption | Made at |
|---|------------|---------|
| A1 | `@types/node` pinned to CI's Node major (24) | REFINE |
| A2 | "Consider adding the type-check to the gate" taken as **yes**: new gate step | REFINE |
| A3 | Step scoped like the others: `e2e/`, Playwright config, `package.json`, lockfile; always with `--all` | REFINE |
| A4 | Type errors surfaced by the fix are fixed minimally in the specs | REFINE |
| A5 | Fix via a direct dev dep — not a pnpm hoist pattern, not dropping `"node"` from `types` | DESIGN |
| A6 | Step id `e2e-types`; **not** skipped by `--quick`; also triggered by `package.json`/`pnpm-lock.yaml` | DESIGN |
| A7 | Never loosen `e2e/tsconfig.json`; many errors or behavioural rewrites → stop for the user (did not happen: one error) | DESIGN |
| A8 | `pnpm add -D @types/node@^24` re-resolved the optional `@types/node` peer of vite/vitest/commitlint to 24.19.0; accepted (gate green, aligns tooling with CI). Revert = revert the lockfile | BUILD 1 |
| A9 | `hoverSlot` fix changes runtime only for an out-of-range `index` (was `NaN`), so the statistics e2e spec was not re-run | BUILD 1 |
| A10 | `e2e/README.md` got the type-check line. Skip path verified in a throwaway worktree at `main` + the new `gate.js`, and a `node -e` check of the regex | BUILD 2 |

Verified at VERIFY: full gate `PASSED: docs, e2e-types, deptrac, js, php, build`;
a planted type error → `FAILED: e2e-types`, exit 1; `--quick` still runs the step.

Plan vs code: none. Architecture §4 said "the two docs" listing gate steps but
named three; all three were updated, plus `e2e/README.md` (not in the plan's §8).

## Not done

- Non-goals: Playwright (`pnpm run e2e`) in the gate; type-checking TS/JS outside
  `e2e/` and `playwright.config.ts`; changing the Node version of CI or Sail;
  rewriting specs beyond what `tsc` required.
- Nothing cut mid-build. No open questions.
- `e2e/pages/AdminStatisticsPage.ts` (the page object fixed here) was used by no
  spec when found at VERIFY; it is now exercised by
  `e2e/tests/core/admin-statistics.spec.ts`, added after WRAP.
- e2e specs: this task added none to `e2e/tests/features/`, nothing to retire.
