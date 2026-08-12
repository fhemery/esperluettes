# Migrate npm to pnpm — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**No application domain owns this.** It is repository tooling: Node package
manager, CI, root scripts, husky, setup/agent docs, and one Composer script
string. Nothing under `app/Domains/` changes behaviour.

### 1.1 Surfaces touched (non-domain)

| Surface | Role |
|---------|------|
| Root Node manifest + lockfile | Source of truth for deps; `packageManager` pin |
| CI (`.github/workflows/pr-tests.yml`) | Frozen pnpm install + same build/test steps |
| Root scripts / husky / `composer.json` `dev` | Stop hardcoding `npm` / `npx` for project tooling |
| Setup + CONTRIBUTING + AGENTS + Deploying + e2e + loop skills | Human/agent instructions match pnpm |
| `docs/adr/` | New home for the package-manager ADR |

## 2. Data model

N/A — no database tables, models, or migrations.

**Lockfile contract:** the committed Node resolution artifact is
`pnpm-lock.yaml` only. `package-lock.json` is removed. Installs that would
mutate the lockfile fail in CI (`pnpm install --frozen-lockfile`).

## 3. PHP architecture

N/A for domains, controllers, policies, events, routes.

**Composer seam only:** the `scripts.dev` string that today shells out to
`npm run dev` must invoke the Vite script through pnpm so
`composer run dev` / Sail’s usual “all local servers” path keeps working.
No PHP classes.

## 4. Frontend architecture

No product UI. Asset pipeline stays Vite + the existing `package.json` script
names (`build`, `dev`, `test`, `gate`, …).

**Tooling contracts:**

1. **`package.json` `packageManager`** — exact `pnpm@<version>` string so
   Corepack (and CI) resolve the same binary.
2. **pnpm major** — pin a **pnpm 11.x** (Node 22+; CI already uses Node 24) so
   `minimumReleaseAge` defaults to 1440 minutes (24h) without a project opt-in.
   If BUILD cannot land on 11.x for a concrete blocker, fall back to
   pnpm ≥10.16 and set `minimumReleaseAge: 1440` explicitly (see §7 / §9).
3. **Project config** — only add `.npmrc` / `pnpm-workspace.yaml` settings when
   needed for the fallback or for non-default behaviour; do not duplicate
   defaults that pnpm 11 already provides.
4. **Binary invocation** — project scripts and husky use `pnpm` /
   `pnpm exec …` (not `npm` / `npx`) for tools installed in this repo.
5. **Gate asset detection** — treat `pnpm-lock.yaml` (and drop sole reliance on
   `package-lock.json`) as an asset-config path so lockfile-only changes still
   trigger the vite step when appropriate.
6. **Script name `package`** — keep it; docs use `pnpm run package`.

## 5. Deptrac

No new edges. No domain PHP changes.

## 6. Testing strategy

| Layer | What |
|-------|------|
| Integration / PHP | N/A — no domain behaviour |
| Vitest | Existing suite must still pass after install via pnpm (gate) |
| Gate | Green after migration (docs + deptrac + scoped suites + asset build when lock/scripts touch assets) |
| CI | Workflow must install with frozen lockfile and run build + JS tests |
| VERIFY | Smoke that setup docs’ commands are coherent; no product UI checklist. Prefer a short “developer path” check (pnpm install / `pnpm run gate -- --quick` or equivalent) over Playwright product flows |

Success criteria from the functional spec: a clean clone following setup docs
gets a working toolchain; CI is green; ADR exists; active docs no longer teach
npm for this repo.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How developers get pnpm | A: Corepack + `packageManager` field. B: Document global `npm i -g pnpm` only. C: Rely on Sail image’s global pnpm only. | **A** | Reproducible version on host and CI; Sail global remains a fallback, not the contract |
| 2 | pnpm major / 24h age | A: pnpm 11.x (default `minimumReleaseAge` 1440). B: pnpm 10.16+ + explicit config. C: No age delay. | **A** (B if 11 blocked) | Matches the request’s supply-chain reason with least custom config; CI Node 24 satisfies pnpm 11’s Node 22+ floor |
| 3 | ADR location | A: new `docs/adr/`. B: section in `docs/Architecture.md`. C: only the task README. | **A** | Request asked for an ADR; no prior home; keeps Architecture.md for DDD |
| 4 | Lockfile migration | A: `pnpm import` from existing lock then verify. B: delete lock and fresh resolve. | **A** preferred | Preserves current resolutions (incl. Quill pin / audit posture); B only if import fails |
| 5 | CI pnpm bootstrap | A: Corepack enable after `setup-node`. B: `pnpm/action-setup`. | **A** | Same mechanism as local docs; one less third-party action unless Corepack proves awkward |
| 6 | Dual manager support | A: pnpm only. B: document both temporarily. | **A** | Spec §8; dual docs rot immediately |

## 8. File layout

New artifacts only (edits to existing files are PLAN’s list):

```
docs/adr/
  README.md                 # one-line index of ADRs
  0001-use-pnpm.md          # why pnpm, consequences, opt-outs
package.json                # packageManager field (existing file, contract above)
pnpm-lock.yaml              # replaces package-lock.json
```

Optional only if fallback path B in §7#2 fires:

```
pnpm-workspace.yaml         # or .npmrc — minimumReleaseAge: 1440
```

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|--------------------|
| pnpm 11 defaults (`blockExoticSubdeps`, `strictDepBuilds`, `verifyDepsBeforeRun`) surprise install or scripts | First `pnpm install` or `pnpm run` fails for a reason that is clearly a v11 default — pin 10.16+ with explicit age instead, document in ADR |
| Corepack disabled in some Node builds / corporate images | Setup fails at `corepack enable` — document Sail global pnpm + `packageManager` still for CI |
| `pnpm import` drifts from npm lock semantics | Gate or vitest fails after import — fresh resolve (tradeoff #4 B) and re-check Quill pin |
| Agents/docs still say `npm run gate` in a file we marked out of scope | Confusing follow-ups — expand the docs sweep only for *active* instructions, not `_done/` |
