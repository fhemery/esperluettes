# Feature toggles declared in service providers — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Declaration registry, undeclared check throws, `shared/dark_theme` declared | S | — | DONE |
| 2 | Toggle state from declarations (service + public API) | M | 1 | DONE |
| 3 | Admin page on declarations + orphans; drop `admin_visibility`; remove create/add/edit | M | 2 | DONE |
| 4 | `config:toggles` command, cleanup skill, Config docs | S | 2 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

### Facts every phase can rely on (checked at PLAN, 2026-09-26)

- Toggle code: `app/Domains/Config/Public/Services/FeatureToggleService.php`,
  `Public/Api/ConfigPublicApi.php`, `Private/Repositories/FeatureToggleRepository.php`,
  `Private/Models/FeatureToggle.php`, `Private/Controllers/Admin/FeatureToggleController.php`,
  `Private/routes.php`, views in `Private/Resources/views/pages/admin/feature-toggles/`.
- Precedent for the static registry: `ConfigParameterService::$definitions`,
  `registerParameter()`, `clearDefinitions()` (same folder).
- Test helpers: `app/Domains/Config/Tests/helpers.php` —
  `createFeatureToggle(TestCase $t, FeatureToggle $toggle): FeatureToggle`
  (acts as tech admin, then creates the row), `checkToggleState()`,
  `clearParameterDefinitions()`. Loaded globally from `tests/Pest.php`.
- The only production toggle check is `SharedServiceProvider::boot()` (~line 164),
  `isToggleEnabled(FeatureToggles::DARK_THEME, FeatureToggles::DOMAIN)`, constants in
  `app/Domains/Shared/Support/FeatureToggles.php`. Shared's test helper
  `enableDarkThemeSettingForTesting()` (`Shared/Tests/helpers.php`) calls
  `createFeatureToggle(...)` with the `FeatureToggle` DTO.
- `Story/Private/Support/FeatureToggles.php` holds **config parameters**, not
  toggles. Never touch it.
- The static registry survives across tests in one PHP process (the app is
  rebuilt per test, statics are not). Every Config toggle test file clears it in
  `beforeEach`, and tests use toggle names unique to their file where a leak
  could matter.

---

## Phase 1 — Declaration registry, undeclared check throws, `shared/dark_theme` declared

**Goal.** Toggles can be declared from a service provider; checking an
undeclared toggle throws; Shared declares its only toggle so nothing breaks.

Architecture: §3.1 (`FeatureToggleDefinition`, `registerFeatureToggle`,
`isToggleEnabled`, `UndeclaredFeatureToggleException`), §3.2 (static registry),
§1.1 (Shared), §9 (boot order).

Nothing else changes in this phase: rows are still created by
`addFeatureToggle`, the admin page is untouched, `admin_visibility` column stays.

**Deliverables.**
- `app/Domains/Config/Public/Contracts/FeatureToggleDefinition.php` — readonly:
  `domain`, `name`, `adminVisibility = FeatureToggleAdminVisibility::TECH_ADMINS_ONLY`.
- `app/Domains/Config/Public/Exceptions/UndeclaredFeatureToggleException.php` —
  extends `LogicException` (a programming error); message names `domain/name`.
- `FeatureToggleService`:
  - `private static array $definitions` keyed `[lower(domain)][lower(name)]`;
  - `registerFeatureToggle(FeatureToggleDefinition $d): void` (last wins);
  - `getDefinition(string $name, ?string $domain = 'config'): ?FeatureToggleDefinition`
    (public — phases 2–4 use it);
  - `public static function clearDefinitions(): void`;
  - `isToggleEnabled()` throws `UndeclaredFeatureToggleException` when no
    definition; a declared toggle with no row (or storage not ready) → `false`;
    otherwise unchanged.
- `ConfigPublicApi::registerFeatureToggle(FeatureToggleDefinition): void`.
- `SharedServiceProvider::boot()` — declare
  `new FeatureToggleDefinition(FeatureToggles::DOMAIN, FeatureToggles::DARK_THEME)`
  (tech admins only, A8) **before** the existing `isToggleEnabled` call.
- `Config/Tests/helpers.php`:
  - `createFeatureToggle()` keeps its signature; it first declares
    `new FeatureToggleDefinition($t->domain, $t->name, $t->admin_visibility)`, then
    creates the row as today;
  - new `declareFeatureToggle(string $name, string $domain = 'config', FeatureToggleAdminVisibility $v = TECH_ADMINS_ONLY): void`;
  - new `clearFeatureToggleDefinitions(): void`.
