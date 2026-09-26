# Feature toggles declared in service providers — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

A feature toggle should only be usable once it is registered in its domain's
service provider. Today toggles are only rows in `config_feature_toggles`,
created by hand in the admin UI; the code just reads them with
`isToggleEnabled(name, domain)`, and a name that has no row silently returns
`false`. Registration makes the code the declared list of toggles.

The toggles still in the code must be moved to the new rule. After the first
cleanup, only `shared` / `dark_theme` is left.

Also a read-only artisan command (e.g. `config:toggles --json`) that lists the
toggles with their production state (access, roles, and when it last changed —
`updated_at`), so I can run it on the VPS and paste its output.

Finally, readjust the `cleanup-feature-flags` skill to the new rule: inventory
from the registrations instead of grepping `isToggleEnabled(` calls, and
production state from the artisan command instead of a paste of the admin page.

## Why

To make the `cleanup-feature-flags` skill exact instead of best-effort. The
skill reports every toggle, whether the code still uses it, and its production
state, so I can decide which ones to remove. A toggle is removed once it has
been ON in production long enough; a ROLE_BASED toggle is not live yet.

## Constraints or ideas I already have

- Order: the `cleanup-feature-flags` skill was written first (against the
  code of the time), and a first cleanup was run with it **before** this
  feature, so no dead toggle gets migrated (`calendar/enabled`,
  `discord/discord_notifications` and the whole Message domain are gone).
- Only feature toggles. Config parameters (`registerParameter`, e.g. Story's
  cover flags, currently in a class named `FeatureToggles`) are here to stay
  and are not part of this.
- A toggle carries no French strings of its own. The admin page still looks
  up an optional `<domain>::config.feature_toggles.<name>` description.

## Explicitly out of scope

- Deleting production rows: done by hand after a deploy (see `release-notes`).
