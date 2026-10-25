# Fix 500 on 404 page when a model-bound route misses — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Theme fallback composer for error views and layouts | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes.

Single phase: the fix is one composer in one provider, plus its tests. No
refactor of existing code, so no checkpoint row; existing consumers (every page
using the layouts) are covered by the existing suite staying green and by the
visual QA below.

## Working agreement

- One phase = one commit. The phase keeps `pnpm run gate` green.
- Failing test first, then the implementation.
- `php` is not on PATH in some shells: run everything through
  `./vendor/bin/sail` (e.g. `./vendor/bin/sail artisan test --filter=Error404Test`).

---

## Phase 1 — Theme fallback composer for error views and layouts

**Goal.** Error pages rendered before `ResolveThemeMiddleware` has run (model-binding
miss, CSRF failure, any 500) get a `theme` instead of crashing on an undefined
`$theme` — architecture §3, §6, tradeoff #1.

**Context (verified during PLAN).**
- `ResolveThemeMiddleware` (`app/Domains/Shared/Http/Middleware/ResolveThemeMiddleware.php`)
  is appended to the `web` group in `bootstrap/app.php`, after
  `SubstituteBindings`, and does `View::share('theme', $themeService->current())`.
  It stays untouched.
- `$theme` is read without fallback by: `shared::errors.404` (line 38),
  `shared::errors.500` (line 35), `shared::errors.419` (only through
  `<x-shared::app-layout>`),
  `shared::layouts.app`, `shared::layouts.guest`,
  `shared::layouts.partials.head`, `shared::components.themed-logo`.
  `shared::components.design-icon` already uses `$theme->value ?? 'autumn'`.
  `resources/views/errors/500.blade.php` just `@include`s `shared::errors.500`.
- Layouts are rendered through components (`AppLayout`, anonymous layouts),
  which do **not** inherit the parent view's data — only shared data. So a
  composer that merely does `$view->with('theme', …)` on the error view would
  not reach the layout. The composer must therefore **`View::share`** the
  fallback (when nothing is shared yet), so every view rendered afterwards in
  the request sees it. This is the implementation of architecture §3's
  "supplies `theme` only when the view has no `theme` yet".
- `ThemeService::current()` resolves `auth()->id()`'s preference, else
  `Theme::seasonal()`. On a model-binding miss inside the `web` group the
  session and auth are already started, so the signed-in user's preference is
  resolvable.
- Existing composer pattern: `View::composer('shared::layouts.partials.navigation', …)`
  in `SharedServiceProvider::boot()` (around line 91).

**Deliverables.**
- `app/Domains/Shared/Providers/SharedServiceProvider.php` — in `boot()`, next to
  the existing navigation composer, register:

  ```php
  View::composer(
      ['shared::errors.*', 'shared::layouts.*', 'shared::components.themed-logo'],
      function () {
          if (View::shared('theme') === null) {
              View::share('theme', app(ThemeService::class)->current());
          }
      }
  );
  ```

  (Adjust the view-name list to whatever names the views actually resolve to —
  the composer must fire for the outermost view rendered on an error path,
  i.e. the error view itself; the layout names are a belt-and-braces addition
  for views rendered outside an error path.) Do not touch `appearance`,
  `userFont`, `userInterline` — their Blade reads already default with `??`.
- `app/Domains/Shared/Tests/Feature/Error404Test.php` — new tests below.
- No Blade change.

**Tests** (in `app/Domains/Shared/Tests/Feature/Error404Test.php`, written first
and run to see them fail with the 500 / undefined `$theme` before the fix):
- `it('shows the 404 page to a guest on a missing model-bound profile')` —
  guest `GET /profile/nobody-xx` → `assertNotFound()`, sees
  `shared::errors.actions.back_home`, and the seasonal theme's 404 image path
  `images/themes/{Theme::seasonal()->value}/errors/404.png`.
- `it('shows the 404 page to a confirmed user on a missing fanart')` —
  `alice($this)` acting, `GET /fanarts/9999` → `assertNotFound()`, sees
  `shared::errors.actions.go_to_dashboard`.
