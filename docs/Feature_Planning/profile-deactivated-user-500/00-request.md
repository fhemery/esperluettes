# Profile — re-check the deactivated user's profile page for a 500 — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Re-check whether opening a deactivated user's profile page (`/profile/<slug>`)
still returns a 500. If it does, fix it; if it now shows a proper page or 404,
close this task with that observation.

## Why

Reported during `story-deactivated-coauthor-label` and deferred to
`fix-404-page-theme-500`, whose spec put it out of scope (§8). That fix
removed the "Undefined variable `$theme`" crash on model-binding 404s, which
may or may not have been the cause here; the deactivated case was never
re-tested (see `_done/fix-404-page-theme-500.md`).

## Constraints or ideas I already have

None.

## Explicitly out of scope

None.
