# Upgrade pnpm to 12

> WRAP output — the compact record of the finished task. Durable rules live in
> [`docs/adr/0001-use-pnpm.md`](../../adr/0001-use-pnpm.md).

**Status:** DONE — 2026-09-28 (CI on the PR not yet run) · **Domain(s):** `dev`
(repo tooling only, no domain touched)

## What it does

Moves the repo's pinned package manager from pnpm 11.21.0 to **12.6.0**
(`packageManager` in `package.json`). No project dependency version moved. The
only config change forced by 12 is the audit-ignore key rename; the lockfile
gains a block recording pnpm itself. ADR 0001, setup docs and the
`upgrade-dependencies` skill follow.

## Key behaviour

- **Version:** 12.6.0 = npm `latest` at the time; 12.8.1 (`next-12`) was
  skipped because it was younger than the repo's 48 h release-age rule.
- **Lockfile:** `pnpm-lock.yaml` now starts with an extra YAML document
  (`importers['.'].packageManagerDependencies.pnpm: 12.6.0` + the 14
  `@pnpm/exe.<platform>` binaries in `packages`/`snapshots`, 158 lines). pnpm 12's
  `--frozen-lockfile` **fails** if this block does not match `packageManager`
  ("Cannot update packageManagerDependencies with frozen-lockfile"). Every
  future pin bump must regenerate it (plain `pnpm install`).
- **Audit ignore:** `auditConfig.ignoreGhsas` → `audit.ignore` (plain list of
  GHSA ids; still only `GHSA-v3m3-f69x-jf25`, Quill). pnpm 12 hard-errors on
  unknown workspace keys (`ERR_PNPM_UNRECOGNIZED_WORKSPACE_SETTINGS`), so the
  old key could not stay. `pnpm audit` reports it as "1 ignored: 1 low".
- **`minimumReleaseAge: 2880`** unchanged; pnpm 12's own default is still 24 h.
  Side effect of setting it explicitly: `minimumReleaseAgeStrict` defaults to
  `true` in 12 → too-young versions are refused with a prompt instead of being
  silently excluded. Accepted, no setting added.
- **`strictDepBuilds`:** `pnpm config get` prints `undefined` under 12 (defaults
  aren't listed) — it is still `true`; proven by a scratch project failing with
  `ERR_PNPM_IGNORED_BUILDS`. `allowBuilds: { esbuild: true }` unchanged.
- **CI install unchanged:** `npm install -g "<packageManager>"` still yields a
  working pnpm 12 binary (native `@pnpm/exe.*` via postinstall — would break
  under `ignore-scripts`).
- **Local devs** on a global pnpm 11 are switched to 12.6.0 by pnpm's own
  `packageManager` self-switching; no `devEngines`/`engines` enforcement.

## Where the code lives

| Concern | Path |
|---------|------|
| Pin | `package.json` → `packageManager` |
| Workspace settings (`allowBuilds`, `minimumReleaseAge`, `audit.ignore`) | `pnpm-workspace.yaml` |
| pnpm self-record | `pnpm-lock.yaml` (first YAML document) |
| CI (untouched) | `.github/workflows/pr-tests.yml` |
| Durable rationale | `docs/adr/0001-use-pnpm.md` (amended in place, 2026-09-28) |
| Install instructions | `docs/Setup.md`, `docs/Setup_01a_Docker_Sail.md`, `docs/Setup_01b_Windows_Laragon.md` |
| Audit-key mention | `.agents/skills/upgrade-dependencies/SKILL.md` §2.1 |

## Extension points used

None.

## Decisions worth remembering

All taken without asking (`auto` mode); the ones most likely to be reversed:

- **Lockfile carries `packageManagerDependencies`** (A14). Plan allowed only a
  format-only lockfile rewrite; this is metadata about pnpm, not a dependency,
  and CI's frozen install needs it while the pin exists.
- **`minimumReleaseAgeStrict` left at its new `true` default** (A13). Reverse
  with `minimumReleaseAgeStrict: false` in `pnpm-workspace.yaml`.
- **`docker/8.4/Dockerfile` stays unpinned** (`npm install -g pnpm`, A4): a
  rebuild gets `latest`, then pnpm self-switches to the pin.
- ADR 0001 amended in place, no superseding ADR (A3).
- CI keeps `npm install -g`; `pnpm/action-setup` was the fallback, not needed
  (A7/A16). CI store cache key unchanged — the store path is layout-versioned
  (A8).

## Plan vs code

- Plan/architecture expected `pnpm-lock.yaml` to be "empty or format-only";
  the code adds the 158-line `packageManagerDependencies` document (above).
  ADR amendment records it.
- Otherwise the single phase shipped as planned. `pr-tests.yml` was not
  modified.

## Not done

- **Pending:** CI (`pr-tests.yml`) on the PR has not run — nothing pushed yet.
  Local checks from a clean `node_modules` and `pnpm run gate -- --all` passed
  (3016 PHP, 151 JS tests, build).
- Non-goals: upgrading other dependencies; adopting pnpm 12 features
  (`audit.ignorePrune`, `globalShims`, …); switching CI to
  `pnpm/action-setup`/corepack; pinning pnpm in the Dockerfile; rewriting the
  illustrative "pnpm 11" in `.agents/skills/write-adr/SKILL.md`; historical
  planning records (`_done/migrate-npm-to-pnpm.md` still says pnpm 11 — true
  at the time).
- Nothing cut mid-build. No open questions. No backlog rows added. No e2e specs.
