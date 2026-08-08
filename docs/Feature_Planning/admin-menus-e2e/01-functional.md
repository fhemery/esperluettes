# Admin menus E2E — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Playwright E2E coverage that, for each staff role allowed into the admin panel
(`moderator`, `admin`, `tech-admin`), asserts the sidebar shows exactly the
expected set of navigation links — no missing item, no extra item. An explicit
role→menu mapping lives with the tests and must be updated when a domain
registers or changes an admin page. The suite belongs to the permanent core
regression set (`e2e/tests/core/`, `npm run e2e:core`).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Admin panel | The `/administration` shell (sidebar + content), gated to moderator / admin / tech-admin |
| Sidebar link | A navigation entry in the admin sidebar (registry pages + the two hardcoded links: Tableau de bord, Retour au site) |
| Role→menu mapping | The authoritative expected set of sidebar links per role, kept beside the E2E tests |
| Core regression suite | Specs under `e2e/tests/core/`, run by `npm run e2e:core` |

## 3. Roles & visibility

This feature is a test contract, not a product surface. The roles under test
are the ones that can open the admin panel today:

| Role | Can see (sidebar) | Under test? |
|------|-------------------|-------------|
| Guest / `user` / `user-confirmed` / author | No admin panel | No — already refused by the layout |
| Moderator | Dashboard, Retour au site, plus moderation-scoped registry pages only | Yes |
| Admin | Everything a moderator sees, plus config / stats / static / story refs / auth admin / FAQ — **except** maintenance and logs | Yes |
| Tech-admin (`tech-admin`) | Everything an admin sees, **plus** maintenance and logs | Yes |

Expected registry pages per role (from current `registerPage` permissions —
the mapping must stay in sync with code):

- **Moderator:** calendar.activities · moderation.reasons · moderation.reports · moderation.admin.user-management · story.admin.moderation · news.management · news.pinned · auth.promotion_requests · events.admin.domain-events
- **Admin:** moderator set **minus** nothing from that set, **plus** config.parameters · config.feature-toggles · statistics.admin · static.pages · seven story_ref pages · auth.users · auth.roles · auth.activation_codes · faq.categories · faq.questions — **without** maintenance / logs
- **Tech-admin:** full admin set **plus** maintenance · logs

## 4. Functional requirements

### 4.1 Sidebar inventory per role

1. An E2E account for the role logs in and opens `/administration`.
2. The test reads all visible admin sidebar links (stable selector already on
   the markup: `data-test-id="admin-sidebar-link"`; links may appear twice for
   mobile + desktop — count uniquely by href or label).
3. The set of links matches the role→menu mapping exactly:
   - every expected link is present;
   - no unexpected registry link is present.
4. Hardcoded links (Tableau de bord, Retour au site) are present for every
   tested role.

### 4.2 Mapping kept current

1. The mapping is a single, readable source next to the core spec (not scattered
   asserts).
2. When a domain adds, removes, or re-permissions an admin nav page, the
   mapping is updated in the same change — otherwise the core suite fails.
3. The mapping documents which roles see which page keys (or equivalent stable
   identifiers: href / `data-*` / French label as already rendered).

### 4.3 Tech-admin E2E identity

1. A dedicated E2E account with role `tech-admin` exists (seed + Playwright
   auth fixture), mirroring the existing moderator/admin pattern.
2. The tech-admin case runs in the same core spec as the other two roles.

### 4.4 Suite membership

1. Specs live under `e2e/tests/core/` from day one.
2. They run as part of `npm run e2e:core` (and therefore `npm run e2e`).

## 5. Lifecycle

N/A — no persistent product data. The only "lifecycle" is keeping the mapping
aligned when admin navigation registrations change.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Cover moderator, admin, tech-admin only. Non-staff roles are out of scope for this suite. |
| Visibility / privacy | N/A — no new data; asserts existing sidebar visibility. |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A — moderation *pages* appear in the mapping; no moderation behaviour is tested. |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | Asserts against French labels / rendered hrefs already shown; no new copy. |
| Mobile | Sidebar links are duplicated mobile+desktop; tests must dedupe, not require a specific viewport. |
| Accessibility | N/A for this chore (no a11y change). |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | (from request) What to test? | Admin sidebar menus for moderator, admin, tech_admin — presence and exact set. |
| 2 | (from request) Mapping? | Yes — explicit role→menu mapping kept with the tests. |
| 3 | (from request) Suite? | Core regression (`e2e/tests/core/`). |
| 4 | (assumption) Depth of click-through? | Inventory only — do not click every admin page. |
| 5 | (assumption) tech-admin account? | Add missing E2E seeder + Playwright fixture. |
| 6 | (assumption) Source of truth for expected set? | Current `registerPage` permissions in code at REFINE time; mapping freezes that inventory for the suite. |

## 8. Out of scope

- Clicking through or asserting content of every admin page.
- HTTP/middleware authorization tests (PHP feature tests already cover gates).
- Filament `/admin` parity.
- Guests / non-confirmed / confirmed users / authors attempting admin access.
- Changing which roles see which pages in production (this task only locks the
  current mapping in E2E).
- Updating `Administration/README.md` role table (nice-to-have leftover; not
  required for green core).

## 9. Open questions

None blocking. Non-blocking: whether DESIGN prefers asserting by page key /
href / French label — left to architecture.
