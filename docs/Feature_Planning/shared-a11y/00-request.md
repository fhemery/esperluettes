# Shared components — two accessibility gaps — request

*Leftover pushed back by `quote-contest/` (assumption A40, BUILD phase 11).*

## What I want

Two one-line fixes in `app/Domains/Shared`, applied once for every domain that
renders them:

1. `<x-shared::tabs>` renders `role="tablist"` on the tab strip, but its panels
   are plain `<div>`s — no `role="tabpanel"`, no `aria-labelledby` tying a panel
   to the tab that controls it. A screen reader announces the tabs and then
   loses the relationship.
2. `<x-shared::confirm-modal>` does not forward `focusable` to the modal it
   wraps, so a confirmation dialog opens without moving focus into it. Visible
   on the admin quote-contest category delete, and on every other consumer.

## Why

The quote-contest a11y pass (phase 11) stopped at the sub-module's own Blade on
purpose: both gaps live in Shared components rendered across the whole app, so
fixing them there is a Shared change with a Shared blast radius — not something
a feature branch should smuggle in.

## Constraints or ideas I already have

- Both fixes are additive; nothing depends on the current markup.
- Check every consumer of `<x-shared::tabs>` before adding `role="tabpanel"`:
  some panels may already be hidden by Alpine rather than removed, which changes
  what a screen reader reaches.
- `focusable` is an existing prop of the underlying modal — the confirm wrapper
  simply never passes it through.

## Explicitly out of scope

- A general a11y audit of the app. This row is the two known gaps.
