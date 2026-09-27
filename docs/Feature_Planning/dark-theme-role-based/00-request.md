# `shared/dark_theme` ignores `role_based` — request

*Leftover from `feature-toggle-registration` (its functional spec §9, open
question; see [`_done/feature-toggle-registration.md`](../_done/feature-toggle-registration.md)).
Not a user-written request yet.*

## What I want

Setting the `shared/dark_theme` toggle to `role_based` should expose the
appearance setting to users holding one of its roles.

## Why

`SharedServiceProvider::boot()` checks `isToggleEnabled('dark_theme', 'shared')`
once, at boot, before any user is known, and only then registers the
« Apparence » settings parameter. With no user, `role_based` always reads as
`off`, so the mode cannot be rolled out to a role first — which is exactly what
`role_based` exists for. Pre-existing; noted, not fixed, in the toggle
registration task.

## Constraints or ideas I already have

- Needs the check to move to request time (e.g. register the parameter
  unconditionally and gate its visibility per user), which depends on what the
  Settings registry supports.
- Also check where the chosen theme is applied (layout), so a user losing the
  role falls back to light.
