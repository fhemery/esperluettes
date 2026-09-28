# Upgrade pnpm to 12 — architecture

> DESIGN output, `auto` mode. Tooling-only change: no domain, table, route or
> PHP code is touched. Sections that do not apply say so.

## 1. Domain placement

None. Everything here lives at the repo root (`package.json`,
`pnpm-workspace.yaml`, `pnpm-lock.yaml`, `.github/workflows/pr-tests.yml`) or
in `docs/`.

### 1.1 Changes in other domains

None.

## 2. Data model

N/A. There are no tables, models or lifecycle rules.

## 3. PHP architecture

N/A. There is no PHP change.

## 4. Frontend architecture

No runtime change. The asset build (Vite + esbuild) has to keep working under
pnpm 12. Specifically, esbuild's postinstall has to keep running through the
`allowBuilds` allowlist.

## 5. Deptrac

No new edge.

## 6. Testing strategy

There is no code to test. Verification is operational, following the success
criteria in `01-functional.md` §4.4:

| Check | How |
|-------|-----|
| Pinned version is used | `pnpm --version` → `12.6.0` inside the repo |
| Lockfile untouched | `pnpm install --frozen-lockfile` passes; `git diff pnpm-lock.yaml` empty or format-only |
| Workspace keys all recognised | same install does not raise `ERR_PNPM_UNRECOGNIZED_WORKSPACE_SETTINGS` |
| Audit ignore honoured | `pnpm audit` lists `GHSA-v3m3-f69x-jf25` as ignored, not as a finding |
| Build allowlist enforced | the resolved config shows `strictDepBuilds` true; esbuild's build ran (asset build passes) |
| CI install mechanism | `npm install -g pnpm@12.6.0` into a scratch prefix yields a working `pnpm` binary (Q2) |
| Everything else | `pnpm run gate` (full, not `--quick`), then CI on the PR |

## 7. Tradeoffs locked

These are all taken without asking (`auto` mode) and recorded as assumptions
in `DECISIONS.md`. None of them is expensive to reverse.

| # | Question | Taken | Rejected | Why |
|---|----------|-------|----------|-----|
| T1 | How does CI install pnpm 12? | Keep `npm install -g "<packageManager>"` | `pnpm/action-setup`; corepack | Only the pin moves, and CI keeps following it automatically. Revisit only if Q2 shows that `npm install -g` cannot produce a working pnpm 12 binary. In that case switch the step to `pnpm/action-setup` reading `packageManager`. |
| T2 | CI store cache key | Leave it keyed on `pnpm-lock.yaml` only | Add the pnpm version to the key | The store path is versioned by layout (`…/store/vN`), so a cache restored from 11 cannot corrupt a store read by 12. At worst the first run is a cold install. |
| T3 | Local developers on Volta/global pnpm 11 | Rely on pnpm's own `packageManager` self-switching, and update the setup docs to 12.6.0 | Enforce with `devEngines` / `engines` | Adding enforcement is a new feature, which is out of scope. |
| T4 | Audit ignore key | Move to `audit.ignore` | Keep `auditConfig.ignoreGhsas` | The docs say the legacy key does not outlive the major, and pnpm 12 rejects unknown keys. |

## 8. File layout

| File | Change |
|------|--------|
| `package.json` | `packageManager` → `pnpm@12.6.0` |
| `pnpm-workspace.yaml` | audit key renamed; "pnpm 11" comments updated |
| `pnpm-lock.yaml` | only if pnpm 12 rewrites it (format-only) |
| `.github/workflows/pr-tests.yml` | only if T1 flips |
| `docs/adr/0001-use-pnpm.md` | updated in place (A3) |
| `docs/Setup.md`, `docs/Setup_01a_Docker_Sail.md`, `docs/Setup_01b_Windows_Laragon.md` | version strings |

## 9. Risks acknowledged

- **Q2, native binary packaging.** pnpm 12 installs through optional
  `@pnpm/exe.<platform>` deps plus a postinstall. A CI or global npm
  configuration with `ignore-scripts` would produce a broken binary. The CI
  runner has no such setting today. The check in §6 catches this before the
  PR.
- **Sail image (A4).** `docker/8.4/Dockerfile` installs the unpinned `latest`,
  so a rebuild picks up 12.6.0 or later, and pnpm then switches to the pin.
  This was already the case before the task.
- **Minor-release changes (Q3).** Only the 12.0.0 notes were read. The full
  gate plus CI is the safety net.
