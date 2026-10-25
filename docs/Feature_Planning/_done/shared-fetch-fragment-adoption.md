# Shared — adopt fetchFragment in other domains' fragment loaders

**Status:** DONE — 2026-10-25 · **Domain(s):** `Shared`, `Settings`, `Story`, `Moderation`, `Profile`

## What it does

Four lazily loaded HTML widgets used a raw `fetch` and injected whatever came
back, so a user with reset CGU or an expired session got a whole page injected
in the widget. They now call Shared's `window.fetchFragment(url, options?)`
(`Promise<string|null>`): redirect navigates the window, 401 / 419 reloads, any
other failure returns `null`, and the widget injects nothing (silent, no new
error text). No PHP, route, i18n or deptrac change.

## Key behaviour

- Adopted widgets are all behind `auth` and never rendered for guests, so the 401/419 reload cannot loop.
- On `null`: Settings keeps the current tab content and does not push the URL; Story chapters stays closed with loading cleared; Moderation search and Profile user search empty their results (Profile also closes the dropdown).
- Previously Moderation/Profile search injected the error body on failure.

## Where the code lives

| Concern | Path |
|---------|------|
| Settings tabs | `app/Domains/Settings/Private/Resources/views/pages/index.blade.php` |
| Story admin moderation chapters | `app/Domains/Story/Private/Resources/views/pages/admin/moderation/index.blade.php` |
| Moderation user-management search (`onSearchInput` only) | `app/Domains/Moderation/Private/Resources/views/pages/admin/user-management.blade.php` |
| Profile user search | `app/Domains/Profile/Private/Resources/views/components/user-search-input.blade.php` |
| Helper (unchanged) | Shared `fragment-fetch.js`, covered by `fragment-fetch.test.js` |
| Docs | `app/Domains/Shared/AGENTS.md` (Alpine traps: lists the deliberate raw-`fetch` loaders) |

## Decisions worth remembering

Deliberately left on raw `fetch` (do not "complete" the list):
- Comment lazy list, header search dropdown: public routes, shown to guests, no redirect/401 possible. Flips if those routes gain `auth`/`compliant`.
- Moderation report form (`moderation.js` `loadForm`): button is guest-visible and already redirects to `/login`; a reload would regress that.
- Notification load-more: reads the `X-Has-More` header, which the text-only helper does not expose.
- All JSON / POST / PUT / DELETE calls (ReadList, Quote, Jardino, etc.).

## Not done

- Non-goals: changing `compliant` middleware ([`auth-compliant-xhr-intended`](../BACKLOG.md)), error messages on `null`.
- Pushed to backlog: `notification-load-more-fetch-fragment/` (expose headers or move the flag into markup).
- E2E: VERIFY passed in a real browser for all four sites (happy path, CGU redirect, 419), with failures simulated via `page.route`, not a real reset-CGU user. The feature spec and its page object `FragmentLoaders.ts` were deleted at WRAP.
