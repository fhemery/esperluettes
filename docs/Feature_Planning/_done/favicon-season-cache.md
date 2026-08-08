# Favicon — stays the same when season changes

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** `Shared`

## What it does

Favicon `<link>` hrefs in the shared layout head append `?theme=<resolved season>` so a calendar or preference-driven season change produces a new URL and browsers stop showing a stale cached icon. Replaces the previous hardcoded `?v=20260425`.

## Key behaviour

- Same favicon behaviour for every role; only the resolved `Theme` differs.
- Query is appended **outside** `Theme::asset()` — pass a clean path into `asset()`.
- Full page reload required; no mid-session JS favicon swap.
- Root `public/favicon.ico` (autumn copy) is untouched — browsers that ignore HTML links may still show it.

## Where the code lives

| Concern | Path |
|---------|------|
| Head partial | `app/Domains/Shared/Resources/views/layouts/partials/head.blade.php` |
| Theme helper | `app/Domains/Shared/Contracts/Theme.php` (`asset()`) |
| Tests | `app/Domains/Shared/Tests/Feature/FaviconThemeQueryTest.php` |

## Extension points used

None.

## Decisions worth remembering

- Cache-bust with `?theme=<season>`, not a static version date.
- Favicons only — logos / CSS theme images unchanged.
- Leave root `public/favicon.*` alone unless users still report wrong icons after HTML is correct.

## Not done

- No change to root `/favicon.ico` fallbacks.
- No cache-bust on logos / backgrounds / ribbons.
- No e2e: HTML contract is covered by the Shared feature test.
