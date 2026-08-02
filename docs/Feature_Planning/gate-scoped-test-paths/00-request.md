# Gate — scoped test paths — request

*Leftover from `quotes-author-view/` BUILD. Captured in the backlog row.*

## What I want

`npm run gate` (scoped mode) must accept a branch that touches several domains.
Today `scripts/gate.js` passes several directories to `artisan test:parallel`,
which forwards to `artisan test` — that accepts a single `path`, so any branch
touching ≥2 domains dies on `Too many arguments, expected arguments "path"`.

Reproducible on a clean tree with any two directories. A one-domain branch is
unaffected. `-- --all` is green, so nothing is hidden by the workaround.

## Why

Every multi-domain BUILD phase is forced onto `-- --all`, which defeats the
scoped gate and slows the inner loop.

## Constraints or ideas I already have

The call to make: fix `scripts/gate.js` (loop, or pass one path — smaller) or
`ParallelTestCommand` (accept several paths — more useful). Prefer the more
useful fix if cheap.

## Explicitly out of scope

Changing which domains the gate selects, vitest scoping, or deptrac behaviour.
