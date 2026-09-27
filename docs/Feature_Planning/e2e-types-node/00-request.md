# Make the e2e specs type-check (@types/node) — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

`pnpm exec tsc -p e2e --noEmit` should pass. Today it fails with
`TS2688: Cannot find type definition file for 'node'`.

## Why

`e2e/tsconfig.json` lists `"node"` in `types`, but `@types/node` is not a
direct dependency: it only arrives transitively, and pnpm does not hoist it to
`node_modules/@types`. Nothing runs `tsc` today (Playwright transpiles the
specs itself), so type errors in the specs go unnoticed. Found during the
2026-09-27 dependency upgrade; it predates it (same with TypeScript 5.9 and 7).

## Constraints or ideas I already have

- Likely fix: add `@types/node` as a dev dependency, matching the Node major
  CI uses (24).
- Once it passes, consider adding the type-check to the gate.

## Explicitly out of scope

Rewriting the e2e specs beyond what the type-check requires.
