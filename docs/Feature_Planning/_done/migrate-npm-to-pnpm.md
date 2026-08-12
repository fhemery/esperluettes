# Migrate npm to pnpm

> WRAP output — the compact record of the finished feature. **Load this (in
> `_done/`) by default** when touching Node tooling. Detail lives in
> [`docs/adr/0001-use-pnpm.md`](../../adr/0001-use-pnpm.md).

**Status:** DONE — 2026-08-12 · **Domain(s):** `dev` (repo tooling only)

## What it does

pnpm is the **sole** Node package manager for this repo. `package-lock.json` is
gone; `pnpm-lock.yaml` is the only lockfile. Developers and CI install via
Corepack + the `packageManager` pin; Husky, gate, Composer `dev`, packaging,
and active docs/skills invoke `pnpm` / `pnpm exec` instead of `npm` / `npx`.
No application behaviour, domain code, or Quill/axios pins changed.

## Key behaviour

- **Install path:** `corepack enable` once → `pnpm install` → `pnpm run build`.
  Sail's global pnpm is a **fallback only** — not the documented contract.
- **CI:** `corepack enable` after `setup-node`, `cache: 'pnpm'`,
  `pnpm install --frozen-lockfile`, then build/test/gate steps.
- **Supply chain:** pnpm **11.21.0** pinned in `packageManager`; 24 h minimum
  release age is pnpm 11's default (no explicit `.npmrc` needed).
- **Script names unchanged** (`gate`, `dev`, `package`, …). Docs prefer
  `pnpm run package` over bare `pnpm package`.
- **Existing clones:** remove `node_modules` and any stray `package-lock.json`,
  then `pnpm install` once after pulling the migration.
- **Rollback:** git revert — no dual npm+pnpm mode.

## Where the code lives

| Concern | Path |
|---------|------|
| Manager pin | `package.json` → `"packageManager": "pnpm@11.21.0"` |
| Lockfile | `pnpm-lock.yaml` (imported from old `package-lock.json`) |
| esbuild postinstall | `pnpm-workspace.yaml` → `allowBuilds: esbuild: true` |
| Gate / asset detection | `scripts/gate.js` — `pnpm-lock.yaml` in `ASSET_CONFIG`; vitest/vite via `pnpm exec` |
| Husky | `.husky/commit-msg`, `scripts/husky-precommit.js` |
| Composer dev | `composer.json` → `scripts.dev` shells to `pnpm run dev` |
| Packaging | `scripts/package.js` |
| Worktree helper | `scripts/worktree.js` |
| CI | `.github/workflows/pr-tests.yml` |
| Durable rationale | `docs/adr/0001-use-pnpm.md`, `docs/adr/README.md` |
| Human/agent instructions | `AGENTS.md`, `CONTRIBUTING.md`, setup/deploy/e2e docs, loop skills |

Commits on branch (newest first): `2c9fe4b3` (VERIFY), `eae471f2` (docs sweep),
`9fb82d96` (core switch), `f5a7eb85` (ADR), plus planning commits.

## Extension points used

None — repo-root tooling only.

## Decisions worth remembering

- **pnpm over npm/yarn/bun** — supply-chain delay + DX; Node's bundled npm is
  not supported in parallel (#2).
- **Corepack + `packageManager`** as contract; Sail global pnpm is fallback (#3,
  #9).
- **pnpm 11.21.0** — default 24 h `minimumReleaseAge`; stayed on 11.x when
  `strictDepBuilds` blocked esbuild; fixed with `allowBuilds` in
  `pnpm-workspace.yaml`, not a downgrade to 10.16 (#4, #8, #9).
- **Lockfile:** `pnpm import` from `package-lock.json`; Quill **2.0.3** and
  axios **1.18.1** unchanged (#6, #12).
- **CI bootstrap:** Corepack after `setup-node`, not `pnpm/action-setup` (#7,
  #13).
- **Kept script name `package`**; docs say `pnpm run package` (#5, A5).
- **No `_done/` or in-flight planning rewrites** for npm→pnpm wording alone (#A4).

## Plan vs. code

All three phases shipped. One drift worth noting: architecture §9 contemplated
downgrading to pnpm ≥10.16 + explicit `minimumReleaseAge: 1440` if v11
strictness blocked installs. **What shipped:** stayed on 11.21.0; added
`pnpm-workspace.yaml` `allowBuilds: esbuild: true` for `strictDepBuilds`
(DECISIONS #9). No `.npmrc` minimumReleaseAge file — pnpm 11 default suffices.
PR CI was not exercised live during VERIFY (API unreachable); workflow matches
local frozen install + build + vitest.

## Assumptions you may want to reverse

From the archived decisions log (auto mode):

| # | Assumption | Reversible? |
|---|------------|-------------|
| A1/A9 | Corepack + `packageManager` is primary; Sail global pnpm is fallback story only | Yes — docs |
| A2/A10 | Rely on pnpm 11 default 24 h age (no explicit 1440 in project config) | Medium |
| A7 | Leave yarn/bun in Sail Dockerfile | Yes |
| A8 | No dual npm+pnpm — one lockfile only | Painful after merge |
| A4 | Did not rewrite `_done/` or unrelated in-flight task folders | Yes |

## Not done

- **Deliberate non-goals:** app/dependency major bumps; Composer migration;
  Sail Dockerfile yarn/bun removal; `_done/*.md` npm→pnpm wording sweep;
  recurring audit gate (`dependency-audit-gate/` — see
  [`fix-audit-vulnerabilities`](./fix-audit-vulnerabilities.md)); dual-manager
  support.
- **Left in place on purpose:** in-flight planning folders (`annotations/`,
  `gate-parallel-execution/`, …) may still say `npm run gate` until those tasks
  wrap.
- **E2E:** no feature specs added; nothing to promote or delete.
- **No new backlog rows** from this task.
