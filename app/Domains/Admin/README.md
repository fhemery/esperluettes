# Admin Module

This module contains all the features accessible to Administrators to manage the site

## Technical detail

The module uses [Filament](https://filamentphp.com/) to provide an admin panel.

## Roles

- `admin`: Standard site administrator role with access to the Admin panel.
- `tech-admin`: Technical administrator role. Also has access to the Admin panel. Additional technical-only pages are visible exclusively to users with this role.

Both roles can access the panel, but certain pages (see below) are restricted to `tech-admin`.