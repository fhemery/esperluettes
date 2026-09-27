# Config Domain — Agent Instructions

- README: [app/Domains/Config/README.md](README.md)

## Public API

- [ConfigPublicApi](Public/Api/ConfigPublicApi.php) — feature toggle declaration, query, state update and orphan deletion; configuration parameter registration and value retrieval

## Console

- `config:toggles {--json}` — read-only report of every declared toggle and every orphan row (`declared: false`). Needs no user. Use it to inspect production state.

## Events emitted

| Event | When |
|-------|------|
| `Config.FeatureToggleUpdated` | A declared toggle's access or roles are changed (first change creates the row) |
| `Config.FeatureToggleDeleted` | An orphan row is deleted |
| `Config.ConfigParameterUpdated` | A parameter override is set or reset to default |

`Config.FeatureToggleAdded` is no longer emitted — toggles are declared in code, not created. It stays registered only so stored events in the audit log still deserialize. Do not remove it, do not emit it.

## Listens to

None. This domain emits events but does not subscribe to any.

## Non-obvious invariants

**Parameter definitions are in-memory; only overrides reach the database.** `registerParameter()` must be called from the owning domain's `ServiceProvider::boot()` every request. If a domain registers a parameter but `getParameterValue()` returns `null`, the registration call is missing or running after `ConfigServiceProvider` has already been booted.

**Toggle and parameter keys are case-insensitive.** Lookup always normalizes to `strtolower()`. Register and query with consistent casing anyway to avoid confusion, but mismatches will not silently break.

**A feature toggle must be declared before it is checked.** `isToggleEnabled()` and `updateFeatureToggle()` throw `UndeclaredFeatureToggleException` for a toggle no `registerFeatureToggle()` call declared. Declare it in the owning domain's `ServiceProvider::boot()`; in tests, declare it (or use `createFeatureToggle()`, which does) after clearing the registry. A declared toggle without a row reads as `OFF`.

**Visibility and permission come from the declaration, never from the row.** The table has no `admin_visibility` column. Updating a toggle requires `TECH_ADMIN`, or `ADMIN` when the declaration says `ALL_ADMINS`. Deleting is for orphan rows only and requires `TECH_ADMIN`; deleting a declared toggle throws. This is enforced in the service layer, not at the route level. Do not assume a route middleware check is sufficient.

**Orphan rows are never deleted automatically.** A row no declaration matches is ignored by `isToggleEnabled()`, reported by `config:toggles` and listed to tech admins in the admin page's « Non déclarés dans le code » section, where they delete it by hand. Removing a toggle from code therefore means: remove its `registerFeatureToggle()` declaration and its checks, deploy, then delete the row in the admin UI.

**Both caches must be invalidated on mutation.** Feature toggles use key `feature_toggles:all`; parameter overrides use `config_parameters:values`. Both services call `Cache::forget()` immediately after a write. Any new mutation path must do the same or queries will return stale data for up to 60 minutes.

**`updated_by` has no FK to `users`.** Cross-domain FK to the `users` table is prohibited by architecture. Do not add one.

## Declaring a feature toggle (for agents working in other domains)

In the owning domain's `ServiceProvider::boot()`:

```php
app(ConfigPublicApi::class)->registerFeatureToggle(new FeatureToggleDefinition(
    domain: 'my_domain',
    name: 'my_toggle',
    adminVisibility: FeatureToggleAdminVisibility::ALL_ADMINS, // default: TECH_ADMINS_ONLY
));
```

Then check it with `ConfigPublicApi::isToggleEnabled('my_toggle', 'my_domain')`.

## Registering a configuration parameter (for agents working in other domains)

In the owning domain's `ServiceProvider::boot()`:

```php
app(ConfigPublicApi::class)->registerParameter(new ConfigParameterDefinition(
    domain: 'my_domain',
    key: 'my_key',
    type: ParameterType::INT,
    default: 42,
    constraints: ['min' => 1, 'max' => 100],
    visibility: ConfigParameterVisibility::ALL_ADMINS,
));
```

Add translation keys `my_domain::config.params.my_key.name` and `my_domain::config.params.my_key.description` in the owning domain's lang files, or the admin panel will display raw keys.
