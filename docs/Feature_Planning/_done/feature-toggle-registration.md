# Feature toggles declared in service providers

**Status:** DONE — 2026-09-26 · **Domain(s):** `Config` (+ one declaration in
`Shared`) · mode `auto`

## What it does

A feature toggle now exists only because a domain **declares** it in its
service provider (`ConfigPublicApi::registerFeatureToggle(new FeatureToggleDefinition(domain, name, adminVisibility))`),
like config parameters. The `config_feature_toggles` row only stores state
(access, roles, `updated_at`). Checking an undeclared toggle throws. A read-only
`artisan config:toggles [--json]` reports every declaration and every orphan
row, and the `cleanup-feature-flags` skill now works from declarations + that
command. The only toggle in the code is `shared/dark_theme` (tech admins only).

## Key behaviour

- `isToggleEnabled()` / `updateFeatureToggle()` on an undeclared toggle →
  `UndeclaredFeatureToggleException` (a `LogicException`), **in every environment**.
- Declared, no row → `OFF`. The first access change creates the row (lowercased
  domain/name). No per-declaration default: a new toggle ships OFF.
- Visibility/permission come from the declaration, never the row:
  `TECH_ADMINS_ONLY` (default) → tech admin; `ALL_ADMINS` → admin or tech admin.
  The `admin_visibility` column is dropped.
- Orphan row = row with no declaration: ignored by checks, shown to tech admins
  only in « Non déclarés dans le code », delete is its only action. Declared
  toggles cannot be deleted (`DomainException` → error flash). Nothing is
  deleted automatically; a re-declared toggle picks its old row back up.
- No create form, no description column/lookup; `FeatureToggleAdded` is no
  longer emitted but stays registered so stored events deserialize.
- Declaring twice → last wins. Domain/name case-insensitive; listing and the
  report show the declared casing, orphans the row's casing.
- `setAccess` keeps current roles; the tech-admin edit form **replaces** roles
  (no `roles` field → `[]`).
- `config:toggles`: no auth, no visibility filter; one merged sort on
  domain/name; row-less declaration → `off`, `[]`, `updated_at: null`.
- Registry is a static array: it survives across tests in one process. Config
  toggle tests call `clearFeatureToggleDefinitions()` in `beforeEach` and use
  file-unique names.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Config/Public/Api/ConfigPublicApi.php` (`registerFeatureToggle`, `isToggleEnabled`, `updateFeatureToggle(..., ?array $roles)`, `deleteFeatureToggle`, `listFeatureToggles`, `listOrphanFeatureToggles`) |
| Declaration / exception | `Config/Public/Contracts/FeatureToggleDefinition.php`, `Config/Public/Exceptions/UndeclaredFeatureToggleException.php` |
| Service (registry, merge, `report()`) | `Config/Public/Services/FeatureToggleService.php` |
| Controller / routes | `Config/Private/Controllers/Admin/FeatureToggleController.php`, `Config/Private/routes.php` — all toggle routes are `/admin/config/feature-toggles/{domain}/{name}[...]` |
| Views / lang | `Config/Private/Resources/views/pages/admin/feature-toggles/{index,edit}.blade.php`, `lang/fr/admin.php` |
| Console | `Config/Private/Console/ListFeatureTogglesCommand.php` (registered in `ConfigServiceProvider::register()`) |
| Migration | `Config/Database/Migrations/2026_09_26_120000_drop_admin_visibility_from_config_feature_toggles.php` |
| Declaration of `shared/dark_theme` | `Shared/Providers/SharedServiceProvider.php` (before its boot-time check) |
| Tests | `Config/Tests/Feature/{IsToggleEnabled,UpdateFeatureToggle,DeleteFeatureToggle,ListFeatureToggles,ListFeatureTogglesCommand,FeatureToggleRegistration}Test.php`, `Admin/FeatureToggleControllerTest.php`; helpers `declareFeatureToggle()`, `clearFeatureToggleDefinitions()`, `createFeatureToggle()` (declares + sets state) in `Config/Tests/helpers.php` |
| Skill | `.agents/skills/cleanup-feature-flags/SKILL.md` |

## Extension points used

- Config's own declaration registry (new) — the extension point other domains
  now use to add a toggle. No other registry touched.

## Plan vs code

- Phase 3 said `destroy` keeps model binding; it is `DELETE .../{domain}/{name}`
  — the orphan DTO has no id (A23). `GET .../feature-toggles/create` is a 404.
- Phase 2 said `updateOrCreate`; the code resolves an existing row through the
  cache case-insensitively, then updates it or creates a lowercased one, so a
  legacy mixed-case row is not duplicated (A20).
- Phase 4 said "declarations then orphans, sorted"; `report()` does one merged
  sort (A27).
- Edit loads the toggle via `listFeatureToggles()` (404 if undeclared), not a
  new API method (A24).

## Decisions worth remembering

- Production state for the skill = pasted output of `config:toggles --json` (#1).
- Orphan rows deleted by hand in the admin UI after deploy, no migration (#2).
- Toggles only; config parameters (incl. Story's `FeatureToggles` class, which
  holds *parameters*) untouched (#3). Toggles carry no French strings (#5).
- Removing a toggle: replace checks, **then** delete the declaration (so a
  leftover check throws in the gate), deploy, delete the row (A30).

### Assumptions the user is most likely to reverse (full list: A1–A31, made in `auto`)

- **A1** undeclared check throws in production too, not "log and return false".
  A missed declaration in a `boot()` check takes every request down.
- **A2** no per-declaration default: every new toggle starts OFF until an admin acts.
- **A3/A4** visibility is code-only (not editable in the UI); create form removed.
- **A5** orphans visible to tech admins only, delete-only.
- **A11** `admin_visibility` column dropped (`down()` re-adds it, default `tech_admins_only`).
- **A24** edit form with no roles submitted clears roles.
- **A8** `shared/dark_theme` declared tech-admins-only.

## Not done

- **Non-goals (spec §8):** config parameters; automatic orphan deletion;
  changing what `shared/dark_theme` gates or its boot-time evaluation; reader
  experience; the release-note mechanism (`release-notes/`, still in the backlog).
- **Cut mid-build:** nothing.
- **Open → backlog:**
  [`dark-theme-role-based/`](../dark-theme-role-based/00-request.md) —
  `shared/dark_theme` is checked at boot with no user, so `role_based` acts as
  `off` (spec §9);
  [`admin-double-flash`](./admin-double-flash.md) — admin layout and
  ~23 admin pages both render `<x-shared::flash-block />`, so flashes show twice
  (pre-existing, seen at VERIFY).
- **e2e:** `e2e/tests/features/feature-toggle-registration.spec.ts` **deleted**
  at WRAP (PHP tests cover listing, permissions, orphans, command); its page
  object `AdminFeatureTogglesPage`, the `FEATURE_TOGGLES` fixture and
  `E2eFeatureTogglesSeeder` deleted with it. Kept: the `cache:clear` in
  `e2e/support/global-setup.ts` (the file cache outlived the DB reset).
- **375px:** action buttons reached by scrolling the table container — the
  existing admin-table pattern, accepted (A31).
- Production follow-up: after deploy, check `config:toggles` and delete orphan
  rows by hand.
