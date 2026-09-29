# Make the e2e specs type-check (@types/node) — functional specification

> REFINE output (`auto` mode). Describes **what** the change does, never **how**
> it is built. Every statement here is either from `00-request.md` or a stated
> assumption (see `DECISIONS.md`).

## 1. Overview

Developer tooling chore, no user-facing change. `pnpm exec tsc -p e2e --noEmit`
must pass: today it fails with `TS2688: Cannot find type definition file for
'node'` because `e2e/tsconfig.json` lists `"node"` in `types` while
`@types/node` is only a transitive dependency that pnpm does not hoist. Once it
passes, the type-check becomes part of `pnpm run gate`, so type errors in the
Playwright specs stop going unnoticed.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| e2e type-check | `tsc -p e2e --noEmit` over `e2e/**/*.ts` and `playwright.config.ts` |

## 3. Roles & visibility

N/A — no application behaviour changes; affects developers and CI only.

## 4. Functional requirements

### 4.1 Type-check passes

1. `@types/node` is a direct dev dependency, on the Node major CI uses (24,
   `.github/workflows/pr-tests.yml`).
2. `pnpm exec tsc -p e2e --noEmit` exits 0 on a clean checkout after
   `pnpm install`.
3. If resolving the Node types surfaces genuine type errors in the specs, they
   are fixed with the smallest change that satisfies the compiler — no
   behavioural rewrite of any spec.

### 4.2 Type-check guarded by the gate

1. `pnpm run gate` runs the e2e type-check as a step.
2. Like the other gate steps, it is scoped: it runs when the branch changed a
   file under `e2e/`, `playwright.config.ts`, `package.json` or the lockfile,
   and always with `--all`. Otherwise it is reported as skipped.
3. A type error in a spec makes the gate fail, with the compiler output shown.

## 5. Lifecycle

N/A — no data.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — tooling only |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no user-facing text |
| Mobile | N/A |
| Accessibility | N/A |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| — | none asked (`auto` mode) | see assumptions A1–A4 in `DECISIONS.md` |

## 8. Out of scope

- Rewriting the e2e specs beyond what the type-check requires.
- Running Playwright (`pnpm run e2e`) in the gate — it needs the app running.
- Type-checking any other TypeScript/JS outside `e2e/` and
  `playwright.config.ts`.
- Changing the Node version CI or Sail uses.

## 9. Open questions

None.
