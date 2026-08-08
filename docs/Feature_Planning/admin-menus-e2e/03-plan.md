# Admin menus E2E — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.
>
> **Note:** PLAN ran in the orchestrator thread because subagents were
> unavailable (API limit). Content still follows `plan-phases`.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Sidebar `data-nav-key` markup | S | — | DONE |
| 2 | tech-admin E2E account + Playwright role | S | — | DONE |
| 3 | Core admin-menus inventory spec | S | 1, 2 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- `npm run gate` does **not** run Playwright; phase 3 acceptance also requires
  `npm run e2e:core` green.

---

## Phase 1 — Sidebar `data-nav-key` markup

**Goal.** Every admin sidebar link exposes a stable `data-nav-key` so E2E can
assert semantic identity (architecture §4, tradeoff #1).

**Depends on architecture.** §4 Frontend (markup contract); §1.1 Administration.

**Prerequisite discovery (resolved at PLAN).**
`AdminNavigationRegistry::getPagesForGroup()` currently calls `->values()->all()`,
which **drops** the associative page key. The Blade loop therefore has no
`$page['key']` today. This phase must merge the registry key into each page
payload before the view can render it.

**Deliverables.**
- `app/Domains/Administration/Public/Contracts/AdminNavigationRegistry.php` —
  in `getPagesForGroup`, include `'key' => $key` (collection key) on each page
  array passed to the view.
- `app/Domains/Administration/Private/Resources/views/components/navigation-item.blade.php` —
  accept optional `navKey` prop; render `data-nav-key="{{ $navKey }}"` on the
  `<a>` (keep existing `data-test-id="admin-sidebar-link"`).
- `app/Domains/Administration/Private/Resources/views/components/sidebar.blade.php` —
  pass `nav-key` for hardcoded links: `dashboard`, `back-to-site`; pass
  `:nav-key="$page['key']"` for registry pages.

**Tests.**
- Extend or add under `app/Domains/Administration/Tests/Feature/`:
  - `AdminNavigationTest` (or new `AdminNavigationNavKeyTest`): as admin (or
    tech-admin), GET `/administration`, assert HTML contains
    `data-nav-key="dashboard"`, `data-nav-key="back-to-site"`, and at least one
    known registry key that admins see (e.g. `news.management` or
    `config.parameters`).
  - Assert a page the role cannot see does **not** appear with that key (e.g.
    moderator response has no `data-nav-key="maintenance"`).

**Acceptance.**
- ✅ Sidebar links for registry pages carry `data-nav-key` equal to the
  `registerPage` key.
- ✅ Hardcoded links carry `dashboard` and `back-to-site`.
- ✅ Existing `data-test-id="admin-sidebar-link"` count behaviour unchanged
  (PHP tests that divide by 2 still pass).
- ✅ `npm run gate` green.

---

## Phase 2 — tech-admin E2E account + Playwright role

**Goal.** A `tech-admin` identity exists in the e2e seed world and as a
Playwright fixture, matching architecture §1.1 Auth and §4 Playwright.

**Independent of phase 1** (can land in either order; index lists it second).

**Deliverables.**
- `app/Domains/Auth/Database/Seeders/E2eAccountsSeeder.php` — add
  `'tech-admin@e2e.test' => [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]` to
  `ACCOUNTS` (and optional `TECH_ADMIN_EMAIL` constant for parity with
  `ADMIN_EMAIL` / `MODERATOR_EMAIL`).
- `e2e/support/fixtures.ts` — extend `RoleName` with `'tech_admin'`; add
  `ACCOUNTS.tech_admin` (`email: 'tech-admin@e2e.test'`, password `password`,
  displayName / profileSlug consistent with other staff accounts, e.g.
  `E2E Tech Admin` / `e2e-tech-admin` — Profile seeder still derives from email
  local-part for `@e2e.test` users).
- `e2e/support/test.ts` — add `tech_admin: roleFixture('tech_admin')`.
- `e2e/support/auth.setup.ts` — no structural change if it already loops
  `ROLES` from fixtures (verify it picks up the new role automatically).
- Brief comment in `e2e/README.md` Roles list: add `tech_admin` to the
  documented fixtures (one line).

**Tests.**
- Prefer a small PHP feature/unit check only if cheap: e.g. when seeding with
  the seeder in isolation is already covered by convention — **do not** invent a
  heavy APP_ENV dance. Primary proof is phase 3 auth + inventory.
- If the repo already has a seeder mirror test, extend it; otherwise phase-2
  gate green + TypeScript `RoleName` compile via existing tooling is enough,
  with phase 3 as the behavioural lock.

**Acceptance.**
- ✅ `E2eAccountsSeeder::ACCOUNTS` includes `tech-admin@e2e.test` with
  `tech-admin` + `user-confirmed`.
- ✅ Playwright `RoleName` / `ACCOUNTS` / `test` fixture include `tech_admin`.
- ✅ `npm run gate` green.

---

## Phase 3 — Core admin-menus inventory spec

**Goal.** Permanent core Playwright coverage: for each staff role, sidebar
`data-nav-key` set equals the explicit mapping (architecture §4, §6; functional
§4.1–§4.4).

**Builds on.** Phase 1 (`data-nav-key` in markup). Phase 2 (`tech_admin` fixture).

**Deliverables.**
- `e2e/support/admin-nav-map.ts` — expected keys per role. Include hardcoded
  `dashboard` and `back-to-site` for all three. Registry keys from functional
  §3 (freeze current inventory):

  **All roles:** `dashboard`, `back-to-site`

  **moderator:** `calendar.activities`, `moderation.reasons`,
  `moderation.reports`, `moderation.admin.user-management`,
  `story.admin.moderation`, `news.management`, `news.pinned`,
  `auth.promotion_requests`, `events.admin.domain-events`

  **admin:** moderator set **plus** `config.parameters`,
  `config.feature-toggles`, `statistics.admin`, `static.pages`,
  `story_ref.audiences`, `story_ref.types`, and the other five story_ref page
  keys as registered today (verify exact keys from
  `StoryServiceRefProvider` / StoryRef provider at implement time — do not guess
  missing keys), `auth.users`, `auth.roles`, `auth.activation_codes`,
  `faq.categories`, `faq.questions` — **without** `maintenance`, `logs`

  **tech_admin:** admin set **plus** `maintenance`, `logs`

- `e2e/pages/AdminSidebar.ts` — `goto()` → `/administration`;
  `collectNavKeys(): Promise<string[]>` reading unique
  `[data-test-id="admin-sidebar-link"][data-nav-key]` (or
  `[data-nav-key]` within sidebar), deduped.
- `e2e/tests/core/admin-menus.spec.ts` — for `moderator`, `admin`, `tech_admin`
  fixtures: goto sidebar, expect collected keys (sorted) to equal mapping
  (sorted). Fail on missing or unexpected keys.

**Tests.**
- The Playwright file above is the test. No new PHP inventory twin (architecture
  tradeoff #3).

**Acceptance.**
- ✅ Spec lives under `e2e/tests/core/`.
- ✅ `npm run e2e:core` green (includes auth setup for `tech_admin`).
- ✅ Changing a mapped key expectation without matching markup/registration
  fails the spec (manual sanity during implement: temporarily wrong map → red).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| `/administration` as moderator | Sidebar shows only moderator-scoped groups; no Maintenance / Logs / Config / FAQ | |
| `/administration` as admin | Broader menu present; still no Maintenance / Logs | |
| `/administration` as tech-admin | Maintenance + Logs visible in addition to admin set | |
| Mobile viewport (optional) | Sidebar still usable; no duplicate-looking double list confusion (dedupe is for tests) | |

## Open items

| Item | Phase | Resolution |
|------|-------|------------|
| Exact seven `story_ref.*` page keys | 3 | Read `StoryRef` / Story service provider `registerPage` calls at BUILD start; list them in `admin-nav-map.ts` |
| `getPagesForGroup` drops keys | 1 | Fixed in phase 1 deliverables (merge `'key'`) |
| Profile for tech-admin | 2 | `E2eProfilesSeeder` already seeds all `*@e2e.test` — no extra work unless login fails |
