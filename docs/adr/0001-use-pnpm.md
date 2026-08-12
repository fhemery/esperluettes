# ADR 0001 — Use pnpm as the sole Node package manager

- **Status:** Accepted
- **Date:** 2026-08-12
- **Context:** Node frontend toolchain (Vite, Husky, Vitest, CI asset build) previously used npm exclusively

## Context

This repository ships a Laravel application with a Vite frontend, Husky hooks,
and CI that installs Node dependencies before building assets and running
Vitest. That toolchain used to rely on npm (`package-lock.json`, `npm install`,
`npm run …`, `npx …` in scripts).

We want two things npm does not give us out of the box:

1. **Supply-chain hardening** — delay uptake of freshly published packages so a
   compromised release has time to be pulled before we install it.
2. **Developer experience** — faster, disk-efficient installs and a single,
   explicit lockfile contract for local work and CI.

Node still ships npm, so choosing pnpm is an intentional extra step for every
developer and for CI.

## Decision

**Use pnpm as the only Node package manager for this repository.** npm is not
supported in parallel; there is one lockfile and one install path for project
dependencies.

How to put pnpm on a machine (global install vs Corepack vs Sail) is an
**operational detail** — see the setup guides, not this ADR.
`package.json` still declares `"packageManager": "pnpm@…"` so tools and CI can
read the intended version.

### Lockfile and installs

| Artifact | Role |
|----------|------|
| `pnpm-lock.yaml` | Sole committed Node resolution file |
| `package-lock.json` | Not used; do not reintroduce alongside pnpm |

**CI and reproducible installs** use a frozen lockfile:

```bash
pnpm install --frozen-lockfile
```

Any install that would mutate `pnpm-lock.yaml` fails in CI instead of silently
drifting.

### Supply-chain: minimum release age

**Target: 24 hours** before a newly published package version may be installed.

We pin **pnpm 11.x** (see `packageManager`). pnpm 11 defaults
`minimumReleaseAge` to **1440 minutes (24 h)** and requires Node 22+ (CI uses
Node 24).

If a future pnpm major drops that default or its stricter install defaults
block us, either restore an explicit `minimumReleaseAge: 1440` in
`pnpm-workspace.yaml` or document a version floor here.

**Dependency build scripts** are gated by pnpm 11 `strictDepBuilds`. Allowed
packages are listed explicitly in `pnpm-workspace.yaml` → `allowBuilds`
(today: `esbuild`). That file is the allowlist — not an interactive
per-machine prompt.

### Script names

`package.json` script **names stay unchanged** (`gate`, `dev`, `build`, `test`,
`package`, …). Tooling invokes them via `pnpm` / `pnpm exec …`.

The script named **`package`** is kept for deploy/packaging. In documentation,
prefer **`pnpm run package`** over a bare `pnpm package` in case a future pnpm
built-in collides with that name.

Audit commands for this repo use `pnpm audit`, not `npm audit`.

### What we give up vs “Node ships npm”

| npm (default) | pnpm (chosen) |
|---------------|---------------|
| Zero extra bootstrap — npm is bundled with Node | Install pnpm once on the machine (see setup docs) |
| Familiar to every Node tutorial | Team and docs must say “pnpm” for this repo |
| `package-lock.json` ecosystem default | Contributors must not mix npm lockfiles with this repo |
| Simpler mental model for occasional contributors | Stricter install semantics (frozen CI, content-addressable store, build allowlist) |

We accept that cost for reproducible versions, faster installs, stricter
lockfile discipline, and delayed uptake of fresh registry releases.

We explicitly rejected supporting npm and pnpm side-by-side for project
deps (“dual manager” docs or lockfiles).

## Consequences

### Positive

- One lockfile (`pnpm-lock.yaml`) and one install vocabulary for the repo.
- Default 24 h minimum release age on new package versions (pnpm 11).
- Explicit `allowBuilds` allowlist for dependency install scripts.
- Husky, gate, Composer `dev`, and CI use pnpm while script names stay stable.

### Negative / operational

- Every developer needs pnpm on PATH before `pnpm install` (setup docs).
- Existing clones after the migration: remove old `node_modules` / stray
  `package-lock.json`, then reinstall once.
- New dependency build scripts require a committed `allowBuilds` update.

### Rollback

Rollback is a **git revert** of the migration commits (lockfile, scripts, CI,
docs) and restoring `package-lock.json` from history if needed. **Dual
npm + pnpm support is not a supported mode** — it would rot immediately.
