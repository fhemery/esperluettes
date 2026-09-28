# Upgrade pnpm to 12 — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Move the pin, workspace settings and docs to pnpm 12.6.0 | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

One phase only. The version pin and the `auditConfig` → `audit.ignore` rename
cannot be split: pnpm 12 rejects unrecognised workspace keys, so a pin-only
phase might fail to install, and a rename-only phase has nothing to check
against on pnpm 11. The doc edits are a few lines and would not be verifiable
apart from the pin they describe.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- There is no application code, so "failing test first" does not apply. The
  checks in "Tests" below take its place: run the ones you can before the
  change to see the baseline (e.g. `pnpm --version` → `11.21.0`).
- Never edit files with Python, `sed` or `awk`. Use the Edit/Write tools.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Move the pin, workspace settings and docs to pnpm 12.6.0

**Goal.** The repo, CI and the docs run on pnpm 12.6.0, with the same
supply-chain guarantees as under 11.21.0 and no dependency version change.

Reads: `02-architecture.md` §6 (verification table), §7 (T1–T4), §8 (file
layout), §9 (risks). `DECISIONS.md` A1–A10 are settled; do not re-open them.

**Deliverables.**

- `package.json` — `"packageManager": "pnpm@11.21.0"` → `"pnpm@12.6.0"`.
  Nothing else in the file.
- `pnpm-workspace.yaml`
  - Replace the `auditConfig:` / `ignoreGhsas:` block with:
    ```yaml
    audit:
      ignore:
        # (keep the existing three-line Quill justification comment verbatim)
        - GHSA-v3m3-f69x-jf25
    ```
    If 12.6.0 turns out to expect a different shape for `audit.ignore` (see
    Open items O1), follow the pnpm docs for the shape and keep the same GHSA
    and comment.
  - Comment `# Allow dependency install scripts (pnpm 11+).` → drop the
    version qualifier (no "pnpm 11" may remain; see Acceptance).
  - Comment `(pnpm 11 defaults to 24 h)` → state the pnpm 12 default, after
    confirming it (O2). If unconfirmed, say "pnpm's default is 24 h".
  - `allowBuilds` and `minimumReleaseAge: 2880` stay unchanged.
- `pnpm-lock.yaml` — only if `pnpm install` under 12 rewrites it, and then
  format/metadata only (A5). If any `version:` or `resolution:` line moves,
  stop and report: that is a dependency upgrade and is out of scope.
- `.github/workflows/pr-tests.yml` — **no change** unless the Q2 check below
  fails (T1 / A7). In that case replace the "Install pnpm" step with
  `pnpm/action-setup` (it reads `packageManager` when no `version` is given),
  and record the switch as a new row in `DECISIONS.md`.
- `docs/adr/0001-use-pnpm.md`, updated in place (A3), section "Supply-chain:
  minimum release age":
  - "We pin **pnpm 11.x** (see `packageManager`), which requires Node 22+ (CI
    uses Node 24) and would otherwise default to 24 h." → pin **pnpm 12.x**;
    replace "requires Node 22+" with "declares `node >=18`" (CI uses Node 24);
    keep "would otherwise default to 24 h" only if O2 confirms it.
  - "gated by pnpm 11 `strictDepBuilds`" → "gated by pnpm's `strictDepBuilds`
    (default `true`)".
  - Where the ADR mentions audit (the `pnpm audit` line under "Script names"),
    add that known, mitigated advisories are listed under `audit.ignore` in
    `pnpm-workspace.yaml`. One sentence; the ADR does not name the old key
    today, so there is nothing else to rename.
  - Add a dated amendment line in the same style as the existing
    "*Amended 2026-09-27:*" one, saying the pin moved to 12.x and the audit
    key was renamed. Use the date BUILD runs on.
  - The ADR must not link to `docs/Feature_Planning`.
- `docs/Setup.md` line 6, `docs/Setup_01a_Docker_Sail.md` lines 56 and 58,
  `docs/Setup_01b_Windows_Laragon.md` lines 45 and 56 — `11.21.0` → `12.6.0`.
  Re-grep afterwards: `grep -rn "11\.21" docs/*.md docs/adr` must print
  nothing.
- `.agents/skills/upgrade-dependencies/SKILL.md` line ~164 —
  "`auditConfig.ignoreGhsas` in `pnpm-workspace.yaml`" → "`audit.ignore` in
  `pnpm-workspace.yaml`" (A10). No other edit to that skill.
- Untouched on purpose (A4, A6): `docker/8.4/Dockerfile`, the "pnpm 11"
  example in `.agents/skills/write-adr/SKILL.md`, everything under
  `docs/Feature_Planning/_done/`, other tasks' `00-request.md`, `BACKLOG.md`.

