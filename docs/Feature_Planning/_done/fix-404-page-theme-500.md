# Fix 500 on 404 page when a model-bound route misses

**Status:** DONE — 2026-10-25 · **Domain(s):** `Shared` · Commit `9bab1448`

## What it does

A model-bound route miss (`/profile/nobody-xx`, `/fanarts/9999`), a CSRF
failure or any 500 used to crash the error page with "Undefined variable
`$theme`" (`errors/404.blade.php:38`), turning a 404 into a 500. Error pages and
layouts now get a fallback theme from a view composer. No Blade or middleware
change.

## Root cause

`ResolveThemeMiddleware` (the only place `theme` was shared, via
`View::share`) is **appended** to the `web` group in `bootstrap/app.php`, so it
runs after `SubstituteBindings` and `VerifyCsrfToken`. A `ModelNotFoundException`
or `TokenMismatchException` throws before it; the exception handler renders
the error view with no `theme`. `/stories/zzz-9999` was unaffected because the
story controller resolves the slug itself (the middleware had already run).

## Key behaviour

- Fallback composer in `SharedServiceProvider::boot()`, on
  `shared::errors.*`, `errors.*`, `shared::layouts.*`,
  `shared::components.themed-logo`.
- Fires only when `View::shared('theme') === null` **and** the view has no
  `theme` in its data — never overrides the middleware's theme on normal pages.
- Resolves through `ThemeService::current()`: the signed-in user's preference
  (session/auth already started on a binding miss), else `Theme::seasonal()`.
- Sets it with **`$view->with('theme', …)`, per view — not `View::share`**
  (the plan said share). Sharing from inside a composer would leave the theme
  resolved for whoever triggered it shared for the rest of the process (tests,
  long-lived workers), so a guest's seasonal theme could stick. Layouts are
  rendered as components and do not inherit the parent view's data, which is
  why the layout names are in the composer list.
- Guest `/fanarts/9999` → login redirect, not 404: the route group has `auth`,
  which runs before binding. Expected.

## Where the code lives

| Concern | Path |
|---------|------|
| Fallback composer | `app/Domains/Shared/Providers/SharedServiceProvider.php` (`boot()`, after the navigation composer) |
| Middleware (unchanged) | `app/Domains/Shared/Http/Middleware/ResolveThemeMiddleware.php`, registered in `bootstrap/app.php` |
| Theme resolution | `app/Domains/Shared/Services/ThemeService.php` |
| Views reading `$theme` | `shared::errors.404/419/500`, `shared::layouts.app/guest/partials.head`, `shared::components.themed-logo` |
| Tests | `app/Domains/Shared/Tests/Feature/Error404Test.php` (5 new cases) |

Documented in `app/Domains/Shared/README.md` (Theme system) and
`app/Domains/Shared/AGENTS.md` (non-obvious rules).

## Decisions worth remembering

- Fallback composer rather than moving `ResolveThemeMiddleware` before
  `SubstituteBindings` (assumption #2 in DECISIONS, made without asking;
  reversible). Middleware order left untouched to avoid side effects on every
  route.
- 419 and 500 fixed by the same change, same root cause (assumption #1).
- Per-view `with` instead of `View::share` (assumption #4, BUILD deviation
  from the plan, see above).
- Stayed on branch `fix/misc-small-fixes` (assumption #3, user instruction).

## Counter-intuitive bits

- **Test caveat:** in the Pest process, requests made while building fixtures
  (`alice($this)` logs in…) leave `theme` shared, which masks the bug. The
  preference test resets with `View::share('theme', null)` before the request
  to mimic a fresh one. Any new test of this path must do the same.
- 419/500 are tested by rendering the view directly (`view(...)->render()`):
  tests run with `APP_DEBUG=true`, so a real 500 shows the debug page.
- A new view reading `$theme` outside the composer's list will crash on error
  paths again — add it to the list.

## Verification

VERIFY PASS (2026-10-25, real app, user preference `winter` vs seasonal
autumn): model-bound 404s for guest and confirmed show the right theme and
buttons; unknown URLs and normal pages unchanged; 375 px intact. Favicon not
inspected.

## Not done

- Appearance (dark/light), font and interline are still not resolved on these
  error paths; they fall back to Blade defaults (`light`, `aptos`, `medium`)
  even for a user who chose otherwise. Out of spec (theme only); no backlog row.
- Out of scope (spec §8): the deactivated user's profile page 500 (flagged by
  [`story-deactivated-coauthor-label`](./story-deactivated-coauthor-label.md)
  as tracked here) was **not re-checked** → backlog
  `profile-deactivated-user-500/` (TODO).
- No e2e spec written; nothing to retire.
