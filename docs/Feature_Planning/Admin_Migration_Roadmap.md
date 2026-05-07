# Admin → Custom Admin Migration Roadmap

## Context

We are migrating away from Filament to eliminate the `filament/filament` dependency for Laravel 13. The custom admin system already exists in `app/Domains/Administration/` with layout (`<x-admin::layout>`) and `AdminNavigationRegistry`.

**During migration:** each time a Filament resource is removed, a `NavigationItem` must be added to `AdminServiceProvider::panel()` pointing to the new custom route. Tests verify nav parity between both admin systems. These `NavigationItem`s are all removed together when Filament is fully torn down in Phase 7.

**Reference implementation:** `app/Domains/News/` (controller, views, routes, nav registration, tests).  
**Skill to use:** `/migrate-admin-screen`

---

## Status Legend

- `[ ]` Not started
- `[~]` In progress
- `[x]` Complete

---

## Phase 0 — Infrastructure (done)

- [x] `AdminNavigationRegistry` singleton — `app/Domains/Administration/`
- [x] Admin layout `<x-admin::layout>` — `app/Domains/Administration/`
- [x] Administration domain: dashboard, maintenance, logs
- [x] Nav registered: News, Auth users, Auth promotion requests, Story moderation, StoryRef ×7, Moderation user-management, Config parameters

---

## Phase 1 — Auth Domain

### 1.1 — RoleResource

| | |
|---|---|
| **Target domain** | Auth |
| **Nav group** | `auth` — "Gestion des utilisateurs" |
| **Operations** | List, Create, Edit, Delete |
| **Complexity** | Low |
| **Cross-domain deps** | None |
| **Special cases** | Delete blocked if role assigned to users; users count column (aggregation) |
| **Roles** | ADMIN, TECH_ADMIN |
| **Icons** | Filament: `heroicon-o-shield-check` / Registry: `shield` |

- [x] Controller — `app/Domains/Auth/Private/Controllers/Admin/RoleController.php`
- [x] Views — `app/Domains/Auth/Private/Resources/views/pages/admin/roles/`
- [x] Routes — `app/Domains/Auth/Private/routes.php`
- [x] Nav registration — `AuthServiceProvider::registerAdminNavigation()`
- [x] Tests — `app/Domains/Auth/Tests/Feature/Admin/RoleControllerTest.php`
- [x] Filament resource removed — `app/Domains/Admin/Filament/Resources/Auth/RoleResource.php`
- [x] `NavigationItem` added to `AdminServiceProvider::panel()`

---

### 1.2 — ActivationCodeResource

| | |
|---|---|
| **Target domain** | Auth |
| **Nav group** | `auth` — "Gestion des utilisateurs" |
| **Operations** | List, Create, Delete (no edit — used codes are immutable) |
| **Complexity** | Low–Medium |
| **Cross-domain deps** | Shared `ProfilePublicApi` (sponsor + used-by display names, with in-request caching) |
| **Special cases** | Status badges (active / used / expired); delete only available for unused codes |
| **Roles** | ADMIN, TECH_ADMIN |
| **Icons** | Filament: `heroicon-o-key` / Registry: `key` |

- [ ] Controller — `app/Domains/Auth/Private/Controllers/Admin/ActivationCodeController.php`
- [ ] Views — `app/Domains/Auth/Private/Resources/views/pages/admin/activation-codes/`
- [ ] Routes — `app/Domains/Auth/Private/routes.php`
- [ ] Nav registration — `AuthServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/Auth/Tests/Feature/Admin/ActivationCodeControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/Auth/ActivationCodeResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

## Phase 2 — Moderation Domain

### 2.1 — ModerationReasonResource

| | |
|---|---|
| **Target domain** | Moderation |
| **Nav group** | `moderation` |
| **Operations** | List, Create, Edit, Delete |
| **Complexity** | Medium |
| **Cross-domain deps** | None — `ModerationRegistry` is internal to Moderation |
| **Special cases** | Drag-reorder by `sort_order` within topic (see StoryRef pattern); delete blocked if reason used in reports; topic badge |
| **Roles** | ADMIN, TECH_ADMIN, MODERATOR |
| **Icons** | Filament: `heroicon-o-flag` / Registry: `flag` |

- [ ] Controller — `app/Domains/Moderation/Private/Controllers/Admin/ModerationReasonController.php`
- [ ] Views — `app/Domains/Moderation/Private/Resources/views/pages/admin/moderation-reasons/`
- [ ] Routes — `app/Domains/Moderation/Private/routes.php`
- [ ] Nav registration — `ModerationServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/Moderation/Tests/Feature/Admin/ModerationReasonControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/Moderation/ModerationReasonResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

