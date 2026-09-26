# Feature toggles declared in service providers — architecture

> DESIGN output. Describes **how** the feature is built. Written in `auto` mode:
> every tradeoff took the recommended option, recorded in §7 and in
> `DECISIONS.md` → assumptions.
>
> Scope: **shape and contracts, not a change list.**

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Entirely in **Config**, which already owns toggles (table, service, admin UI,
`ConfigPublicApi`). The declaration mechanism copies Config's own precedent,
config parameters (`ConfigParameterDefinition` + `registerParameter` + a static
definition registry in `ConfigParameterService`), so both halves of Config work
the same way.

### 1.1 Changes in other domains

- **Shared** — declares `shared/dark_theme` in `SharedServiceProvider` through
  `ConfigPublicApi` (direct call, existing edge). Its test helper
  `enableDarkThemeSettingForTesting()` follows the new test helper contract.
- **Cleanup skill** (`.agents/skills/cleanup-feature-flags/SKILL.md`) — steps 1
  and 2 switch to the declarations and the command (functional §4.6).

No other domain checks a toggle any more.

## 2. Data model

### 2.1 Tables

`config_feature_toggles` keeps its shape except **`admin_visibility` is
dropped** (and its index): visibility now lives in the declaration. Migration
with a `down()` that re-adds it (`string`, default `tech_admins_only`, indexed).
`unique(domain, name)` stays — it is the join key between a declaration and its
row. `updated_at` is the "last change date" of the spec.

### 2.2 Model

`FeatureToggle` model loses `admin_visibility` from `#[Fillable]`. Nothing else.

### 2.3 Lifecycle rules

- A row is created on the first state change of a declared toggle (upsert on
  `domain` + `name`, lowercased like today).
- A row whose `(domain, name)` matches no declaration is an **orphan**. It is
  only ever deleted by an explicit tech-admin action.

## 3. PHP architecture

### 3.1 Public API

New contract, `Config/Public/Contracts/FeatureToggleDefinition` (readonly):

```php
new FeatureToggleDefinition(
    domain: string,
    name: string,
    adminVisibility: FeatureToggleAdminVisibility = TECH_ADMINS_ONLY,
);
```

`ConfigPublicApi` becomes:

| Method | Contract |
|--------|----------|
| `registerFeatureToggle(FeatureToggleDefinition): void` | **new** — declare; last declaration wins |
| `isToggleEnabled(string $name, ?string $domain = 'config'): bool` | unchanged signature; throws `UndeclaredFeatureToggleException` (new, `Config/Public/Exceptions/`) when not declared |
| `updateFeatureToggle(string $name, FeatureToggleAccess $access, ?string $domain = 'config', ?array $roles = null): void` | sets the state, **creates the row if missing**; `$roles = null` keeps the current roles. Throws `UndeclaredFeatureToggleException` if not declared. Same permission rule as today, visibility read from the declaration |
| `deleteFeatureToggle(string $name, ?string $domain = 'config'): void` | now **only deletes an orphan row**; refuses (domain exception) on a declared toggle; tech admin only as today |
| `listFeatureToggles(): array<FeatureToggle>` | declared toggles merged with their rows (no row → `OFF`, no roles), filtered by the viewer's visibility as today |
| `listOrphanFeatureToggles(): array<FeatureToggle>` | **new** — rows without declaration; tech admin only (empty otherwise) |

**Removed:** `addFeatureToggle` and `editFeatureToggle` (no hand-created
toggles; roles now go through `updateFeatureToggle`). The `FeatureToggle`
contract (state DTO) stays; its `admin_visibility` is filled from the
declaration, or `TECH_ADMINS_ONLY` for an orphan.

### 3.2 Services

`FeatureToggleService` holds the declarations in a **static** array keyed by
lowercased domain/name — same as `ConfigParameterService::$definitions`, same
reason (survives rebinding, refilled at every boot) — with a
`clearDefinitions()` for tests. The row cache (`feature_toggles:all`) and its
invalidation are unchanged; merging declarations with rows happens on read.

A report method used by the command (no auth, no visibility filter) returns
every declared toggle plus every orphan: domain, name, declared, access, roles,
`updated_at`. Kept internal to Config (not on `ConfigPublicApi`).

The admin index stops querying the model directly and goes through the service.

### 3.3 Policy / authorization

Unchanged rules, moved source of truth: admin vs tech admin is still enforced in
the service (as today) and repeated in the controller; the visibility compared
against is the declaration's. Orphan listing and deletion: tech admin only.

