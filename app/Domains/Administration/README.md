# Administration Domain

## Purpose and Scope

The Administration domain owns the custom admin panel UI that runs under `/administration`. It provides:

- A shared admin layout (sidebar, navbar) consumed by all admin pages across every domain
- A plugin-based navigation registry that other domains use to register their own admin pages
- A logs viewer for inspecting Laravel log files
- A maintenance controller for clearing application caches
- A general-purpose CSV export utility available to any domain

This domain does **not** own any database tables. It is purely a UI and navigation infrastructure layer. Business-level admin operations (user management, moderation, FAQ, etc.) belong to their respective domains; this domain only wires them into the shared sidebar.

## Key Concepts

### The Admin Navigation Registry

The sidebar is driven by `AdminNavigationRegistry`, a singleton service. Any domain can register navigation groups and pages into it at boot time. The registry builds the navigation tree, filters entries by the current user's roles, and sorts groups and pages by their declared `sort_order`.

Domains register their admin pages in their own `ServiceProvider::boot()` method by resolving `AdminNavigationRegistry` from the container and calling `registerGroup()` and `registerPage()`. The Administration domain seeds the registry with its own pages (maintenance, logs) and also registers all legacy Filament-backed admin links so the sidebar mirrors the Filament sidebar exactly.

### Legacy Filament Links

The site previously used Filament for the entire admin panel. The Administration domain's service provider contains an `addLegacyAdminLinks()` method that injects links to existing Filament pages (users, roles, calendar, config, moderation, FAQ, events) into the registry. This keeps the two panels in sync while the migration away from Filament is in progress. The sidebar link count is verified by tests for each role.

### The Admin Layout Component

The admin layout is exposed as a Blade component `<x-admin::layout>`. It is backed by `LayoutComponent`, which enforces that the user is authenticated and has at least one admin role (`moderator`, `admin`, or `tech_admin`) before rendering. Other admin pages use this component as their outer shell.

### Role-Based Access Control

Three roles exist in the admin context:

| Role | Access |
|------|--------|
| `moderator` | Dashboard; moderation and events pages |
| `admin` | All of the above plus user management, calendar, config, FAQ |
| `tech_admin` | All of the above plus maintenance and logs |

The dashboard route accepts all three roles. Maintenance and logs routes are restricted to `tech_admin` only.

### Logs Viewer

The logs viewer reads files from `storage/logs/*.log`. It displays the last 1 000 lines of the selected file (read efficiently from the file tail without loading the entire file into memory) and offers a download link for each file. Path traversal is prevented by sanitising the file parameter with `basename()`.

### CSV Export Utility

`ExportCsv::streamFromQuery()` is a stateless utility exposed in `Public/Support/` for use by any domain that needs to stream a CSV download from an Eloquent query. It uses a cursor to avoid loading large result sets into memory and writes a UTF-8 BOM for Excel compatibility.

## Architecture Decisions

**`AdminNavigationRegistry` is a singleton** — registered once at boot, populated by all domain service providers, then read by the sidebar component on each request. This avoids re-resolving and re-registering on every render, and allows any domain to contribute to the sidebar without depending on Administration's internals.

**The dashboard link is hardcoded in the sidebar template** — it is not registered through the registry because it is always present and always first, regardless of role. Adding it to the registry would require special handling to pin it at position zero.

**`AdminRegistryTarget` supports both route names and raw URLs** — legacy Filament pages use raw `/admin/...` URLs while new custom pages use named routes. The value object encapsulates this distinction so the sidebar template does not need to know which kind it is handling.

**`ExportCsv` lives in `Public/Support/`** — making it accessible to other domains without them reaching into Administration's Private code. It has no dependency on any Administration-specific model or service.

## Cross-Domain Delegation

| Concern | Handled by |
|---------|-----------|
| User authentication and roles | Auth domain (`Roles` constants, `hasRole()`) |
| Business admin operations (users, moderation, FAQ, etc.) | Their respective domains, which register pages via the registry |
| Filament admin panel | Admin domain (`app/Domains/Admin`) |

## Feature Planning

No feature planning document exists for this domain under `docs/Feature_Planning/`.
