# Upgrade pnpm to 12 — functional specification

> REFINE output, written in `auto` mode: no interview. Every statement comes
> from `00-request.md`, from the codebase, or from the pnpm 12 release notes and
> docs. Anything else is an assumption (§7 and `DECISIONS.md`).

## 1. Overview

A tooling chore with no user-facing change. The repo moves from pnpm 11.21.0
to pnpm 12.6.0. That covers the `packageManager` pin that CI follows, the
workspace settings that pnpm 12 reads differently, ADR 0001 and the setup docs.
Developers and CI get the current pnpm major. The supply-chain guarantees ADR
0001 relies on keep working as before.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| `packageManager` pin | `package.json` field (`pnpm@11.21.0` today). CI installs exactly this version (`npm install -g "$(node -p …packageManager)"` in `.github/workflows/pr-tests.yml`). |
| Workspace settings | `pnpm-workspace.yaml` keys: `allowBuilds`, `minimumReleaseAge`, `auditConfig`. |
| Supply-chain guarantees | The behaviours ADR 0001 depends on: 48 h `minimumReleaseAge`, `strictDepBuilds` (default `true`) with the `allowBuilds` allowlist, `pnpm install --frozen-lockfile`, `pnpm audit` with an explicit ignore list. |

## 3. Roles & visibility

N/A. This is developer tooling with no app role involved.

## 4. Functional requirements

### 4.1 The repo runs on pnpm 12

1. `package.json` pins `pnpm@12.6.0`. That is the `latest` dist-tag, published
   2026-09-22, so it clears the 48 h release-age rule the repo applies to its
   own dependencies.
2. CI installs the pinned version through its existing `packageManager`
   lookup. The workflow's install mechanism needs no change unless pnpm 12's
   native-binary packaging breaks `npm install -g` (see §9).
3. `pnpm install --frozen-lockfile` passes with the committed lockfile, both
   locally and in CI.
4. The lockfile stays at `lockfileVersion: '9.0'`, since the pnpm 12 notes
   list no format change. If pnpm 12 rewrites it anyway, only format and
   metadata may change. No dependency version may move, because upgrading
   dependencies is out of scope.

### 4.2 Workspace settings keep their meaning

1. **`auditConfig.ignoreGhsas`**: this key was renamed to `audit.ignore` in
   pnpm 11.16. The docs say the old name only keeps working until the next
   major. The same GHSA (`GHSA-v3m3-f69x-jf25`) moves to `audit.ignore`, and
   its justification comment is kept. `pnpm audit` must still leave it out
   (since 12.4 an ignored advisory is reported separately rather than counted).
2. **Unrecognised keys**: pnpm 12 turns unknown keys in `pnpm-workspace.yaml`
   into a hard error (`ERR_PNPM_UNRECOGNIZED_WORKSPACE_SETTINGS`) when the
   project pins a pnpm it satisfies, which is the case here. Every key left in
   the file must be recognised by 12.6.0.
3. **`minimumReleaseAge: 2880`**: unchanged in 12 (still minutes). It stays.
4. **`strictDepBuilds` + `allowBuilds: { esbuild: true }`**: the default is
   still `true` and the map format is the same. esbuild's build script still
   runs, and a package outside the allowlist still fails the install.
5. The comments in `pnpm-workspace.yaml` that say "pnpm 11" are updated.

### 4.3 Documentation follows

1. ADR `docs/adr/0001-use-pnpm.md` is updated in place, as the request says:
   - it pins pnpm 12.x;
   - the "requires Node 22+" statement is corrected. pnpm 12 declares
     `node >=18`, and the Node version the repo actually uses is CI's Node 24;
   - the audit ignore list is named by its new key;
   - `strictDepBuilds` is referred to without the "pnpm 11" qualifier.
2. The setup docs that hard-code `11.21.0` are updated to `12.6.0`:
   `docs/Setup.md`, `docs/Setup_01a_Docker_Sail.md` and
   `docs/Setup_01b_Windows_Laragon.md`.
3. Historical planning records (`_done/`, other tasks' requests, the Done
   section of `BACKLOG.md`) are left as they are.

### 4.4 Success criteria

- `pnpm --version` prints `12.6.0` in the repo.
- `pnpm install --frozen-lockfile` succeeds with no lockfile diff, or with a
  format-only diff (4.1.4).
- `pnpm audit` runs and reports `GHSA-v3m3-f69x-jf25` as ignored, not as a
  finding.
- An install with a package missing from `allowBuilds` would still fail. This
  is checked by reading the resolved config or by one throwaway experiment. It
  is not committed.
- `pnpm run gate` is green, including the asset build (esbuild runs).
- CI (`pr-tests.yml`) is green on the PR.

## 5. Lifecycle

N/A. There is no data.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — tooling |
| Visibility / privacy | N/A — tooling |
| Settings | N/A — no user preference |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A — no data |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no user-facing text |
| Mobile | N/A |
| Accessibility | N/A |

## 7. Decisions confirmed

No interview took place (`auto` mode). The judgement calls are in the
"Assumptions" table of `DECISIONS.md`, A1–A6.

## 8. Out of scope

- Upgrading any other dependency. That is the `upgrade-dependencies` skill's
  job.
- Adopting new pnpm 12 features (`audit.ignorePrune`, `globalShims`,
  provisioning other package managers, …).
- Switching CI to `pnpm/action-setup` or corepack.
- Pinning pnpm in `docker/8.4/Dockerfile`, which installs the unpinned
  `latest` (see A4).
- Rewriting the example sentence in `.agents/skills/write-adr/SKILL.md` that
  mentions "pnpm 11". It is only an illustration.
- Rewriting historical planning records.

## 9. Open questions

| # | Question | Blocking? |
|---|----------|-----------|
| Q1 | Does 12.6.0 still read `auditConfig` or reject it? The migration to `audit.ignore` makes the answer moot, but BUILD should confirm the new key is honoured. | non-blocking |
| Q2 | pnpm 12 ships native executables through optional deps with a postinstall `install.js`. Does CI's `npm install -g pnpm@12.6.0` still yield a working `pnpm`? If not, DESIGN/BUILD adapt the CI install step, which is still in scope because CI and the pin must move together. | non-blocking |
| Q3 | Do minor releases 12.1–12.6 hold other breaking changes relevant here? Only the 12.0.0 notes were read. The success criteria in §4.4 would catch one. | non-blocking |
