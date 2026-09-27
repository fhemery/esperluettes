# Admin pages show their flash message twice — request

*Leftover from `feature-toggle-registration` WRAP (see
[`_done/feature-toggle-registration.md`](../_done/feature-toggle-registration.md)).
Not a user-written request yet.*

## What I want

A success/error flash shows once on admin pages, not twice.

## Why

The admin layout (`app/Domains/Administration/Private/Resources/views/layouts/layout.blade.php`)
renders `<x-shared::flash-block />`, and so do ~23 admin page views under
`app/Domains/*/Private/Resources/views/pages/admin/` (e.g. Config feature
toggles index/edit, Auth roles and activation codes, FAQ, Moderation reports).
Every such page prints the same message twice. Pre-existing, seen during the
feature-toggle VERIFY; not caused by it.

## Constraints or ideas I already have

- One owner for the flash: probably the layout, removing the per-page blocks —
  but check which admin pages do *not* use that layout before deleting anything.
