# Config Domain

## Purpose and scope

The Config domain owns two distinct but related facilities: **feature toggles** and **configuration parameters**. Both are managed by admins and tech admins through an admin panel page, and both are exposed to all other domains via `ConfigPublicApi`.

Feature toggles control whether a named capability is active, and for whom. Configuration parameters are typed values with defaults that other domains register and can have overridden at runtime without a deployment.

This domain does not own any user-facing pages. It provides infrastructure consumed by every other domain that needs runtime-configurable behaviour.

## Key concepts

### Feature toggles

A feature toggle is **declared in code**: the owning domain calls `ConfigPublicApi::registerFeatureToggle(new FeatureToggleDefinition(domain, name, adminVisibility))` from its `ServiceProvider::boot()`. The declaration is held in memory (`FeatureToggleService::$definitions`), like parameter definitions. Checking an undeclared toggle with `isToggleEnabled()` throws `UndeclaredFeatureToggleException` — a typo or a forgotten declaration fails loudly instead of reading as `false`.

The row in `config_feature_toggles` only stores state. A declared toggle with no row reads as `OFF`; its row is created the first time an admin changes its access. A toggle has three access modes:

- `OFF` — the feature is disabled for everyone.
- `ON` — the feature is enabled for all authenticated and unauthenticated users.
- `ROLE_BASED` — the feature is enabled only for users who hold one of the listed roles.

Who sees and changes a toggle in the admin panel comes from its declaration, never from the row: `TECH_ADMINS_ONLY` (the default) restricts it to tech admins; `ALL_ADMINS` lets regular admins change it too. Toggles cannot be created from the admin panel — declaring one is a code change.

An **orphan** is a row no declaration matches, typically left behind after a toggle was removed from code. Orphans have no effect. Tech admins see them in a separate « Non déclarés dans le code » section of the admin page and delete them by hand there; declared toggles cannot be deleted. Nothing deletes rows automatically.

`php artisan config:toggles` prints, read-only and with no user, every declared toggle and every orphan row (`--json` for machine-readable output, with a `declared` flag per entry). It is how a production state is inspected without the admin panel.

Toggle state is cached for 60 minutes under the key `feature_toggles:all`. Any mutation (update, delete) immediately invalidates this cache.

### Configuration parameters

Parameters are declared at application boot by calling `ConfigPublicApi::registerParameter()` from a domain's `ServiceProvider::boot()`. The definition is stored in-memory (never in the database); only overrides are persisted in `config_parameter_values`.

A parameter definition carries:
- A owning `domain` and a unique `key` within that domain. The combined `domain.key` is the full identifier.
- A `ParameterType` (`INT`, `STRING`, `BOOL`, `ENUM`, `MULTI_SELECT`, `TIME`, `RANGE`) that drives validation, casting, and serialisation.
- A `default` value returned when no database override exists.
- Optional `constraints` (e.g. `min`, `max`, `max_length`, `pattern`) applied during validation.
- A `visibility` level (`TECH_ADMINS_ONLY` or `ALL_ADMINS`) that mirrors the feature-toggle visibility model.

Calling `ConfigPublicApi::getParameterValue()` returns the cast PHP value (from the DB override if one exists, or the default). Returns `null` if the parameter was never registered.

Parameter overrides are cached for 60 minutes under `config_parameters:values`. Any write or reset invalidates this cache.

### Admin panel integration

The domain registers two navigation entries in the Administration domain's `AdminNavigationRegistry`, both visible to `ADMIN` and `TECH_ADMIN`: the parameters page (`config.admin.parameters.index`) and the feature toggles page (`config.admin.feature-toggles.index`). The toggles page lists the declared toggles the current admin may see (`ConfigPublicApi::listFeatureToggles()`), lets them change access and roles, and shows orphan rows to tech admins (`listOrphanFeatureToggles()`).

## Architecture decisions

**Definitions are in-memory, overrides in the database.** Parameter definitions are registered at boot and live only in `ConfigParameterService::$definitions` (a static array). This means the set of available parameters is always determined by the deployed codebase, not by what is in the database. An override row without a matching registered definition is silently ignored. This prevents stale config rows from affecting the application after a feature is removed. Feature toggles follow the same rule: the declaration decides what exists and who may change it, the row only its state.

**Toggle names and domain keys are case-insensitive.** Both services normalize names to `strtolower()` before cache lookups and storage comparisons. This avoids subtle mismatches between registering domains and querying domains.

**No foreign keys to `users`.** The `updated_by` column on both tables stores user IDs but has no FK constraint. This conforms to the project-wide rule prohibiting cross-domain FK constraints to the `users` table.

## Cross-domain delegation map

| Concern | Delegated to |
|---------|-------------|
| Role checks in toggle/parameter access | `Auth::AuthPublicApi` |
| Domain event emission | `Events::EventBus` |
| Admin navigation registration | `Administration::AdminNavigationRegistry` |

Other domains that register parameters or toggles depend on this domain's `ConfigPublicApi`; they call it from their own `ServiceProvider::boot()`.
