---
name: upgrade-dependencies
description: Audit and upgrade the PHP (Composer) then JS (pnpm) dependencies, with an approved plan before every change. Use when the user asks to "upgrade dependencies", "fix vulnerabilities", "update packages", "composer/pnpm audit", "bump Laravel", or runs /upgrade-dependencies [php|js].
---

# Upgrade dependencies

Two stages, **PHP first, then JS**. Each stage is the same loop:

1. **Audit** — read-only.
2. **Plan** — a table of what will change and why, then **stop and ask**.
3. **Apply** — only what the user approved, adjusted to their comments.
4. **Verify** — audit again, gate, e2e.
5. **Commit** — one commit for the stage.

Argument: none → both stages; `php` or `js` → that stage only.

## Ground rules

- **Say what you are about to do before each step**, in one or two lines, with
  the commands. Audit steps are read-only and need no approval. Every step that
  writes (`composer.json`, `package.json`, lockfiles, `pnpm-workspace.yaml`,
  code, git) runs **only after an explicit yes**. Ask with the question tool.
- Approving the plan covers **exactly the plan**. Anything that comes up
  afterwards needs a new yes: an extra package, an override, a code migration
  bigger than announced, reverting a bump, loosening a constraint.
- Default scope: **security fixes + patch/minor updates within the existing
  constraints**. **Major versions** go in a separate *optional* section, one
  line each. The user picks them individually, and none is taken by default.
- Never bypass the safeguards: pnpm's 48 h `minimumReleaseAge`
  ([ADR 0001](../../../docs/adr/0001-use-pnpm.md)), the `allowBuilds` allowlist,
  Composer's `audit.block-insecure`. When one of them blocks a fix, report it
  and let the user decide.
- Never run `composer update` or `pnpm update` without arguments unless that
  is the approved plan.

## 0. Preflight (once)

- `git status` must be clean. If it isn't, stop and ask.
- Branch: reuse the current `chore/upgrade-deps-*` branch if you are on one.
  Otherwise propose `chore/upgrade-deps-<YYYY-MM-DD>` from an up-to-date
  `main`, and create it once the user approves.
- Sail must be up (`./vendor/bin/sail ps`). If it isn't, ask before running
  `./vendor/bin/sail up -d`.
- **Target PHP version**: `composer.json` says `^8.3`, and CI runs 8.4
  (`.github/workflows/pr-tests.yml`). Ask the user which version production
  runs, unless they already said so this session. No package may need a PHP
  newer than production.

## 1. PHP stage

### 1.1 Audit (read-only)

```bash
./vendor/bin/sail composer audit --format=json
./vendor/bin/sail composer outdated --direct --format=json
./vendor/bin/sail composer outdated --format=json      # transitive, to find what the advisories hit
```

For each advisory, note the package, installed version, fixed versions,
severity, CVE/GHSA and whether the package is direct or transitive. For a
transitive one, find its parent with `composer why <pkg>`. Also note any
**abandoned** packages that `audit` reports.

`--with-all-dependencies` also moves **transitive** packages across majors
when a parent widens its range (e.g. Laravel 13.33 allowing guzzle 8). Before
the plan, dry-run the update (`sail composer update --dry-run -W …`) and list
every transitive major it would bring in section C, marked *(transitive)*,
with whether `app/` uses it directly. The user can hold one back with
`--with <pkg>:<constraint>`.

For each major candidate, read its changelog or UPGRADE guide (WebFetch the
GitHub release notes / `UPGRADE.md`). For `laravel/framework`, read the
official upgrade guide. Keep a one-line summary of the breaking changes that
touch this codebase: grep for the APIs they mention under `app/`, `config/`,
`bootstrap/`, `routes/`, `tests/`.

### 1.2 Plan — then stop

```text
Vulnerabilities: N (critical x, high y, …)

A. Security fixes
| Package | Direct? | Installed → Target | Advisory | How |
B. Patch / minor (within constraints)
| Package | Installed → Target | Notes |
C. Optional majors — not done unless picked
| Package | Current constraint → Proposed | Breaking changes relevant here | Effort |
D. Cannot fix now
| Package | Advisory | Why (blocked by X, needs PHP 8.x, no fixed release…) |
```

For each group, give the commands you will run (see 1.3). End with the
question: *approve A+B? which of C? any change?* **Wait.** If the user adjusts
the plan, show the revised table before applying.

### 1.3 Apply (approved items only)

| Case | Command |
|------|---------|
| Patch/minor of approved direct packages | `sail composer update <pkg…> --with-all-dependencies` |
| Transitive security fix | `sail composer update <vuln-pkg> <parent> --with-all-dependencies`; if it won't move, `sail composer why-not <pkg> <fixed-version>` → report, back to the user |
| Major | `sail composer require <pkg>:^X --with-all-dependencies` (`--dev` for require-dev), then apply the migration from its upgrade notes |