- `it('uses the signed-in user theme preference on a model-bound 404')` —
  pick a `Theme` case different from `Theme::seasonal()`, set it with
  `app(SettingsPublicApi::class)->setValue($user->id, SharedServiceProvider::TAB_GENERAL, SharedServiceProvider::KEY_THEME, $case->value)`,
  `GET /profile/nobody-xx` → 404 and `assertSee("images/themes/{$case->value}/errors/404.png")`.
- `it('renders the 419 page without a resolved theme')` — render the view
  directly, outside any middleware: `$this->view('shared::errors.419')` (or
  `view(...)->render()`), assert it renders and contains the seasonal theme
  value. Cheap reproduction of the CSRF-before-theme path.
- `it('renders the 500 page without a resolved theme')` — same, for
  `shared::errors.500`. (Tests run with `APP_DEBUG=true`, so a real 500
  shows the debug page; rendering the view directly is the cheap check.)
- Existing `Error404Test` cases (unknown URL, guest and authenticated) stay green.

**Acceptance.**
- ✅ Before the fix, the model-bound 404 tests fail (500 / `Undefined variable $theme`); after it, they pass.
- ✅ Guest `GET /profile/nobody-xx` → 404 with the error page content.
- ✅ Confirmed user `GET /fanarts/9999` → 404 with the dashboard button.
- ✅ A signed-in user with a non-seasonal theme preference sees that theme's 404 image.
- ✅ `shared::errors.419` and `shared::errors.500` render with no theme shared beforehand.
- ✅ `ResolveThemeMiddleware` and `bootstrap/app.php` unchanged.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. Run in the real app (`run-app`).

| Surface | Check | OK? |
|---------|-------|-----|
| `/profile/nobody-xx`, guest | 404 page (not 500), seasonal theme illustration, Back / Home / Login buttons | |
| `/profile/nobody-xx`, confirmed user with a non-seasonal theme preference | 404 page in the user's chosen theme (logo, favicon, illustration), Back / Dashboard buttons | |
| `/fanarts/9999`, guest | redirected to login (route is behind `auth`, which runs before binding) — not a 500 | |
| `/fanarts/9999`, confirmed user | 404 page in the user's theme, Back / Dashboard buttons | |
| `/totally-unknown-page`, guest and confirmed | unchanged 404 page (regression check) | |
| any normal page (home, a story), confirmed user with a theme preference | theme unchanged (regression check: the composer must not override the middleware's theme) | |
| `/profile/nobody-xx`, mobile 375 px | 404 page layout intact | |

**VERIFY result (2026-10-25, HEAD 9bab1448) — PASS.** Driven with the `run-app`
driver against the e2e instance (`localhost:8081`), temporary flow (deleted), assets rebuilt.
Confirmed user's theme preference set to `winter` (seasonal today = autumn).

- ✅ `/profile/nobody-xx` guest: HTTP 404, error page, autumn images, all loaded.
- ✅ `/profile/nobody-xx` confirmed: HTTP 404, winter logo/illustration, Back + Dashboard buttons.
- ✅ `/fanarts/9999` guest: redirected to `/login`, no 500.
- ✅ `/fanarts/9999` confirmed: HTTP 404, winter theme, Back + Dashboard buttons.
- ✅ `/totally-unknown-page` guest (autumn) and confirmed (winter): 404 unchanged.
- ✅ Normal page (CGU gate, first page after login) still winter for the confirmed user.
- ✅ `/profile/nobody-xx` at 375 px, guest and confirmed: no horizontal overflow.
- Not tested: favicon (not inspected), dark appearance/font on error pages (known open item), deactivated-user profile.

## Open items

- **Phase 1** — `/fanarts/9999` for a guest: the route group carries `auth`, so
  a guest is redirected to login before binding runs; the guest 404 test uses
  `/profile/nobody-xx` (public route) instead. The request's "as guest" for
  `/fanarts/9999` therefore expects a login redirect, not a 404.
- **Phase 1** — On the error path the user's `appearance` (dark/light), font and
  interline are not resolved either; they fall back to Blade defaults (`light`,
  `aptos`, `medium`). Out of the spec (theme only); report rather than fix
  unless trivially covered by the same composer.
- **Phase 1** — Out of scope per spec §8: if a deactivated user's profile still
  500s after this fix, report it as a separate cause.
