# Admin Module

This module contains all the features accessible to Administrators to manage the site

## Technical detail

The module uses [Filament](https://filamentphp.com/) to provide an admin panel.

## Roles

- `admin`: Standard site administrator role with access to the Admin panel.
- `tech-admin`: Technical administrator role. Also has access to the Admin panel. Additional technical-only pages are visible exclusively to users with this role.

Both roles can access the panel, but certain pages (see below) are restricted to `tech-admin`.

## Pages

### System Maintenance

- Navigation label: "Admin site tech."
- Icon: Cog (`heroicon-o-cog-6-tooth`)
- Visibility: Only users with the `tech-admin` role can see and access this page.
- Actions:
  - "Clear all cache": Runs Laravel `optimize:clear` via Artisan to clear config, route, view, and other caches. A success notification is displayed upon completion.

Location: `app/Domains/Admin/Filament/Pages/SystemMaintenance.php`