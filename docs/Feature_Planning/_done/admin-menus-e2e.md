# Admin — menus E2E for moderator / admin / tech_admin

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** Administration, Auth, e2e

## What it does

Permanent Playwright coverage that, for each staff role allowed into
`/administration` (moderator, admin, tech-admin), asserts the sidebar shows
exactly the expected set of links — identified by `data-nav-key`, not French
labels or hrefs. The expected sets live in `e2e/support/admin-nav-map.ts` and
must be updated whenever a domain registers or re-permissions an admin page.
Specs are under `e2e/tests/core/` (`npm run e2e:core`).

## Key behaviour

- **Staff only** — layout already gates moderator / admin / tech-admin; suite
  does not cover guests or confirmed users.
- **Exact set equality** — missing or unexpected keys fail; inventory only, no
  click-through of admin pages.
- **Hardcoded keys** — `dashboard` and `back-to-site` are not in the registry;
  they are invented for the two template links.
- **tech-admin E2E account** — `tech-admin@e2e.test` in `E2eAccountsSeeder`,
  Playwright fixture `tech_admin`.
- **Maintenance page key** is the literal `maintenance` (not a translated
  string), so testing locale `zz` matches production.

## Where the code lives

| Concern | Path |
|---------|------|
| Nav key in registry payload | `Administration/.../AdminNavigationRegistry.php` |
| Sidebar / link markup | `Administration/.../sidebar.blade.php`, `navigation-item.blade.php` |
| Maintenance key registration | `Administration/.../AdministrationServiceProvider.php` |
| PHP nav-key tests | `Administration/Tests/Feature/AdminNavigationNavKeyTest.php` (fake registrations only — no live cross-domain keys) |
| E2E account | `Auth/Database/Seeders/E2eAccountsSeeder.php` |
| Mapping | `e2e/support/admin-nav-map.ts` |
| Page object | `e2e/pages/AdminSidebar.ts` |
| Core spec | `e2e/tests/core/admin-menus.spec.ts` |
| Fixtures | `e2e/support/fixtures.ts`, `e2e/support/test.ts` |

## Extension points used

- **AdminNavigationRegistry** — consumed as-is; keys exposed for E2E, no new API.

## Decisions worth remembering

1. Identify links by `data-nav-key` (= `registerPage` key), not href or FR label.
2. Mapping lives only in TS under `e2e/` — no PHP inventory twin.
3. Assert set equality only; do not click every admin page.
4. Core suite from day one (`e2e/tests/core/`), not a temporary feature spec.

## Not done

- Deliberate non-goals: Filament `/admin` parity, non-staff access asserts,
  clicking every admin page, refreshing outdated Administration README role
  tables beyond the nav-key note.
- No rows pushed back to the backlog.
- On remapped Sail worktrees (host `:8081`), run with
  `E2E_BASE_URL=http://localhost:8081` — Playwright’s default `:8080` is the
  container port, not always the host port.
