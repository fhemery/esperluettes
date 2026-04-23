# Config Domain — Agent Instructions

- README: [app/Domains/Config/README.md](README.md)

## Public API

- [ConfigPublicApi](Public/Api/ConfigPublicApi.php) — feature toggle CRUD and query; configuration parameter registration and value retrieval

## Events emitted

| Event | When |
|-------|------|
| `Config.FeatureToggleAdded` | A new feature toggle is created |
| `Config.FeatureToggleUpdated` | A toggle's access mode is changed |
| `Config.FeatureToggleDeleted` | A toggle is deleted |
| `Config.ConfigParameterUpdated` | A parameter override is set or reset to default |

## Listens to

None. This domain emits events but does not subscribe to any.

## Non-obvious invariants

**Parameter definitions are in-memory; only overrides reach the database.** `registerParameter()` must be called from the owning domain's `ServiceProvider::boot()` every request. If a domain registers a parameter but `getParameterValue()` returns `null`, the registration call is missing or running after `ConfigServiceProvider` has already been booted.

**Toggle and parameter keys are case-insensitive.** Lookup always normalizes to `strtolower()`. Register and query with consistent casing anyway to avoid confusion, but mismatches will not silently break.

**Creating and deleting toggles requires `TECH_ADMIN`; updating requires `ADMIN` or `TECH_ADMIN` depending on the toggle's `admin_visibility`.** This two-level permission model is enforced in the service layer, not at the route level. Do not assume a route middleware check is sufficient.

**Both caches must be invalidated on mutation.** Feature toggles use key `feature_toggles:all`; parameter overrides use `config_parameters:values`. Both services call `Cache::forget()` immediately after a write. Any new mutation path must do the same or queries will return stale data for up to 60 minutes.

**`updated_by` has no FK to `users`.** Cross-domain FK to the `users` table is prohibited by architecture. Do not add one.

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