### 3.4 Events and listeners

- `FeatureToggleUpdated` on every state change (including the one that creates
  the row); `FeatureToggleDeleted` on orphan deletion.
- `FeatureToggleAdded` is **no longer emitted** but stays registered on the
  event bus so stored events of that name still deserialize.
- `FeatureToggleSnapshot` keeps its shape (`admin_visibility` from the
  declaration). No consumer exists; events only feed the stored-event log.

### 3.5 Routes, controllers, form requests, console

- Removed: `create` / `store` routes and views. `edit` / `update` stay (tech
  admin) but edit only access + roles. `setAccess` stays. `destroy` stays, for
  orphans only. No `PATCH` (existing routes use `PUT`/`POST`/`DELETE`).
- New console command `config:toggles {--json}` in `Config/Private/Console/`,
  registered with `$this->commands([...])` in `ConfigServiceProvider::register()`
  (precedent: `NotificationServiceProvider`). JSON: a list of
  `{domain, name, declared, access, roles, updated_at}`; `updated_at` is ISO-8601
  or `null`. Without `--json`: a console table of the same rows. Must work when
  storage is not ready (empty rows, declarations only), like the service does.

## 4. Frontend architecture

Blade only, in the existing admin feature-toggle pages:

- index: declared toggles (grouped by domain, as today), description column
  removed; a second section "Non déclarés dans le code" for tech admins listing
  orphans with a delete button only;
- edit: access + roles only;
- create view deleted.

French strings added to `config::admin.feature_toggles`; strings of removed
fields deleted.

## 5. Deptrac

No new edge. Shared → Config Public already exists.

## 6. Testing strategy

Integration (feature) tests, rewriting Config's toggle tests around the new
contract:

- declaring and checking (on/off/role_based, case-insensitive, missing row →
  off, undeclared → exception);
- state change creating the row; roles kept when `$roles` is null; permission
  rules with visibility from the declaration;
- orphan listing and deletion (tech admin only; refused on a declared toggle);
- admin controller: index shows declared-without-row and orphans (tech admin
  only), create routes gone, edit/update/setAccess/destroy auth;
- command: JSON shape with a declared toggle with row, one without row, one
  orphan; table output smoke test;
- Shared: dark theme setting registered or not according to the toggle, via the
  updated helper.

Test helper contract: Config's `createFeatureToggle` becomes "declare + set
state"; the static declarations are cleared between tests so a test-declared
toggle cannot leak into the next one.

VERIFY: admin page in the browser as admin and tech admin (orphan section
visibility, access change on a row-less toggle).

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where declarations live | static registry in the service (parameter precedent) · container singleton · DB-seeded rows | static registry | Same as config parameters; no boot-time DB writes |
| 2 | Undeclared check | throw · log + false | throw (A1) | The point of the feature is to catch mistakes; tests fail loudly |
| 3 | `admin_visibility` column | drop · keep and ignore · keep as override of the declaration | drop | One source of truth; `down()` re-adds it |
| 4 | Row creation | lazily on first state change · eagerly at boot for every declaration | lazily | No DB writes at boot; row-less = off is already the runtime rule |
| 5 | Roles editing | fold into `updateFeatureToggle` · keep `editFeatureToggle` | fold | `editFeatureToggle` only differed by visibility, which leaves the UI |
| 6 | `FeatureToggleAdded` | stop emitting, keep registered · delete the class | keep registered | Stored events of that name must still deserialize |
| 7 | Command data access | internal service method · new `ConfigPublicApi` method | internal | Only Config's own command needs it |

## 8. File layout

```
app/Domains/Config/
├── Database/Migrations/<ts>_drop_admin_visibility_from_config_feature_toggles.php
├── Private/Console/ListFeatureTogglesCommand.php
├── Public/Contracts/FeatureToggleDefinition.php
└── Public/Exceptions/UndeclaredFeatureToggleException.php
```

## 9. Risks acknowledged

- **Boot order**: a domain checking another domain's toggle during boot, before
  that domain declared it, would throw. Today the only boot-time check is
  Shared's own toggle, declared just before. Revisit if a cross-domain
  boot-time check appears.
- **Static registry in tests**: must be cleared between tests, or a
  test-declared toggle leaks. Covered by the helper contract (§6).
- **Existing prod rows**: `shared/dark_theme` keeps its row; any other row
  becomes an orphan until deleted by hand — intended (functional §5).
