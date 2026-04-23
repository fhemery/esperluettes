# Administration Domain — Agent Instructions

- README: [app/Domains/Administration/README.md](README.md)

## Public API

- `AdminNavigationRegistry` (`Public/Contracts/AdminNavigationRegistry.php`) — singleton; call `registerGroup()` and `registerPage()` in a domain's `ServiceProvider::boot()` to add entries to the admin sidebar. Resolve via the container: `app(AdminNavigationRegistry::class)`.
- `AdminRegistryTarget` (`Public/Contracts/AdminRegistryTarget.php`) — value object used as the `$target` argument to `registerPage()`. Use `AdminRegistryTarget::route('route.name')` for named routes and `AdminRegistryTarget::url('/admin/...')` for legacy Filament URLs.
- `LayoutComponent` (`Public/View/LayoutComponent.php`) — registered as the Blade component `<x-admin::layout>`. Use this as the outer shell for any new admin page. It enforces authentication and role checks; do not duplicate those checks in the controller.
- `ExportCsv::streamFromQuery()` (`Public/Support/ExportCsv.php`) — stream a CSV download from an Eloquent builder. Available to all domains; no Administration-specific dependency.

## Non-Obvious Invariants

**Sidebar link parity with Filament is enforced by tests.** `AdminNavigationTest` verifies that the link count in `/administration` matches the count in the Filament `/admin` sidebar for each role. If you add or remove a page registration in any domain's service provider, the corresponding Filament resource must also be added or removed, or these tests will fail.

**The dashboard sidebar link is hardcoded, not registered.** Do not attempt to register the dashboard link through `AdminNavigationRegistry` — it is rendered unconditionally at the top of the sidebar template outside the registry loop.

**`AdminNavigationRegistry` must be cleared between tests** that manipulate it directly. The singleton persists across test cases in the same process. Call `$registry->clear()` in a `beforeEach` block when testing navigation registration.

**`LayoutComponent` throws exceptions on auth failure** — it does not redirect, it throws `\Exception`. Route middleware (`auth`, `role:...`) on the routes is what handles the redirect; the component's check is a secondary safety net for direct Blade rendering in tests.

**Log file access is restricted to `storage/logs/*.log`.** The `LogsController` sanitises the `file` query parameter with `basename()` and then verifies the resolved path still starts with `storage_path('logs')`. Do not bypass this by constructing paths manually.

## Events Emitted

This domain emits no domain events.

## Listens To

This domain subscribes to no events from other domains.
