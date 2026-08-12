# Migrate npm to pnpm — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Replace npm with pnpm as the sole Node package manager for this repository.
Developers and CI install and run frontend tooling through pnpm; documentation
and agent instructions match that reality. The change exists to harden the
supply chain (delayed uptake of freshly published packages) and to simplify
day-to-day install/run workflows — not to change any end-user product behaviour.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Lockfile | The committed dependency resolution file (`pnpm-lock.yaml` after migration) |
| ADR | Architecture Decision Record — short durable note of *why* pnpm, and the tradeoffs |
| Corepack | Node's built-in tool to pin and invoke the `packageManager` declared in `package.json` |
| Frozen install | CI/local install that fails if the lockfile would change |

No new user-facing (French app UI) vocabulary — this is developer tooling only.

## 3. Roles & visibility

N/A for application roles. Audience:

| Audience | Can see | Can do |
|----------|---------|--------|
| App users (any role) | No change | No change |
| Local developers | Setup + CONTRIBUTING docs | Install deps and run scripts with pnpm |
| CI | Workflow uses pnpm | Frozen install, build, test |
| Agents / skills | Updated instructions | Run `pnpm` / `pnpm run …` instead of npm |

## 4. Functional requirements

### 4.1 Developer install and scripts

1. A developer following the Docker Sail or Laragon setup docs ends on a working
   frontend toolchain installed with **pnpm**, not npm.
2. Setup docs explain how to obtain pnpm (Corepack / declared `packageManager`
   preferred; Sail image already ships pnpm globally as a fallback).
3. Every documented script invocation that today says `npm install`, `npm ci`,
   `npm run …`, or `npx …` for *this project's* scripts is rewritten to the
   pnpm equivalent (`pnpm install`, frozen install in CI, `pnpm run …` /
   `pnpm exec …`).
4. Existing `package.json` script *names* stay (`gate`, `e2e`, `package`,
   `dev`, …). Invoking them via pnpm must work; any naming collision with a
   built-in pnpm command is documented (see §4.3).

### 4.2 CI and packaging

1. PR CI installs Node dependencies with pnpm against the committed lockfile
   (frozen) and runs the same build/test steps as today.
2. Deploy / packaging docs and the packaging script path use pnpm for the
   asset build step.
3. After migration, `package-lock.json` is gone from the repo; `pnpm-lock.yaml`
   is the only Node lockfile.

### 4.3 Script and hook continuity

1. Husky still installs on dependency install (`prepare`).
2. Pre-commit / commit-msg behaviour is unchanged from the developer's point of
   view (same checks); internal invocations that hardcode `npm` / `npx` are
   updated so they resolve binaries from the pnpm-managed `node_modules`.
3. The script named `package` remains available. Docs prefer
   `pnpm run package` where a bare `pnpm package` could be ambiguous with a
   future pnpm built-in — called out in the ADR / setup note if needed.
4. Composer’s `dev` helper that today shells to `npm run dev` is updated so
   `composer`/`sail` “run the Vite dev server” still works.

### 4.4 Documentation and ADR

1. An ADR records: why pnpm (supply-chain delay + DX), what we give up
   (extra setup step vs “Node includes npm”), and operational consequences
   (lockfile name, Corepack/`packageManager`, CI cache).
2. Active project documentation that instructs humans or agents to use npm for
   *this* repo is updated to pnpm (setup, CONTRIBUTING, AGENTS, Deploying,
   Working_With_Agents, e2e README, loop/skills that hard-require `npm run gate`,
   etc.).
3. Historical `_done/` planning records and unrelated in-flight task notes are
   **not** rewritten for wording alone (see §8).

### 4.5 Supply-chain posture

1. Fresh package versions younger than the configured minimum age are not
   installed until they age in (24h target from the request). If the chosen
   pnpm version does not enable that by default, the project config turns it
   on and the ADR states the setting.
2. Day-to-day audit commands in docs that still say `npm audit` for this repo
   become `pnpm audit` (including any backlog wording we own when wrapping).

## 5. Lifecycle

No application data. Lifecycle is tooling only:

- **Existing clones:** after pull, developers remove the old npm `node_modules`
  (and any leftover `package-lock.json`) and reinstall with pnpm once; setup
  docs say so.
- **CI cache:** npm cache key is replaced by the pnpm cache strategy.
- **Rollback:** restoring npm would mean restoring `package-lock.json` from git
  history and reverting the doc/CI/script changes — acceptable as a git revert;
  not a supported dual-manager mode.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no app role changes |
| Visibility / privacy | N/A |
| Settings | N/A — no user settings |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | Tooling only — see §5 |
| Media | N/A |
| Search | N/A |
| i18n | N/A — docs stay in their existing language (FR/EN mix as today); no app lang keys |
| Mobile | N/A |
| Accessibility | N/A |
| Architecture boundaries | No domain code; touches repo root tooling, docs, CI, scripts, husky, composer `dev` script |
| UI surface | N/A for product UI; setup docs change |
| Performance | Install/CI expected faster or equal; not a product KPI |
| Data & migration | Lockfile swap only; no DB |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Mode | auto — tooling chore; request already answers the functional question |
| 2 | Package manager | pnpm (as requested; alternatives considered and rejected in assumptions) |

(User-confirmed rows only above. Auto-mode judgements are in `DECISIONS.md`
Assumptions and mirrored in §9 as non-blocking notes where useful.)

## 8. Out of scope

- Changing application frontend behaviour, dependencies’ major versions, or
  Quill / audit-exception policy from `fix-audit-vulnerabilities`.
- Migrating PHP/Composer tooling.
- Removing yarn/bun from the Sail Dockerfile (image already installs several
  package managers; cleanup is optional later).
- Rewriting historical `_done/*.md` or other tasks’ planning docs solely to
  replace the word “npm”.
- Adding a recurring `pnpm audit` / `composer audit` gate (separate backlog
  idea `dependency-audit-gate/` if it exists).
- Supporting npm and pnpm side-by-side in docs or CI.

## 9. Open questions

None blocking.

Non-blocking (recorded as assumptions; reverse in DESIGN/BUILD if wrong):

- Prefer Corepack + `packageManager` pin over “install pnpm globally” as the
  documented happy path.
- Enable / document 24h minimum release age even if not the upstream default.
- Keep the `package` script name; document `pnpm run package` if needed.
