# Domain docs gate check — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Document Follow + Domain Registry row | S | — | DONE |
| 2 | Docs-check helpers + Vitest suite | S | — | TODO |
| 3 | Wire rules 4–5 into `check-docs.js` | S | 1, 2 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/3)` resume correctly.

**Order rationale:** Phase 1 closes the only current compliance gap (Follow) *before*
any new gate rules run, so phases 2–3 never land a red gate on `main`. Phase 2
extracts and tests pure helpers while `main()` still runs only the existing three
checks — behaviour unchanged until phase 3.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation (phases 2–3).
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- VERIFY is skipped — no browser surface (architecture §6; same as
  `domain-claude-md-shims`).

---

## Phase 1 — Document Follow + Domain Registry row

**Goal.** Create Follow's documentation trio and register the domain in root
`AGENTS.md` so the tree is already compliant before rules 4–5 exist.

**Architecture sections:** §1.1 (Follow), §8 (file layout — Follow paths only).

**Deliverables.**
- `app/Domains/Follow/README.md` — human-oriented domain overview per
  `.agents/skills/document-domain/references/content-guide.md`: purpose (user
  follow relationships, follow button, profile Following tab), table
  `follow_follows`, cross-domain integrations (Profile tab registry,
  Settings `hide-following-tab`, Notification types, Story event listeners).
- `app/Domains/Follow/AGENTS.md` — agent instructions: README pointer, Public
  API (`FollowPublicApi`), events emitted (`UserFollowed`), listeners
  (`StoryCreated`, `StoryVisibilityChanged`, `UserDeleted`), registry
  integrations (Profile tab, Settings parameter, NotificationFactory group),
  non-obvious invariants (no FK to `users`; `canViewFollowingTab` requires
  confirmed viewer; new-story notifications only for public stories the follower
  can read).
- `app/Domains/Follow/CLAUDE.md` — exactly `@AGENTS.md` + Unix newline (no
  other bytes).
- `AGENTS.md` — one new Domain Registry row for **Follow** between **FAQ** and
  **Home** (alphabetical among neighbours): Path `` `app/Domains/Follow` ``,
  Responsibilities and Tables filled from the domain's actual code (table:
  `follow_follows`).

Use the `document-domain` skill; read Follow's `Public/`, `Private/`, and
`Tests/` on disk — no PHP changes in this phase.

**Tests.**
- None new. Existing docs-step rules (Feature_Planning ban, relative links)
  exercise the new markdown once committed.

**Acceptance.**
- ✅ `app/Domains/Follow/` has `README.md`, `AGENTS.md`, and `CLAUDE.md`.
- ✅ `CLAUDE.md` bytes are exactly `@AGENTS.md\n` (verify with
  `cmp -s <(printf '@AGENTS.md\n') app/Domains/Follow/CLAUDE.md` or equivalent).
- ✅ Root `AGENTS.md` Domain Registry includes a row whose Path is
  `` `app/Domains/Follow` ``.
- ✅ New docs contain no `Feature_Planning` references and all relative links
  resolve (existing docs step).
- ✅ `pnpm run gate` green.

---

## Phase 2 — Docs-check helpers + Vitest suite

**Goal.** Extract testable pure helpers for domain trio and registry checks, and
prove them with a colocated Vitest suite — without changing what `main()` enforces
yet.

**Architecture sections:** §3.1 (docs checker contract — rules 4–5 logic only),
§6 (Node/Vitest test vehicle).

**Deliverables.**
- `scripts/check-docs.js` — refactor: export pure helpers (keep existing rules
  1–3 and `main()` behaviour identical). Suggested exports (names may vary if
  clearer, but responsibilities must match):
  - `listDomainNames(root)` — immediate subdirectory names of
    `app/Domains/`, directories only, non-directories ignored (functional §4.1).
  - `checkDomainTrio(root, domainName)` — returns failure strings for missing
    `README.md` / `AGENTS.md` / `CLAUDE.md` or wrong CLAUDE shim (exact
    `@AGENTS.md\n`, architecture §3.1 rule 4).
  - `parseRegistryDomainPaths(agentsMdContent)` — locate `## Domain Registry`,
    collect Path cells matching `` `app/Domains/<Name>` `` backtick form
    (architecture §3.1 rule 5).
  - `checkRegistrySync(domainNames, registryPaths)` — bidirectional drift:
    disk domain missing from registry, registry path with no disk directory;
    failure strings name the domain/path (functional §4.3).
