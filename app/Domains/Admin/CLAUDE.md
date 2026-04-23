# Admin Domain — Agent Instructions

- README: [app/Domains/Admin/README.md](README.md)

## Public API

This domain exposes no Public API. It is a consumer of other domains, not a provider.

## Events

This domain emits no domain events and registers no listeners. It is read-only with respect to the event bus.

## Non-Obvious Invariants

**Two access-control layers must both be correct.** The Filament panel enforces `CheckRole` middleware at panel entry (allows `admin`, `tech-admin`, `moderator`). Each `Resource` additionally overrides `canAccess()` to restrict to a subset of those roles. When adding a new resource or page, both the panel-level middleware and the `canAccess()` override must be set. Missing either layer silently grants or silently blocks access.

**Write actions go through the owning domain's Public API; reads hit the model directly.** Resources query private models directly for Filament's table/filter needs. Any action that mutates state with business logic (approve report, delete calendar activity, toggle feature) must call the owning domain's `PublicApi`, not write to the model directly. Calling the model directly bypasses events and business rules enforced in the service layer.

**Navigation items for external domains must be added in `AdminServiceProvider::panel()`.** News, StaticPage, StoryRef, and Story moderation UIs live in their own domains. Their sidebar entries are `NavigationItem` objects declared in `AdminServiceProvider`, not auto-discovered resources. When a new domain wants a sidebar link in the admin panel without hosting a Filament resource here, add a `NavigationItem` to `AdminServiceProvider::panel()`.

**`InjectFilamentUserName` middleware is required for panel boot.** It is registered in the panel middleware stack in `AdminServiceProvider`. Removing it causes Filament to fail when rendering the user menu because `Auth::user()->name` is null (the Auth domain does not store a name column). It resolves the display name from `ProfilePublicApi`.

**Logout is handled by a 307 redirect.** `FilamentLogoutController` at `POST /admin/logout` does not log the user out directly; it redirects with a 307 (preserving the POST method) to `/logout`, which the Auth domain handles. Do not add session teardown logic here.

**Datetime columns must use the `<time class="js-dt">` pattern.** A Blade partial (`admin::partials.format-dates`) is injected at `panels::body.end` and activates client-side datetime formatting. Any new datetime column must emit `<time class="js-dt" datetime="{ISO string}">` via `.formatStateUsing()` and `.html()`, not Filament's built-in date formatting methods.

**`HasCsvExport` trait is available for any list resource.** Import the trait, call `$this->makeExportCsvAction($columns)` in `getHeaderActions()`, and pass the column map. The method uses `getFilteredTableQuery()` when available so exported data respects active search and filter state.

**`AdminUserSeeder` is idempotent.** It creates `admin@example.com` with `password` only when no user with the `admin` role already exists. It depends on `AuthSeeder` having run first to create the `admin` role.