**Tests.** No automated test is added: nothing in the repo can observe the
package-manager version at test time, and a committed test for it would be
speculative code. The phase is proven by these operational checks (architecture
§6), each run and its output noted in the commit body:

1. `pnpm --version` in the repo root prints `12.6.0` (pnpm self-switches from
   the global install via `packageManager`; if it does not, install 12.6.0
   globally the way the updated setup docs say).
2. `pnpm install --frozen-lockfile` succeeds, raises no
   `ERR_PNPM_UNRECOGNIZED_WORKSPACE_SETTINGS`, and `git diff --stat
   pnpm-lock.yaml` is empty or format-only.
3. `pnpm audit` lists `GHSA-v3m3-f69x-jf25` as ignored, not as a finding.
   As a negative control, temporarily comment out the entry, confirm the
   advisory appears as a finding, then restore it (not committed).
4. `pnpm config get strictDepBuilds` (or `pnpm config list`) shows `true`, and
   `pnpm config get minimumReleaseAge` shows `2880`.
5. Q2 — CI install mechanism: `npm install -g --prefix
   <scratchpad>/pnpm12 pnpm@12.6.0`, then `<scratchpad>/pnpm12/bin/pnpm
   --version` prints `12.6.0`. If the binary is broken, apply the T1 fallback
   described in Deliverables.
6. `pnpm run gate -- --all` (full run, asset build included, so esbuild's
   allowed build script must have run).

**Acceptance.**

- ✅ `package.json` pins `pnpm@12.6.0`; `pnpm --version` in the repo prints
  `12.6.0`.
- ✅ `pnpm install --frozen-lockfile` passes with no dependency version change
  in `pnpm-lock.yaml`.
- ✅ `pnpm-workspace.yaml` contains no `auditConfig` key; `audit.ignore` holds
  `GHSA-v3m3-f69x-jf25` with its justification comment.
- ✅ `pnpm audit` reports `GHSA-v3m3-f69x-jf25` as ignored.
- ✅ `strictDepBuilds` resolves to `true` and `minimumReleaseAge` to `2880`.
- ✅ `npm install -g pnpm@12.6.0` yields a working binary (or CI was switched
  to `pnpm/action-setup` and a `DECISIONS.md` row records why).
- ✅ `grep -rn "11\.21\|pnpm 11" docs/*.md docs/adr pnpm-workspace.yaml` prints
  nothing.
- ✅ `grep -rn "auditConfig\|ignoreGhsas" --exclude-dir=node_modules
  --exclude-dir=Feature_Planning .` prints nothing.
- ✅ ADR 0001 names pnpm 12.x, `node >=18`, `audit.ignore`, and has no
  "pnpm 11" qualifier on `strictDepBuilds`.
- ✅ `pnpm run gate -- --all` green.
- ✅ CI (`pr-tests.yml`) green on the PR (checked at VERIFY/WRAP, since it
  needs a push).

---

## Visual QA checklist

N/A — there is no UI change. VERIFY runs the operational checks of Phase 1
("Tests" 1–6) again from a clean `node_modules` (`rm -rf node_modules &&
pnpm install --frozen-lockfile`), then confirms CI is green on the PR.

| Surface | Check | OK? |
|---------|-------|-----|
| N/A — tooling only | Operational checks above replace the visual pass | |

## Open items

| # | Item | Needed by |
|---|------|-----------|
| O1 | Exact shape of `audit.ignore` in 12.6.0 (list of GHSA ids vs. a map with reasons). REFINE read that `auditConfig.ignoreGhsas` was renamed to `audit.ignore` in 11.16; BUILD confirms against the 12.6.0 docs, and check 3 proves it is honoured. | Phase 1 |
| O2 | pnpm 12's default `minimumReleaseAge` — still 24 h? Decides the wording of the workspace comment and of the ADR's "would otherwise default to 24 h". Check with `pnpm config get minimumReleaseAge` in a scratch dir with no workspace file, or the 12.x docs. | Phase 1 |
| O3 | Q3 from the spec: breaking changes in 12.1–12.6 not yet read. Skim the changelog for anything touching `allowBuilds`, `minimumReleaseAge`, `audit` or `--frozen-lockfile`; the full gate is the safety net. | Phase 1 |

Verified during PLAN: `pnpm@12.6.0` exists on npm; the only non-planning
references to `auditConfig`/`ignoreGhsas` are `pnpm-workspace.yaml` and
`.agents/skills/upgrade-dependencies/SKILL.md`; the `11.21.0` strings in the
setup docs sit at the lines listed above; CI's "Install pnpm" step reads
`packageManager` and needs no edit if Q2 passes.
