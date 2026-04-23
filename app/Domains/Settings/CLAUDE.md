# Settings Domain — Agent Instructions

- README: [app/Domains/Settings/README.md](README.md)

## Public API

- [SettingsPublicApi](Public/Api/SettingsPublicApi.php) — registration (`registerTab`, `registerSection`, `registerParameter`) and value access (`getValue`, `getValueForCurrentUser`, `setValue`, `setValueForCurrentUser`, `resetToDefault`, `resetToDefaultForCurrentUser`) plus registry reads for UI rendering

## Events emitted

This domain emits no events.

## Listens to

This domain registers no event listeners.

## Non-obvious invariants

**Registration must be deferred to `booted`.** Wrap all `registerTab` / `registerSection` / `registerParameter` calls inside `$this->app->booted(fn() => ...)` in the consuming domain's `boot()` method. This guarantees the Settings domain itself is fully loaded before any registration attempt. Calling it directly in `boot()` can fail if providers boot in an unfavourable order.

**Idempotency guard is the caller's responsibility.** The registry throws `InvalidArgumentException` on duplicate IDs. Consuming providers must guard against double-registration (e.g. during tests that re-boot the container) with a `getTab($id) !== null` check before registering.

**Order is mandatory and significant.** Tab must be registered before its sections; sections before their parameters. The registry enforces this and throws if the parent does not exist.

**Key scope is per-tab, not global.** The same `key` string may be reused across different tabs without conflict. Storage uses a `(user_id, domain, key)` unique index where `domain` is the lowercase tab ID.

**Default values are never stored.** When `setValue()` is called with a value equal to the parameter's default (after casting), the stored override is deleted rather than written. This keeps the `settings` table lean and means `isOverridden()` can serve as a reliable "has the user changed this" check.

**Cache key format is `user_settings:{user_id}`.** The cache entry is a flat array keyed as `tabId.key` (both lowercased). Invalidation calls `Cache::forget()` plus clears the in-request `$requestCache`. If you manipulate the `settings` table directly (e.g. in a seeder or migration), call `Cache::forget("user_settings:{$userId}")` manually or user settings will appear stale until cache expiry (which is forever by default).

**No FK to `users`.** The `user_id` column has no foreign key constraint. Cross-domain FK to `users` is prohibited by architecture. Clean up orphaned settings rows by subscribing to `Auth::UserDeleted` in any domain that needs it (the Settings domain itself does not do this; consumers are responsible for their own data lifecycle).

**Role filtering is enforced at the controller level only.** `SettingsService` and `SettingsPublicApi` do not check roles when reading or writing values. Role gating is applied by `SettingsController` for the web UI routes. If another domain calls `setValue()` directly it bypasses role restrictions — only do this intentionally.

**`SettingsRegistryService` uses static properties.** The registry is stored in `static` class properties, making it process-global. In tests, always call `clearSettingsRegistry()` (which calls `SettingsRegistryService::clearAll()`) in `beforeEach` and `afterEach` to prevent cross-test contamination.

## Registry integrations

Other domains that register settings tabs:

| Domain   | Tab ID     | Constants location                                  |
|----------|------------|-----------------------------------------------------|
| Shared   | `general`  | `SharedServiceProvider::TAB_GENERAL`                |
| ReadList | `readlist` | `ReadListServiceProvider::TAB_READLIST`             |
| Profile  | `profile`  | `ProfileServiceProvider::TAB_PROFILE`               |