Keep `composer.json` sorted (`sort-packages` does it). `post-update-cmd`
republishes Laravel assets; commit what it changes. After a framework major,
compare `config/*.php` and `bootstrap/*.php` with the new skeleton only where
the upgrade guide says so.

### 1.4 Verify

```bash
./vendor/bin/sail composer audit                 # expected: only the "D" items remain
pnpm run gate --all                           # --all: the lockfile alone doesn't trigger scoped steps
pnpm run e2e                                     # needs the app running — see e2e/README.md
```

When something fails, fix it if the cause is obvious and inside the approved
scope (e.g. a renamed API described in the upgrade guide). Otherwise stop and
offer the options: fix (with an estimate), drop that bump, or pin a version.

### 1.5 Commit

Use the `commit` skill. One commit per stage, with no scope because
dependencies affect the whole app:

```text
chore: upgrade PHP dependencies

Security: <pkg> x → y (GHSA-…), …
Updated: <pkg> x → y, …
Major: <pkg> x → y — <migration done in one line>
Remaining: <advisory> — <reason>
```

Report the stage result (vulnerabilities before → after, bumps, what's left),
then ask whether to go on to the JS stage.

## 2. JS stage

pnpm runs **on the host**, not in Sail.

### 2.1 Audit (read-only)

```bash
pnpm audit --json
pnpm outdated --format json
pnpm why <pkg>              # for each vulnerable transitive package
```

Things to know about this repo:

- **Exact pins** in `dependencies` (alpinejs, @alpinejs/intersect, axios,
  quill, quill-delta, uuid) are deliberate. A bump keeps the exact pin
  (`--save-exact`). `alpinejs` and `@alpinejs/intersect` always move together.
- `minimumReleaseAge` (48 h, set in `pnpm-workspace.yaml`): a version
  published less than 48 h ago won't install. List it under "Cannot fix now —
  retry after <date>". The age is checked at resolution only: if it is ever
  tightened, regenerate the lockfile (`rm pnpm-lock.yaml && pnpm install`).
- `ERR_PNPM_UNEXPECTED_STORE`: `node_modules` was linked from another store
  (typically the VS Code snap terminal's). Nothing is written. Ask the user;
  the usual fix is `rm -rf node_modules` and install again.
- Known advisories with no fixed release and a documented mitigation sit in
  `audit.ignore` in `pnpm-workspace.yaml`. Re-check each on every
  run: drop the entry once a fix ships.
- For a major of **Vite, Tailwind, Vitest, Playwright, Quill, Alpine**, check
  the migration guide against this repo's config: `vite.config.*`, the CSS
  entry points, `vitest.config.*`, `playwright.config.*`, Quill usage in the
  Editor domain.

### 2.2 Plan — then stop

Same four tables as 1.2 (A security, B patch/minor, C optional majors, D
cannot fix). For a transitive advisory, the **How** column says in this order
of preference:

1. update the parent that brings it in;
2. `pnpm update <pkg>` if the parent's range already allows the fix;
3. last resort: an `overrides:` entry in `pnpm-workspace.yaml`, with a comment
   naming the advisory, and a note to remove it once the parent is updated.

State explicitly if a new version needs an install script (to be added to
`allowBuilds`). **Wait.**

### 2.3 Apply (approved items only)

| Case | Command |
|------|---------|
| Patch/minor within ranges | `pnpm update <pkg…>` |
| Exact-pinned package | `pnpm add --save-exact <pkg>@<version>` |
| Major | `pnpm add [-D] <pkg>@^X`, then the migration |
| Override | edit `pnpm-workspace.yaml` with the Edit tool, then `pnpm install` |

Never add a `package-lock.json`. Only `pnpm-lock.yaml` changes.

### 2.4 Verify

```bash
pnpm audit                    # expected: only the "D" items remain
pnpm install --frozen-lockfile   # what CI does: the lockfile must be consistent
pnpm run gate --all        # full, including the asset build
pnpm run e2e:setup            # only if playwright moved — downloads its Chromium
pnpm run e2e
```

Same failure handling as 1.4. Take majors in **small batches** (low-risk
tooling together, a bundler on its own), each verified and committed, so a
failure points at one cause. An e2e failure is not always the upgrade's fault:
a new build changes asset hashes, which can expose a brittle spec — find the
cause before blaming the bump.

### 2.5 Commit

`chore: upgrade JS dependencies`, with the same body layout as 1.5.

## 3. Final report

- Vulnerabilities before → after, per stage.
- What was bumped, and the majors taken.
- **Deferred**: majors not picked, advisories left (with the reason and the
  date to retry, if any), overrides to remove later. Offer to add them to the
  backlog with `add-task`.
- Whether the branch should be pushed / a PR opened. Only on a yes.