- Config toggle test files (`IsToggleEnabledTest`, `AddFeatureToggleTest`,
  `UpdateFeatureToggleTest`, `DeleteFeatureToggleTest`, `ListFeatureTogglesTest`,
  `Admin/FeatureToggleControllerTest`): `beforeEach(fn () => clearFeatureToggleDefinitions())`.
  `AddFeatureToggleTest` "proceeds normally" declares the toggle before calling
  `checkToggleState`.
- Remove the dead `moderation/reporting` toggle setups (no code checks that
  toggle since the first cleanup): the `beforeEach` `createFeatureToggle(...)`
  blocks and now-unused imports in
  `Story/Tests/Feature/Chapters/ViewChapterTest.php` (~l.161),
  `Story/Tests/Feature/Stories/StoryShowTest.php` (~l.257),
  `Comment/Tests/Feature/CommentFragmentControllerTest.php` (~l.309),
  `Profile/Tests/Feature/ProfileShowTest.php` (~l.104). Those tests must still pass unchanged otherwise.

**Tests.** (`app/Domains/Config/Tests/Feature/IsToggleEnabledTest.php`, rewritten)
- `it('throws when the toggle is not declared')`
- `it('throws when the toggle is declared in another domain only')`
- `it('returns false for a declared toggle without a row')`
- `it('returns true for a declared toggle with ON access')`
- `it('returns false for a declared toggle with OFF access')`
- `it('is case-insensitive on domain and name')`
- `it('returns true / false for ROLE_BASED depending on the user's roles')` (keep the two existing cases)
- `it('keeps the last declaration when a toggle is declared twice')`

New `app/Domains/Config/Tests/Feature/FeatureToggleRegistrationTest.php`:
- `it('declares shared/dark_theme at boot')` — without clearing, `isToggleEnabled('dark_theme', 'shared')` returns `false` and does not throw.

Existing `Shared/Tests/Feature/ThemePreferenceTest.php` must stay green unchanged.

**Acceptance.**
- ✅ `isToggleEnabled('nope')` throws `UndeclaredFeatureToggleException` in tests.
- ✅ A declared toggle with no row reads `false`.
- ✅ App boots and `ThemePreferenceTest` passes (dark theme declared before it is checked).
- ✅ No test in the repo references `'reporting', 'moderation'` any more.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Toggle state from declarations (service + public API)

**Goal.** `FeatureToggleService` / `ConfigPublicApi` treat declarations as the
list of toggles: listing merges declarations with rows, state changes create the
row, orphans can be listed and are the only deletable rows.

Architecture: §2.3, §3.1 (table of methods), §3.2, §3.3, §3.4.

Built on phase 1: `FeatureToggleService` has a static declaration registry
(`registerFeatureToggle`, `getDefinition`, `clearDefinitions`),
`isToggleEnabled` throws `UndeclaredFeatureToggleException`, and test helpers
`declareFeatureToggle()` / `clearFeatureToggleDefinitions()` exist. The admin
controller and the `admin_visibility` column are untouched here (phase 3):
`addFeatureToggle` / `editFeatureToggle` stay for now because the controller
still calls them.

