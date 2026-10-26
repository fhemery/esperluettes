# Collapse Toute l'oeuvre fanart area by default

**Status:** DONE — 2026-10-26 · **Domain(s):** `Fanart`

## What it does

On the story page Fanarts tab, the « Toute l'œuvre » group (`work`: fanart with
no chapter) now starts collapsed, like unread chapter groups. « Montrer tout »
expands and lazily loads it; « Cacher tout » collapses it again.

## Key behaviour

- Applies to every viewer who sees the tab, authors included (not read-state dependent).
- Nothing is fetched until first expansion (existing lazy fragment path).
- No spoiler notice on the `work` group (A3): the notice is shown only when `collapsed` and key is not `work`.
- Chapter groups unchanged. Profile tab groups untouched (`FanartProfileTabTest` still asserts `false`).

## Where the code lives

| Concern | Path (`app/Domains/Fanart/`) |
|---------|------------------------------|
| `work` group `collapsed => true` | `Private/Services/FanartService.php::tabSummary` |
| Spoiler notice skipped for `work` | `Private/Resources/views/fragments/tab.blade.php` |
| Tests | `Tests/Feature/FanartFragmentsTest.php` |

## Decisions

- A1 everyone incl. authors; A2 « Fanart tab » = story tab only; A3 no spoiler notice on `work`. All reversible.

## Not done

- Non-goals: profile-tab groups, a per-user setting, remembering open state, changing chapter-group rules.
- E2E: temporary spec `fanart-toute-loeuvre-collapsed.spec.ts` deleted, not promoted. The older `fanart-artwork-module.spec.ts` had stale expectations (work group assumed open) fixed to click « Montrer tout » first; it is left in `features/` for its own task.
- Drift: CONTEXT said button « Tout afficher »; the real labels are « Montrer tout » / « Cacher tout ».