### 2.2 — ModerationReportResource

| | |
|---|---|
| **Target domain** | Moderation |
| **Nav group** | `moderation` |
| **Operations** | List, Review (show + action), Delete |
| **Complexity** | Medium–High |
| **Cross-domain deps** | Shared `ProfilePublicApi` (reporter display name) |
| **Special cases** | Approve / Dismiss / Delete workflow actions with conditional visibility; snapshot rendering via `ModerationRegistry::getFormatter()`; default filter to pending; status badges |
| **Roles** | ADMIN, TECH_ADMIN, MODERATOR |
| **Icons** | Filament: `heroicon-o-flag` / Registry: `report` |

- [ ] Controller — `app/Domains/Moderation/Private/Controllers/Admin/ModerationReportController.php`
- [ ] Views — `app/Domains/Moderation/Private/Resources/views/pages/admin/moderation-reports/`
- [ ] Routes — `app/Domains/Moderation/Private/routes.php`
- [ ] Nav registration — `ModerationServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/Moderation/Tests/Feature/Admin/ModerationReportControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/Moderation/ModerationReportResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

## Phase 3 — Events Domain

### 3.1 — DomainEventResource

| | |
|---|---|
| **Target domain** | Events |
| **Nav group** | `technical` |
| **Operations** | List, Show (read-only detail), Bulk Delete |
| **Complexity** | Medium |
| **Cross-domain deps** | Shared `ProfilePublicApi` (display names) |
| **Special cases** | Read-only audit log; event summary via `DomainEventFactory`; filters (name debounce, user_id, date range); default sort by `occurred_at` DESC |
| **Roles** | ADMIN, TECH_ADMIN, MODERATOR |
| **Icons** | Filament: `heroicon-o-bolt` / Registry: `bolt` |

- [ ] Controller — `app/Domains/Events/Private/Controllers/Admin/DomainEventController.php`
- [ ] Views — `app/Domains/Events/Private/Resources/views/pages/admin/domain-events/`
- [ ] Routes — `app/Domains/Events/Private/routes.php`
- [ ] Nav registration — `EventsServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/Events/Tests/Feature/Admin/DomainEventControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/Event/DomainEventResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

## Phase 4 — Config Domain

### 4.1 — FeatureToggleResource

| | |
|---|---|
| **Target domain** | Config |
| **Nav group** | `config` (already exists) |
| **Operations** | List, Create (tech-admin only), Set access (on/off/role_based), Delete (tech-admin only) |
| **Complexity** | Medium |
| **Cross-domain deps** | Auth `AuthPublicApi` (role list for role_based access) |
| **Special cases** | Two-tier visibility (tech-admin sees all; others see only `all_admins` toggles); descriptions from translations (`{domain}::config.feature_toggles.{name}`); color-coded access badges; no pagination |
| **Roles** | ADMIN, TECH_ADMIN (list); TECH_ADMIN only (create/delete) |
| **Icons** | Filament: `heroicon-o-adjustments-vertical` / Registry: `toggle_on` |

- [ ] Controller — `app/Domains/Config/Private/Controllers/Admin/FeatureToggleController.php`
- [ ] Views — `app/Domains/Config/Private/Resources/views/pages/admin/feature-toggles/`
- [ ] Routes — `app/Domains/Config/Private/routes.php`
- [ ] Nav registration — `ConfigServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/Config/Tests/Feature/Admin/FeatureToggleControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/Config/FeatureToggleResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

## Phase 5 — FAQ Domain

### 5.1 — FaqCategoryResource

| | |
|---|---|
| **Target domain** | FAQ |
| **Nav group** | `faq` (new group — create it) |
| **Operations** | List, Create, Edit, Delete |
| **Complexity** | Medium |
| **Cross-domain deps** | None |
| **Special cases** | Drag-reorder via `sort_order` (see StoryRef pattern); auto slug generation; question count aggregation; active toggle |
| **Roles** | ADMIN, TECH_ADMIN |
| **Icons** | Filament: `heroicon-o-folder` / Registry: `folder` |

- [ ] Controller — `app/Domains/FAQ/Private/Controllers/Admin/FaqCategoryController.php`
- [ ] Views — `app/Domains/FAQ/Private/Resources/views/pages/admin/faq-categories/`
- [ ] Routes — `app/Domains/FAQ/Private/routes.php`
- [ ] Nav registration — `FaqServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/FAQ/Tests/Feature/Admin/FaqCategoryControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/FAQ/FaqCategoryResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