- `scripts/check-docs.test.js` — Vitest tests using temporary fixture dirs/files
  under the test (e.g. `fs.mkdtempSync`), cleaned in `afterEach`. No mutation of
  the real repo tree.
- `vitest.config.js` — extend `test.include` to also match
  `scripts/**/*.test.js` (today only `app/Domains/**/Resources/js/**/*.test.js`).

**Tests** (names as they will exist in `scripts/check-docs.test.js`):
- `listDomainNames returns only immediate subdirectories`
- `listDomainNames ignores files at app/Domains root`
- `checkDomainTrio passes when trio present and CLAUDE shim exact`
- `checkDomainTrio fails on missing README, AGENTS, or CLAUDE`
- `checkDomainTrio fails when CLAUDE content is not exactly @AGENTS.md newline`
- `parseRegistryDomainPaths extracts app/Domains paths from registry table`
- `parseRegistryDomainPaths ignores registry paths outside Domain Registry section`
- `checkRegistrySync fails when disk domain absent from registry`
- `checkRegistrySync fails when registry row points at missing directory`

**Acceptance.**
- ✅ `pnpm test` (Vitest) passes including the new script tests.
- ✅ `node scripts/check-docs.js` on the clean tree still runs only the three
  existing checks (rules 4–5 not yet in the `checks` array — grep confirms).
- ✅ `pnpm run gate` green.

---

## Phase 3 — Wire rules 4–5 into `check-docs.js`

**Goal.** Enforce the documentation trio and bidirectional Domain Registry sync
in the gate docs step.

**Architecture sections:** §3.1 (full checker contract), §6 (gate acceptance).

**Prior phases left:** Follow documented and registered (phase 1); helpers
exported and covered by Vitest (phase 2). All other domains already had the
trio and registry rows from `domain-claude-md-shims`.

**Deliverables.**
- `scripts/check-docs.js` — add two entries to the `checks` array in `main()`:
  - `{ label: 'every domain has README.md, AGENTS.md, and CLAUDE.md shim', … }`
    — iterate `listDomainNames(root)`, aggregate `checkDomainTrio` failures.
  - `{ label: 'Domain Registry matches app/Domains on disk', … }` — read root
    `AGENTS.md`, `parseRegistryDomainPaths`, `checkRegistrySync`; same
    report style as existing checks (label + indented failures; all checks run;
    non-zero exit if any failed).

**Tests.**
- Phase 2 Vitest suite (no new test file required unless an integration helper
  is extracted — prefer extending existing tests with a `runAllChecks`-style
  export if useful).
- Manual negative spot-check (once, during implementation, cleaned up):
  create a temp fake domain dir missing docs → `node scripts/check-docs.js`
  exits 1 with expected message → remove temp dir. Do **not** leave fixtures
  on disk (architecture §9).

**Acceptance.**
- ✅ `node scripts/check-docs.js` exits 0 on the clean tree (all five checks).
- ✅ Temporary violation (fake domain dir or bad CLAUDE in a fixture-only test)
  produces a failure message naming the domain and the rule; fixture removed
  after.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

No browser surfaces — VERIFY skipped (architecture §6; functional §3).

| Surface | Check | OK? |
|---------|-------|-----|
| N/A — developer tooling | VERIFY skipped; proof is Vitest + gate docs step | — |

---

## Open items

| Item | Needed by | Status |
|------|-----------|--------|
| `vitest.config.js` currently excludes `scripts/` — phase 2 must widen `include` | Phase 2 | Verified during PLAN |
| Registry parser assumes current `## Domain Registry` markdown table with backtick-wrapped Path cells — format change would break parsing | Phase 3 | Verified: matches root `AGENTS.md` today |
| CLAUDE shim check is byte-exact Unix `\n` — CRLF or trailing space on disk fails (architecture §9) | Phase 2–3 | Accepted tradeoff; Follow shim written with `\n` in phase 1 |
| Follow AGENTS.md must document `FollowPublicApi::canViewFollowingTab` confirmed-viewer rule and Settings-driven tab privacy | Phase 1 | Read `FollowPublicApi.php` and `FollowingTabVisibility.php` during BUILD |
