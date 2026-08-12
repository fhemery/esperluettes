# Migrate npm to pnpm — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | ADR — record the package-manager decision | S | — | DONE |
| 2 | Core switch — lockfile, runtime scripts, CI | M | 1 | DONE |
| 3 | Active documentation sweep | M | 2 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/3)` resume correctly.

## Working agreement

- One phase = one commit. Each phase ships independently, keeps the gate green,
  and is revertable on its own.
- Phases 1–2 still invoke the gate via **`npm run gate`** (npm remains on the
  machine until phase 2 lands). From the end of phase 2 onward, use **`pnpm run
  gate`** — the lockfile and scripts no longer assume npm.
- This is tooling, not domain code: no new Laravel feature tests. Proof is the
  existing vitest suite, vite build, deptrac, scoped/full gate, and CI parity.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Do **not** rewrite `docs/Feature_Planning/_done/` or other in-flight task
  folders for npm→pnpm wording alone (functional §8, DECISIONS A4).

---

## Phase 1 — ADR — record the package-manager decision

**Goal.** Capture *why* pnpm and the operational consequences before any runtime
behaviour changes.

**Architecture.** §1 (surfaces), §4 (tooling contracts — cite intended
`packageManager` / age posture), §7 tradeoffs #1–#6, §8 (new `docs/adr/` files).

**Deliverables.**
- `docs/adr/README.md` — one-line index of ADRs.
- `docs/adr/0001-use-pnpm.md` — supply-chain delay + DX; Corepack +
  `packageManager` as the contract; Sail global pnpm as fallback only; lockfile
  name (`pnpm-lock.yaml`); frozen CI installs; 24h `minimumReleaseAge` (pnpm
  11 default, or explicit 1440 fallback); the kept script name `package` and
  preference for `pnpm run package` in docs; what we give up vs “Node ships npm”;
  rollback = git revert, not dual-manager mode.

**Tests.**
- N/A — no application or domain behaviour. The gate `docs` step is the check.

**Acceptance.**
- ✅ ADR states why pnpm, consequences, Corepack path, lockfile contract, and
  minimum-release-age posture.
- ✅ ADR notes the `package` script name and `pnpm run package` preference.
- ✅ No lockfile, CI, or script changes in this phase — npm remains the active
  manager on disk.
- ✅ `npm run gate` green.

---

## Phase 2 — Core switch — lockfile, runtime scripts, CI

**Goal.** Make pnpm the sole Node package manager everywhere code runs: local
install, hooks, gate internals, packaging script, Composer `dev`, and PR CI.

**Architecture.** §2 (lockfile contract), §3 (Composer `dev` seam), §4 items
1–5 (packageManager pin, pnpm 11.x, binary invocation, gate asset detection),
§7 tradeoffs #1–#7, §8–§9 (import path, pnpm 11 fallback risks).

**Prior phases.** Phase 1 left the ADR in `docs/adr/`; runtime still uses npm
until this phase completes.

**Deliverables.**
- `package.json` — add `"packageManager": "pnpm@<11.x>"` (pin the latest stable
  11.x patch BUILD verifies; see Open items).
- `pnpm-lock.yaml` — generated with `corepack enable` then
  `pnpm import` from the existing `package-lock.json`; verify Quill stays at
  `2.0.3` and axios at `1.18.1`.
- Remove `package-lock.json`.
- Optional (only if pnpm 11 defaults block install/run — architecture §9):
  `.npmrc` or `pnpm-workspace.yaml` with `minimumReleaseAge: 1440` and/or
  documented opt-outs; note the fallback in the ADR.
- `scripts/gate.js` — extend `ASSET_CONFIG` to match `pnpm-lock.yaml`; replace
  hardcoded `npx` invocations with `pnpm exec`; update header usage comments
  to show `pnpm run gate`.
- `scripts/husky-precommit.js` — `npx vitest run` → `pnpm exec vitest run`.
- `.husky/commit-msg` — `npx commitlint` → `pnpm exec commitlint`.
- `composer.json` — `scripts.dev`: `npx concurrently …` → `pnpm exec
  concurrently …`; `"npm run dev"` → `"pnpm run dev"`.
- `scripts/package.js` — runner helper `npm` → `pnpm` (both php and sail
  branches); `r.npm(['run', 'build'])` → pnpm equivalent.
- `scripts/worktree.js` — usage strings and the post-create hint (`pnpm install`,
  `pnpm run build`).
- `scripts/check-docs.js` — top comment references `pnpm run gate`.
- `.github/workflows/pr-tests.yml` — after `setup-node`: `corepack enable`;
  `cache: 'pnpm'` (or drop cache if Corepack + setup-node cache misbehave —
  document in commit body); install with
  `pnpm install --frozen-lockfile`; build/test steps use `pnpm run build` /
  `pnpm run test`.

**Tests.**
- No new test files. Verification commands (run locally after `pnpm install`):
  - `pnpm install --frozen-lockfile` — succeeds on a clean tree with the
    committed lockfile.
  - `pnpm run test` — existing vitest suite passes.
  - `pnpm run build` — vite production build succeeds.
  - `pnpm run gate -- --all` — full gate green under pnpm.
  - `pnpm exec commitlint --help` — hook binary resolves.
- CI: the updated `pr-tests.yml` steps above must pass on the PR (frozen install,
  build, vitest, deptrac, PHP parallel tests).

**Acceptance.**
- ✅ `package-lock.json` absent; `pnpm-lock.yaml` committed; `packageManager`
  pinned.
- ✅ Quill `2.0.3` and axios `1.18.1` unchanged in the lockfile (audit posture
  preserved).
- ✅ No remaining hardcoded `npm` / `npx` for **this repo's tooling** under
  `scripts/`, `.husky/`, `composer.json` `scripts.dev`, or
  `.github/workflows/pr-tests.yml`.
- ✅ `scripts/gate.js` treats `pnpm-lock.yaml` as an asset-config change.
- ✅ Husky `prepare` still runs on `pnpm install`; pre-commit and commit-msg
  behaviour unchanged from a developer's perspective.
- ✅ `composer run dev` (or Sail equivalent) still starts the Vite leg via pnpm.
- ✅ `pnpm run gate -- --all` green.

---

## Phase 3 — Active documentation sweep

**Goal.** Every **active** human- and agent-facing instruction for *this repo*
matches pnpm; no npm install/run examples remain outside historical archives.

**Architecture.** §1.1 (setup, CONTRIBUTING, AGENTS, Deploying, e2e, loop
skills), §4 item 6 (`pnpm run package`), §6 (VERIFY developer-path smoke),
functional §4.1–§4.5 and §8.

**Prior phases.** Phases 1–2 left pnpm as the sole runtime; lockfile, CI, hooks,
and internal scripts already use pnpm. This phase updates prose only (plus any
straggler comment missed in phase 2).

**Deliverables.**
- Root: `AGENTS.md`, `CONTRIBUTING.md`.
- Setup / ops: `docs/Setup_01a_Docker_Sail.md`, `docs/Setup_01b_Windows_Laragon.md`,
  `docs/Deploying.md`, `docs/Working_With_Agents.md`, `docs/Domain_Structure.md`.
- E2E: `e2e/README.md`.
- Loop + skills (rewrite npm/npx **for project commands**; keep generic upstream
  examples that are not about this repo's gate/install):
  - `.agents/loop/README.md`
  - `.agents/skills/implement-phase/SKILL.md`
  - `.agents/skills/verify-visually/SKILL.md`
  - `.agents/skills/run-app/SKILL.md`
  - `.agents/skills/run-app/setup.mjs`, `driver.mjs`
  - `.agents/skills/continue-task/SKILL.md`
  - `.agents/skills/next-task/SKILL.md`
  - `.agents/skills/commit/SKILL.md`
  - `.agents/skills/plan-phases/SKILL.md`
  - `.agents/skills/design-architecture/SKILL.md`
  - `.agents/skills/refine-feature/SKILL.md`
  - `.agents/skills/wrap-task/SKILL.md`
  - `.agents/skills/fix-deptrac/SKILL.md`
  - `.agents/skills/add-event/SKILL.md`
  - `.agents/skills/add-notification/SKILL.md`
  - `.agents/skills/document-domain/references/content-guide.md`
  - `.claude/skills/*` and `.claude/agents/phase-implementer.md` mirrors of the
    above where they exist
  - `.claude/commands/add-task.md`, `.cursor/commands/add-task.md`
- Setup sections must document the Corepack happy path:
  `corepack enable` → `pnpm install` → `pnpm run build`, with Sail global pnpm
  noted as fallback per ADR.
- Existing-clone note: remove old `node_modules` / stray `package-lock.json`,
  then `pnpm install` once (functional §5).
- Audit doc wording owned here: `pnpm audit` replaces `npm audit` where docs
  teach checking this repo (not in `_done/` archives).
- Update `docs/Feature_Planning/BACKLOG.md` status for this task to
  `WIP:BUILD (3/3)` when starting, then `WIP:VERIFY` when done (or leave for
  orchestrator — do not mark DONE; WRAP owns archive).

**Explicitly out of scope for this sweep**
- `docs/Feature_Planning/_done/**`
- Other in-flight task folders (`annotations/`, `gate-parallel-execution/`, …)
  unless a file is edited for another reason
- Generic MCP / hook-pattern examples that use `npx` for **external** servers
  (e.g. `.agents/skills/mcp-integration/examples/stdio-server.json`) — not this
  project's install/run path

**Tests.**
- `rg -n '\bnpm (install|ci|run)\b|\bnpx\b' AGENTS.md CONTRIBUTING.md docs/Setup_*.md docs/Deploying.md docs/Working_With_Agents.md e2e/README.md .agents/loop/README.md` — no matches for project-tooling invocations (except intentional “before migration” history if any remains in this task folder).
- `pnpm run gate` green (docs step validates links).

**Acceptance.**
- ✅ Setup docs (Docker + Laragon) teach Corepack + pnpm install/build/dev.
- ✅ `AGENTS.md`, `CONTRIBUTING.md`, `Working_With_Agents.md`, Deploying, e2e
  README, and loop/skills gate instructions say `pnpm run gate` / `pnpm install`.
- ✅ Deploying and ADR-aligned docs use `pnpm run package`.
- ✅ No npm-only install path documented for this repo in the files listed above.
- ✅ `_done/` and unrelated in-flight planning folders untouched.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

No product UI. VERIFY runs a **developer-path** smoke (architecture §6) — walk
the docs, do not Playwright the app.

| Surface | Check | OK? |
|---------|-------|-----|
| Docker Sail setup (`docs/Setup_01a_Docker_Sail.md`) | Fresh reader can follow Corepack → `pnpm install` → `pnpm run build` without hitting npm | ✅ §8 documents Corepack path, existing-clone `rm`, Sail fallback; no npm install/run in doc |
| Laragon setup (`docs/Setup_01b_Windows_Laragon.md`) | Same pnpm path; Windows notes still coherent | ✅ §8 matches; Windows SSL/Laragon steps unchanged |
| CONTRIBUTING | Husky install tied to `pnpm install`, not npm | ✅ §Local hooks cites `pnpm install` + Corepack |
| AGENTS.md + loop skills | Gate/e2e/browser commands use pnpm | ✅ `rg` on listed active docs — no project `npm install/run` or `npx`; loop skills say `pnpm run gate` |
| Existing clone (hand check) | Remove `node_modules`, `pnpm install`, `pnpm run gate -- --quick` succeeds | ✅ `rm -rf node_modules && pnpm install --frozen-lockfile` + gate green (docs, deptrac, php, js) |
| `composer run dev` | Vite leg starts (pnpm-backed) alongside PHP processes | ✅ `composer.json` `scripts.dev` shells to `pnpm run dev`; `pnpm run dev` → Vite 7 ready |
| Husky pre-commit | Commit with a trivial change runs deptrac + vitest via pnpm exec | ✅ `husky-precommit.js` uses `pnpm exec vitest run`; deptrac + vitest both pass when invoked |
| Deploying | `pnpm run package` documented; dev-server shutdown note still valid | ✅ §Generating a package uses `pnpm run package`; shutdown note references `pnpm run dev` |
| PR CI (GitHub) | Workflow green with frozen pnpm install on the branch | n/a — `gh`/GitHub API unreachable from verify env; `.github/workflows/pr-tests.yml` uses Corepack + `pnpm install --frozen-lockfile` + build/test (matches local smoke) |

---

## Open items

| Item | Phase | Notes |
|------|-------|-------|
| Exact `pnpm@11.x` patch to pin in `packageManager` | 2 | Pick latest stable 11.x at BUILD time; CI Node 24 satisfies pnpm 11 Node 22+ floor. |
| `pnpm import` fidelity vs current `package-lock.json` | 2 | If gate/vitest fails or Quill/axios drift, fresh resolve (architecture §7#4 B) then re-verify pins. |
| pnpm 11 default strictness (`blockExoticSubdeps`, `verifyDepsBeforeRun`, …) | 2 | First install/run failure that is clearly a v11 default → fall back to ≥10.16 + explicit `minimumReleaseAge: 1440`; document in ADR (architecture §9). |
| `actions/setup-node` cache with Corepack-only bootstrap | 2 | Architecture chose Corepack over `pnpm/action-setup`. If `cache: 'pnpm'` fails before pnpm exists, try `cache: 'npm'` with store path or omit cache — note outcome in phase commit. |
| Sail image global pnpm version vs `packageManager` pin | 2–3 | Fallback only; setup docs must not treat Sail's global version as the contract. |
| MCP / hook reference docs using generic `npx` for external tools | 3 | Leave as-is unless BUILD finds project-gate instructions inside those files. |
