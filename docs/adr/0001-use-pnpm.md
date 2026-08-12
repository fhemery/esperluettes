# ADR 0001 — Use pnpm as the sole Node package manager

- **Status:** Accepted
- **Date:** 2026-08-12
- **Context:** [migrate-npm-to-pnpm functional spec](../Feature_Planning/migrate-npm-to-pnpm/01-functional.md)

## Context

This repository ships a Laravel application with a Vite frontend, Husky hooks,
and CI that installs Node dependencies before building assets and running
Vitest. Today that toolchain uses npm (`package-lock.json`, `npm install`,
`npm run …`, `npx …` in scripts).

We want two things npm does not give us out of the box:

1. **Supply-chain hardening** — delay uptake of freshly published packages so a
   compromised release has time to be pulled before we install it.
2. **Developer experience** — faster, disk-efficient installs and a single,
   explicit lockfile contract for local work and CI.

Node still ships npm, so choosing pnpm is an intentional extra step for every
developer and for CI bootstrap.

## Decision

**Use pnpm as the only Node package manager for this repository.** npm is not
supported in parallel; there is one lockfile and one install path.

### How developers and CI get pnpm

**Primary path: Corepack + `package.json` `packageManager`.**

After phase 2 of the migration lands, `package.json` will declare an exact pin
such as `"packageManager": "pnpm@11.x"`. Developers run `corepack enable` once;
Corepack (built into Node) downloads and invokes that exact pnpm version on the
host. CI enables Corepack after `actions/setup-node` and uses the same pin — no
separate `pnpm/action-setup` action.

**Fallback only: Sail’s global pnpm.** The Laravel Sail Docker image already
installs pnpm globally. If Corepack is disabled or unavailable in a corporate
image, a developer inside Sail can still run pnpm, but setup docs and CI treat
Corepack + `packageManager` as the contract. The Sail image version is not the
source of truth.

We explicitly rejected:

- documenting global `npm install -g pnpm` as the happy path;
- relying on Sail’s global pnpm alone;
- supporting npm and pnpm side-by-side (“dual manager” docs or lockfiles).

### Lockfile and installs

| Artifact | Role |
|----------|------|
| `pnpm-lock.yaml` | Sole committed Node resolution file (replaces `package-lock.json`) |
| `package-lock.json` | Removed when the migration completes; not kept alongside pnpm |

**CI and reproducible installs** use a frozen lockfile:

```bash
pnpm install --frozen-lockfile
```

Any install that would mutate `pnpm-lock.yaml` fails in CI instead of silently
drifting.

Lockfile migration (phase 2) prefers `pnpm import` from the existing
`package-lock.json` to preserve current resolutions (including pinned Quill
2.0.3 and axios 1.18.1). A fresh resolve is acceptable only if import fails
verification.

### Supply-chain: minimum release age

**Target: 24 hours** before a newly published package version may be installed.

**Preferred:** pin **pnpm 11.x** in `packageManager`. pnpm 11 defaults
`minimumReleaseAge` to **1440 minutes (24 h)** with no project opt-in, and
requires Node 22+ (CI already uses Node 24).

**Fallback:** if pnpm 11 defaults or strictness (`blockExoticSubdeps`,
`verifyDepsBeforeRun`, etc.) block install or scripts, pin pnpm ≥ 10.16 and set
`minimumReleaseAge: 1440` explicitly in `.npmrc` or `pnpm-workspace.yaml`. Note
the fallback in this ADR when it happens.

Audit commands for this repo should use `pnpm audit`, not `npm audit`, once docs
are updated (phase 3).

### Script names and documentation

`package.json` script **names stay unchanged** (`gate`, `dev`, `build`, `test`,
`package`, …). Internal scripts and Husky hooks will invoke tools via
`pnpm` / `pnpm exec …` instead of `npm` / `npx`.

The script named **`package`** is kept for deploy/packaging. In documentation,
prefer **`pnpm run package`** over a bare `pnpm package` in case a future pnpm
built-in collides with that name.

Day-to-day invocations after migration:

```bash
pnpm install
pnpm run build
pnpm run gate
pnpm run package   # deploy asset build — prefer this form in docs
pnpm exec vitest run
```

### What we give up vs “Node ships npm”

| npm (default) | pnpm (chosen) |
|---------------|---------------|
| Zero extra bootstrap — npm is bundled with Node | One-time `corepack enable` (or Sail fallback) before first install |
| Familiar to every Node tutorial | Team and docs must say “pnpm” for this repo |
| `package-lock.json` ecosystem default | Contributors must delete old `node_modules` / stray `package-lock.json` once when switching |
| Simpler mental model for occasional contributors | Slightly stricter install semantics (frozen CI, content-addressable store) |

We accept that cost for reproducible versions, faster installs, stricter
lockfile discipline, and delayed uptake of fresh registry releases.

## Consequences

### Positive

- Same pnpm version on developer machines and CI via Corepack + `packageManager`.
- Faster, deduplicated installs and a single lockfile artifact.
- Default (or configured) 24 h minimum release age on new package versions.
- Husky, gate, Composer `dev`, and CI stop hardcoding npm while script names
  stay stable.

### Negative / operational

- New clones and existing clones after pull: remove old `node_modules` and any
  leftover `package-lock.json`, then `pnpm install` once (documented in setup).
- CI cache strategy switches from npm to pnpm (Corepack + `cache: 'pnpm'` or
  documented alternative if setup-node cache misbehaves).
- Agents and active docs must be updated to `pnpm run gate` / `pnpm install`
  (phase 3); until then, phase 1–2 still run the gate via **`npm run gate`**
  because npm remains on disk until the core switch lands.
- Yarn and bun remain in the Sail Dockerfile; that is out of scope for this ADR.

### Rollback

Rollback is a **git revert** of the migration commits (lockfile, scripts, CI,
docs) and restoring `package-lock.json` from history if needed. **Dual
npm + pnpm support is not a supported mode** — it would rot immediately.

## References

- Architecture: [`02-architecture.md`](../Feature_Planning/migrate-npm-to-pnpm/02-architecture.md) §4 (tooling contracts), §7 (tradeoffs), §8 (file layout)
- Decisions log: [`DECISIONS.md`](../Feature_Planning/migrate-npm-to-pnpm/DECISIONS.md)