### 5.2 — FaqQuestionResource

| | |
|---|---|
| **Target domain** | FAQ |
| **Nav group** | `faq` |
| **Operations** | List, Create, Edit, Delete, Bulk activate / deactivate / delete |
| **Complexity** | High |
| **Cross-domain deps** | Shared `ImageService` (upload with responsive variants at 400/800px; `deleteWithVariants()` on removal) |
| **Special cases** | Rich editor; image upload/removal with variant cleanup; category filter; drag-reorder by `sort_order`; bulk actions |
| **Roles** | ADMIN, TECH_ADMIN |
| **Icons** | Filament: `heroicon-o-question-mark-circle` / Registry: `help` |

- [ ] Controller — `app/Domains/FAQ/Private/Controllers/Admin/FaqQuestionController.php`
- [ ] Views — `app/Domains/FAQ/Private/Resources/views/pages/admin/faq-questions/`
- [ ] Routes — `app/Domains/FAQ/Private/routes.php`
- [ ] Nav registration — `FaqServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/FAQ/Tests/Feature/Admin/FaqQuestionControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/FAQ/FaqQuestionResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

## Phase 6 — Calendar Domain

### 6.1 — ActivitiesResource

| | |
|---|---|
| **Target domain** | Calendar |
| **Nav group** | `calendar` (check if already exists in CalendarServiceProvider) |
| **Operations** | List, Create, Edit, Delete |
| **Complexity** | High (most complex) |
| **Cross-domain deps** | Auth `AuthPublicApi` (role restriction options); Shared `HtmlLinkUtils` (link processing); Shared `ImageService` (tmp → activities/ move; removal flag) |
| **Special cases** | All mutations go through `CalendarPublicApi` with DTOs (`ActivityToCreateDto`, `ActivityToUpdateDto`); state machine (draft / preview / active / ended / archived); multiple datetime fields; role restrictions; subscription toggle; max participants; rich editor with external link handling |
| **Roles** | ADMIN, TECH_ADMIN |
| **Icons** | Filament: `heroicon-o-calendar` / Registry: `calendar_month` |

- [ ] Controller — `app/Domains/Calendar/Private/Controllers/Admin/ActivityController.php`
- [ ] Views — `app/Domains/Calendar/Private/Resources/views/pages/admin/activities/`
- [ ] Routes — `app/Domains/Calendar/Private/routes.php`
- [ ] Nav registration — `CalendarServiceProvider::registerAdminNavigation()`
- [ ] Tests — `app/Domains/Calendar/Tests/Feature/Admin/ActivityControllerTest.php`
- [ ] Filament resource removed — `app/Domains/Admin/Filament/Resources/Calendar/ActivitiesResource.php`
- [ ] `NavigationItem` added to `AdminServiceProvider::panel()`

---

## Phase 7 — Final Cleanup (after all resources migrated)

- [ ] Delete `app/Domains/Admin/Filament/Pages/BackHome.php` — sidebar already has "back to site" link
- [ ] Remove all `NavigationItem` entries from `AdminServiceProvider::panel()` — now fully managed by domain service providers
- [ ] Move `LogDownloadController` route from `AdminServiceProvider::boot()` to `AdministrationServiceProvider`
- [ ] Delete `FilamentLogoutController` — no longer needed
- [ ] Delete `InjectFilamentUserName` middleware
- [ ] Remove `filament/filament` from `composer.json` (`./vendor/bin/sail composer remove filament/filament`)
- [ ] Delete `app/Domains/Admin/` directory
- [ ] Update `AGENTS.md` domain registry (remove Admin row, update Administration row)
- [ ] Run full test suite: `./vendor/bin/sail artisan test:parallel`
- [ ] Run deptrac: `./vendor/bin/sail composer deptrac`
