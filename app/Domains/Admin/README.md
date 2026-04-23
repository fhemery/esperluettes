# Admin Domain

## Purpose and Scope

The Admin domain provides the Filament-based administration panel for the site, accessible at `/admin`. It is the single entry point for all administrative operations. It owns no database tables of its own — all data it surfaces belongs to other domains. Its sole responsibilities are:

- Bootstrapping the Filament panel (authentication, navigation, middleware, layout)
- Hosting Filament `Resource` classes that wrap models owned by other domains
- Providing shared utilities used by those resources (CSV export, datetime rendering)

Administrative UI for certain domains (News, Story moderation, StaticPage, StoryRef) lives inside those domains' own folder structure and is linked into the Filament panel via navigation items declared in the `AdminServiceProvider`, not via `Resource` autodiscovery. Only the resources listed below live inside this domain.

## Roles and Access Control

Three roles can access the panel, enforced by the `CheckRole` middleware applied at panel level:

- `admin` — standard site administrator; access to all operational sections (users, activation codes, roles, FAQ, calendar activities, moderation, feature toggles, config parameters, news, static pages, story reference data)
- `tech-admin` — technical administrator; same access as `admin` plus tech-only sections (maintenance, logs, domain event log) and the ability to create/delete feature toggles and see toggles hidden from regular admins
- `moderator` — limited access: promotion requests, moderation reports, moderation user management, domain event log only

Visibility of individual navigation items and Filament resources is guarded separately from the panel-level middleware; both layers must be correct when adding a new resource or page.

## Filament Resources Hosted in This Domain

Resources are auto-discovered from `app/Domains/Admin/Filament/Resources/`. Each resource reaches into a foreign domain's private model directly.

| Resource | Owning Domain | Access |
|---|---|---|
| `Auth/ActivationCodeResource` | Auth | admin, tech-admin |
| `Auth/RoleResource` | Auth | admin, tech-admin |
| `Moderation/ModerationReasonResource` | Moderation | admin, tech-admin |
| `Moderation/ModerationReportResource` | Moderation | admin, tech-admin, moderator |
| `FAQ/FaqCategoryResource` | FAQ | admin, tech-admin |
| `FAQ/FaqQuestionResource` | FAQ | admin, tech-admin |
| `Calendar/ActivitiesResource` | Calendar | admin, tech-admin |
| `Event/DomainEventResource` | Events | admin, tech-admin, moderator |
| `Config/FeatureToggleResource` | Config | admin, tech-admin |

Admin UI for **News**, **StaticPage**, **StoryRef**, and **Story moderation** is implemented inside those domains and surfaced here only as `NavigationItem` entries pointing to external URLs. This keeps domain logic inside the owning domain while still appearing in the unified sidebar.

## Architecture Decisions

**No own tables.** The Admin domain is intentionally stateless. It reads and writes through the owning domain's models (or their public APIs where available). This keeps the Admin domain from becoming a second source of truth for any entity.

**Direct model access vs. Public API.** Most resources query the owning domain's private models directly (e.g., `Auth\Private\Models\ActivationCode`). This is a pragmatic trade-off: Filament needs query-builder access that Public APIs do not expose. For write actions that carry business logic (delete a calendar activity, approve/reject a moderation report, update a feature toggle), the resources call the owning domain's Public API.

**User display names via `ProfilePublicApi`.** The Auth domain no longer stores a `name` column. Filament requires a non-null user name for the authenticated user during panel boot. The `InjectFilamentUserName` middleware resolves this by calling `ProfilePublicApi::getPublicProfile()` and setting a transient `name` attribute on the User model for the duration of the request. This avoids adding an Auth → Profile dependency at the model level.

**Logout routing.** Filament posts to `/admin/logout` by default but the app's logout logic lives in the Auth domain. `FilamentLogoutController` intercepts the POST and issues a 307 redirect to `/logout` so Auth's controller handles session teardown consistently.

**Datetime rendering.** All datetime columns render as `<time class="js-dt" datetime="...">` HTML elements. A `format-dates` Blade partial is injected at `panels::body.end` to activate client-side formatting via JavaScript. This is why datetime columns use `.html()` and `.formatStateUsing()` rather than Filament's built-in date formatters.

**CSV export.** The `HasCsvExport` trait and `ExportCsv` utility provide a reusable header action for streaming a CSV of any Filament list resource. It respects the table's current search/filter query.

## Cross-Domain Dependencies

This domain calls into (but does not publish anything to) the following domains:

- **Auth** — `CheckRole` middleware, `Roles` constants, `AuthPublicApi` (role listing for Calendar and Config forms), private models `ActivationCode` and `Role`
- **Profile** — `ProfilePublicApi` for display name resolution in middleware, resources, and the domain event log
- **Moderation** — `ModerationPublicApi` (approve/reject/delete report), private models `ModerationReport` and `ModerationReason`, `ModerationRegistry` (topic display names and snapshot formatters)
- **Calendar** — `CalendarPublicApi` (delete activity), private model `Activity`, `CalendarRegistry` (activity type options)
- **Config** — `ConfigPublicApi` (add/update/delete feature toggle), private model `FeatureToggle`
- **Events** — private model `StoredDomainEvent`, `DomainEventFactory`
- **FAQ** — private models `FaqCategory` and `FaqQuestion`

This domain emits no domain events and listens to no events from other domains.