**Deliverables.**
- `FeatureToggleService`:
  - `updateFeatureToggle(string $name, FeatureToggleAccess $access, ?string $domain = 'config', ?array $roles = null)`:
    throws `UndeclaredFeatureToggleException` if not declared; permission from
    the **declaration's** visibility (ALL_ADMINS → admin or tech admin; else tech
    admin); upserts the row on lowercased `domain`/`name` (`updateOrCreate`);
    `$roles === null` keeps current roles (`[]` for a new row). While the column
    still exists, write `admin_visibility` from the declaration. Forget cache,
    emit `FeatureToggleUpdated` (snapshot visibility from the declaration).
  - `deleteFeatureToggle()`: tech admin only (check first, as today); if the
    toggle is **declared**, throw `DomainException` ("declared toggles cannot be
    deleted"); if no row, no-op; else delete, forget cache, emit
    `FeatureToggleDeleted` (snapshot visibility `TECH_ADMINS_ONLY`).
  - `listFeatureToggles()`: one `FeatureToggle` DTO per **declaration**, merged
    with its row (no row → `OFF`, `[]`), visibility from the declaration,
    filtered as today (tech admin all, admin ALL_ADMINS only, others `[]`),
    sorted by domain then name.
  - `listOrphanFeatureToggles(): array<FeatureToggle>`: rows with no
    declaration, `admin_visibility` = `TECH_ADMINS_ONLY`; `[]` unless tech admin.
  - Visibility is never read from the row any more (`getAllCached` may keep
    selecting it until phase 3 drops it).
- `ConfigPublicApi`: `updateFeatureToggle(..., ?array $roles = null)`,
  new `listOrphanFeatureToggles()`.
- `Config/Tests/helpers.php` — `createFeatureToggle()` becomes "declare
  (visibility from the DTO) + `updateFeatureToggle($name, $access, $domain, $roles)`
  as tech admin". Same signature, so Shared's helper is unchanged.
- `Admin/FeatureToggleControllerTest.php` — the `destroy` "deletes a toggle as
  tech-admin" case now turns its toggle into an orphan
  (`clearFeatureToggleDefinitions()`) before deleting, since declared toggles
  can no longer be deleted. The controller itself is unchanged here (phase 3
  handles the declared-toggle refusal in the UI).

**Tests.**
`UpdateFeatureToggleTest.php` (rewritten):
- `it('creates the row on the first change of a declared toggle')`
- `it('throws when the toggle is not declared')`
- `it('sets roles when given and keeps them when roles is null')`
- `it('lets an admin update an all_admins toggle')`
- `it('forbids an admin from updating a tech_admins_only toggle')` — visibility comes from the declaration
- `it('forbids a confirmed user')`
- `it('emits FeatureToggleUpdated, including on row creation')`

`DeleteFeatureToggleTest.php` (rewritten):
- `it('deletes an orphan row as tech admin and emits FeatureToggleDeleted')`
- `it('refuses to delete a declared toggle')`
- `it('forbids an admin from deleting an orphan')`

`ListFeatureTogglesTest.php` (rewritten):
- `it('lists a declared toggle without a row as OFF')`
- `it('does not list a row without declaration')`
- `it('shows admins only all_admins declarations, tech admins all')`
- `it('returns nothing to a non-admin')`
- `it('lists orphan rows to tech admins only')` (`listOrphanFeatureToggles`)

Orphan rows in tests: create via the helper, then `clearFeatureToggleDefinitions()`.

**Acceptance.**
- ✅ First `updateFeatureToggle` on a declared, row-less toggle creates one row and `isToggleEnabled` follows it.
- ✅ An admin gets `AuthorizationException` updating a `TECH_ADMINS_ONLY` declaration, even if the row says `all_admins`.
- ✅ `deleteFeatureToggle` on a declared toggle throws and deletes nothing.
- ✅ Admin page still works unchanged (existing `Admin/FeatureToggleControllerTest` green).
- ✅ `pnpm run gate` green.

---

## Phase 3 — Admin page on declarations + orphans; drop `admin_visibility`; remove create/add/edit

**Goal.** The admin page lists declared toggles (row or not) and, for tech
admins, orphan rows with delete only; no toggle can be created by hand;
visibility leaves the table.

Architecture: §2.1, §2.2, §3.1 ("Removed"), §3.4, §3.5 (routes), §4.

Built on phases 1–2: `ConfigPublicApi` offers `registerFeatureToggle`,
`listFeatureToggles()` (declarations merged with rows, filtered by viewer),
`listOrphanFeatureToggles()` (tech admin only), `updateFeatureToggle($name,
$access, $domain, ?array $roles)` (creates the row, enforces admin/tech-admin
from the declaration), `deleteFeatureToggle()` (orphans only, tech admin).

**Deliverables.**
- Migration `app/Domains/Config/Database/Migrations/<YYYY_MM_DD_HHiiss>_drop_admin_visibility_from_config_feature_toggles.php`:
  drop index `admin_visibility` then the column; `down()` re-adds
  `string('admin_visibility')->default('tech_admins_only')` + index.
- `Private/Models/FeatureToggle.php` — remove `admin_visibility` from `#[Fillable]`.
- `FeatureToggleService` — remove `addFeatureToggle`, `editFeatureToggle`,
  `assertNewToggleIsValid`, every read/write of `admin_visibility` on rows.
  `FeatureToggleAdded` no longer emitted; its registration in
  `ConfigServiceProvider` **stays** (A14).
- `ConfigPublicApi` — remove `addFeatureToggle`, `editFeatureToggle`.
- `Private/routes.php`:
  - remove `create` and `store`;
  - `setAccess`, `edit`, `update` become `/feature-toggles/{domain}/{name}/set-access`
    (POST), `/feature-toggles/{domain}/{name}/edit` (GET),
    `/feature-toggles/{domain}/{name}` (PUT) — a row-less declared toggle has no
    id to bind (same shape as the parameters routes);
  - `destroy` keeps `DELETE /feature-toggles/{featureToggle}` model binding (an orphan always has a row).
- `FeatureToggleController`:
  - `index()` — `$toggles = $api->listFeatureToggles()`, `$orphans = $api->listOrphanFeatureToggles()`; no direct model query;
  - `setAccess(Request, string $domain, string $name)` → `updateFeatureToggle($name, $access, $domain)`;
    translate `UndeclaredFeatureToggleException` to 404;
  - `edit(string $domain, string $name)` / `update(...)` — tech admin only (as
    today); 404 if undeclared; validate `access` + `roles` only; call
    `updateFeatureToggle($name, $access, $domain, $roles)`;
  - `destroy(FeatureToggle $featureToggle)` — tech admin; a `DomainException` from the API → redirect back with an error flash;
  - remove `create()`, `store()`.
  - Validation stays inline `$request->validate` as today (existing style in this controller).
- Views: delete `create.blade.php`; `index.blade.php` — drop the create button,
  description and visibility columns, route params `domain`/`name`, add a
  tech-admin-only section « Non déclarés dans le code » listing orphans (domain,
  name, access, delete button only), hidden when empty; `edit.blade.php` —
  access + roles only.
- `Private/Resources/lang/fr/admin.php` (`feature_toggles` block): add the
  orphan section title/label and the "declared cannot be deleted" error; delete
  keys only used by the removed create form / visibility / description fields
  (grep each before deleting).

**Tests.** `Admin/FeatureToggleControllerTest.php` (rewritten; `create`/`store` blocks deleted), `AddFeatureToggleTest.php` deleted:
- index: `it('shows a declared toggle without a row as OFF')`,
  `it('hides tech_admins_only declarations from regular admins')`,
  `it('shows orphan rows with « Non déclaré dans le code » to tech admins')`,
  `it('does not show orphan rows to regular admins')`,
  keep the unauthenticated / non-admin cases.
- `it('has no create route any more')` — `GET /admin/config/feature-toggles/create` is not 200 (404 or matched as a non-existent domain/name — assert `assertNotFound()`).
- setAccess: `it('creates the row when an admin sets access on a row-less all_admins toggle')`,
  `it('returns 403 to an admin on a tech_admins_only toggle')`,
  `it('returns 404 for an undeclared toggle')`, validation case kept.
- edit/update: `it('returns 403 for regular admin')`, `it('updates access and roles as tech admin')`, `it('returns 404 for an undeclared toggle')`.
- destroy: `it('deletes an orphan as tech admin')`, `it('refuses to delete a declared toggle')`, `it('returns 403 for regular admin')`.
- Migration: `it('no longer has an admin_visibility column')` via `Schema::hasColumn`.

**Acceptance.**
- ✅ Tech admin sees `shared/dark_theme` as `OFF` on a fresh DB, and an orphan row in its own section with only a delete button.
- ✅ Admin sees neither `shared/dark_theme` nor the orphan section.
- ✅ Admin POSTing set-access on a `tech_admins_only` toggle gets 403; row unchanged.
- ✅ `DELETE` on a declared toggle's row leaves it in place.
- ✅ `grep -rn "addFeatureToggle\|editFeatureToggle\|admin_visibility" app/Domains --include=*.php` hits only the old create migration, the new drop migration, the DTO/snapshot fields and the enum.
- ✅ `pnpm run gate` green.

---

## Phase 4 — `config:toggles` command, cleanup skill, Config docs

**Goal.** A read-only artisan command reports every declared toggle and every
orphan row; the cleanup skill and Config's docs use the declarations and the
command.

Architecture: §3.2 (report method), §3.5 (console), §1.1 (skill), functional §4.4 and §4.6.

Built on phase 2: `FeatureToggleService` has a static declaration registry
(`getDefinition`, `clearDefinitions`) and reads rows through `getAllCached()`
(returns empty rows when `ConfigStorageReadiness` says storage is not ready).
Phase 3 is not required — the command never reads `admin_visibility`.

**Deliverables.**
- `FeatureToggleService::report(): array` — no auth, no visibility filter; one
  entry per declaration then one per orphan row, sorted by domain, name:
  `['domain', 'name', 'declared' => bool, 'access' => 'on'|'off'|'role_based',
  'roles' => list<string>, 'updated_at' => ?string]` (ISO-8601, `null` without a
  row; row-less declaration → `access` `off`, `roles` `[]`). Rows need
  `updated_at`: add it to the cached row array in `getAllCached()`. Not on
  `ConfigPublicApi` (A15).
- `app/Domains/Config/Private/Console/ListFeatureTogglesCommand.php` — signature
  `config:toggles {--json}`; `--json` prints `json_encode($report, JSON_PRETTY_PRINT)`;
  otherwise `$this->table()` with the same columns (roles comma-joined, booleans as yes/no).
  Returns `self::SUCCESS`.
- `ConfigServiceProvider::register()` — `$this->commands([ListFeatureTogglesCommand::class])`
  (precedent: `NotificationServiceProvider`, ~l.30).
- `.agents/skills/cleanup-feature-flags/SKILL.md`:
  - "What counts as a feature toggle": declared with `registerFeatureToggle` in a
    service provider; checking an undeclared one throws; rows only store state.
  - Step 1: inventory = `grep -rn -A4 "registerFeatureToggle\|new FeatureToggleDefinition" app --include=*.php`,
    then grep each declared toggle's `isToggleEnabled` uses to tell used from unused;
    removal also deletes the declaration. Drop the description-string bullet.
  - Step 2: ask the user to run `php artisan config:toggles --json` on production
    and paste the output; orphans are the entries with `"declared": false`.
  - Bucket "in prod, unused" → split: declared but unused (remove declaration +
    uses), and orphan (delete the row in the admin UI after deploy, no code).
- `app/Domains/Config/README.md` and `AGENTS.md` — feature-toggle sections:
  declaration in service providers, undeclared check throws, visibility from the
  declaration, orphans + manual deletion, `config:toggles`, `FeatureToggleAdded`
  kept only for stored events; fix the stale "Feature toggles have no dedicated
  page" sentence. No link to `docs/Feature_Planning`.

**Tests.** New `app/Domains/Config/Tests/Feature/ListFeatureTogglesCommandTest.php`
(`beforeEach` clears declarations):
- `it('prints declared toggles with and without a row and orphan rows as JSON')` — asserts the exact entries: declared+row (`declared: true`, access, roles, ISO `updated_at`), declared without row (`off`, `[]`, `null`), orphan (`declared: false`).
- `it('prints a table without --json')` — exit code 0, output contains the toggle names.
- `it('changes nothing')` — row count and values identical before/after.
- `it('needs no authenticated user')` — run without `actingAs`, succeeds.

**Acceptance.**
- ✅ `./vendor/bin/sail artisan config:toggles --json` on a dev DB lists `shared/dark_theme` with `"declared": true`.
- ✅ The skill no longer asks for a paste of the admin page nor greps `isToggleEnabled` as the inventory source.
- ✅ Config README/AGENTS describe declarations and the command; docs step of the gate green.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Admin → Feature toggles, as tech admin, fresh DB | `shared/dark_theme` listed under its domain as `OFF`; no create button; no description / visibility column | |
| Same, as tech admin, after inserting an orphan row (e.g. `moderation/reporting`) | Section « Non déclarés dans le code » shows it with a delete button only; deleting it removes it and flashes success | |
| Same, as regular admin | `shared/dark_theme` not shown (tech admins only); orphan section absent; empty state readable, not a broken table | |
| Set access on row-less `shared/dark_theme` (tech admin) | Switch to `ON` sticks after reload; Settings → Général shows the Apparence option on a new request | |
| Edit page (tech admin) | Only access + roles fields; `role_based` + a role saves and shows the role on the index | |
| Mobile width (375px), tech admin | Index and orphan section stay usable (no horizontal overflow of action buttons) | |
| `sail artisan config:toggles` and `--json` | Table readable; JSON matches the index (declared + orphan) | |

## Open items

- **Phase 3** — `GET /admin/config/feature-toggles/create` after the route
  change: with `{domain}/{name}` routes it matches nothing (two segments needed
  for edit, three with `/edit`), so 404 is expected. BUILD confirms with the test
  rather than assuming.
- **Phase 1** — removing the dead `moderation/reporting` test setups in Story,
  Comment and Profile tests is a leftover of the first cleanup, folded here
  because the helper contract changes. If any of those tests fails without the
  setup, it means some code still depends on the toggle: stop and report instead
  of restoring the setup.
- **Phases 1–4, test isolation** — the static registry is cleared only by Config
  test files. A toggle declared in one test can make a later "undeclared throws"
  assertion in the same process pass by accident only if names collide; Config
  tests use file-unique names. If flakiness appears under `test:parallel`,
  consider clearing in `ConfigServiceProvider::register()` (runs before any
  `boot()`) — a design change to surface, not to take silently.
- **Out of scope, noted** — `shared/dark_theme` is evaluated at boot with no
  user, so `role_based` behaves as `off` (functional §9). Not fixed.
