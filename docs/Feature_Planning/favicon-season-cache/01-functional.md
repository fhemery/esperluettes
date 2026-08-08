# Favicon — stays the same when season changes — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

When the site season (theme) changes — by calendar rotation or by the user
picking another theme in settings — the browser must show that season's
favicon, not a previously cached one. Today the favicon URL path already
includes the season, but the cache-bust query string is a fixed date, so
browsers keep serving the old icon. Fix: make the favicon URL's query string
carry the current season (e.g. `?theme=spring`).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Season / thème | One of `winter`, `spring`, `summer`, `autumn` (or the auto `seasonal` preference that resolves to one of those) |
| Favicon | Site icon shown in the browser tab / bookmarks; several sizes under each season's theme assets |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Seasonal favicon for the resolved season | — |
| `user` / `user-confirmed` / Moderator / Admin | Favicon for their resolved theme (preference or seasonal) | Change theme via existing settings (unchanged) |

No new permissions. Same favicon behaviour for every role; only the resolved
theme differs.

## 4. Functional requirements

### 4.1 Season change → favicon updates

1. The HTML head always links favicon assets for the **currently resolved**
   theme (existing behaviour).
2. Each favicon `<link>` URL includes a query parameter whose value is the
   resolved season (user request: `?theme=<season>`).
3. After the season/theme changes and the page is reloaded, the browser
   requests the new URL (different query) and displays the matching season's
   icon — not a stale cached copy from another season.
4. Applies to all favicon links already present in the head (`.ico` and PNG
   sizes / apple-touch-icon).

### 4.2 Theme preference (existing)

Unchanged: authenticated users with an explicit theme preference get that
season's favicons; `seasonal` / guests follow the calendar season.

## 5. Lifecycle

N/A — no new persisted data. Favicon URLs are derived at render time from the
already-resolved theme.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — same for all roles |
| Visibility / privacy | N/A — public site chrome |
| Settings | N/A — uses existing `general.theme`; no new setting |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A — static theme files under `public/images/themes/…`, not Media domain |
| Search | N/A |
| i18n | N/A — no new copy |
| Mobile | Same favicon links on all viewports |
| Accessibility | N/A — browser chrome only |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | How to bust favicon cache on season change? | Add query param `?theme=<season>` (user request) |

## 8. Out of scope

- Changing favicon artwork or adding new sizes
- Cache-busting other theme assets (logos, backgrounds, ribbons) — only
  favicons, unless DESIGN finds they share the same helper and a one-line
  change is free (then still only favicons unless assumed otherwise)
- Replacing or removing root `public/favicon.ico` fallback copies
- Vite-hashing theme images
- Forcing live favicon swap without a page reload

## 9. Open questions

None blocking. Non-blocking for DESIGN: whether the static `?v=20260425` is
replaced entirely by `?theme=…` or kept alongside (assumption: replace).
