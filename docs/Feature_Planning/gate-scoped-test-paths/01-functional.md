# Gate — scoped test paths — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

When a branch touches more than one domain, `npm run gate` (default scoped
mode) must still run the PHP tests for every impacted domain and exit green or
fail on real test failures — never abort because too many path arguments were
passed. Developers and agents rely on the scoped gate for the inner loop;
`-- --all` remains available but must not be required solely because several
domains changed.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Scoped gate | Default `npm run gate` run that selects PHP test directories from files changed vs `main` |
| Full gate | `npm run gate -- --all` — entire PHP suite via `test:parallel` with no path args |
| Impacted domain dirs | One or more `app/Domains/<D>/Tests` directories chosen by the gate’s change plan |

## 3. Roles & visibility

N/A — developer / CI tooling only; no end-user surface.

| Role | Can see | Can do |
|------|---------|--------|
| Guest / app roles | N/A | N/A |

## 4. Functional requirements

### 4.1 Scoped gate with several impacted domains

1. A branch (or dirty tree) that changes files in ≥2 domains such that the gate
   plan resolves to ≥2 test directories.
2. `npm run gate` (without `--all`) runs PHP tests covering **all** of those
   directories.
3. The command does **not** fail with Symfony/ParaTest
   `Too many arguments, expected arguments "path"`.
4. Exit status reflects real test / other gate step outcomes only.

### 4.2 Scoped gate with one impacted domain

1. Unchanged behaviour: a single domain dir still works as today.

### 4.3 Full gate (`-- --all`)

1. Unchanged: pathless parallel suite remains green when the suite is green.

### 4.4 Direct `artisan test:parallel` with several dirs

1. Invoking `artisan test:parallel` with multiple directory arguments (the
   shape the gate already uses) must run tests under **all** of those
   directories successfully when the tests pass — same contract as the gate needs.
2. Zero directories / pathless invocation remains the full-suite parallel run.

### 4.5 Failure modes

1. If any selected domain’s tests fail, the gate fails (existing behaviour).
2. Empty plan / no PHP impact continues to skip or run as today’s gate already
   decides — not redesigned here.

## 5. Lifecycle

N/A — no persistent domain data.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — tooling |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no user-facing copy |
| Mobile | N/A |
| Accessibility | N/A |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Mode | `auto` |
| 2 | Success criterion | Scoped gate with ≥2 domain dirs runs all of them; no “too many arguments” abort |
| 3 | Prefer which call site | Prefer teaching `test:parallel` to accept several dirs (matches its signature and the gate’s existing argv) if cheap; looping in `gate.js` is acceptable fallback |

## 8. Out of scope

- Changing how domains are selected, `maxDirs`, or dependent-domain expansion
- Vitest scoping, deptrac, docs gate steps
- Making husky pre-commit use `test:parallel` for multi-dir staged runs (it
  already uses non-parallel `artisan test` with multiple dirs)
- Reworking Collision / ParaTest upstream beyond this app’s wrapper command
- Adding a full automated suite for `gate.js` itself (optional smoke is fine;
  not required for “done”)

## 9. Open questions

None blocking.
