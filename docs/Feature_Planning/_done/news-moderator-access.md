# News — moderator access

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** `News`

## What it does

Moderators join admins and tech-admins at every existing News management gate.
They reach the admin list, create/edit/delete, publish/unpublish, pin and reorder
the homepage carousel, preview drafts on the public show route, and see the
edit affordance on published articles. No schema, notifications, events or new
UI — only the role lists on five enforcement points were widened.

## Key behaviour

- **Guests and confirmed users** — published articles only; 404 on drafts; no edit
  link; admin routes redirect as before.
- **Moderators** — same News admin surface as `admin` and `tech-admin`; pin and
  carousel reorder included (not a subset).
- **Draft preview** — `NewsController::show` allows drafts when
  `AuthPublicApi::hasAnyRole([MODERATOR, ADMIN, TECH_ADMIN])`; middleware does
  not cover this path.
- **Five gates must stay in sync** — route middleware, `NewsRequest::authorize()`,
  two checks in `NewsController::show`, two `AdminNavigationRegistry` entries,
  and the Blade `hasRole()` on the edit link. Canonical order everywhere:
  `moderator, admin, tech-admin`.
- **Comment moderation unchanged** — Comment / Moderation domains already
  include moderators; News comment policy untouched.
- **FAQ and StaticPage admin** — still `admin` + `tech-admin` only.

## Where the code lives

| Concern | Path |
|---------|------|
| Admin route middleware | `app/Domains/News/Private/routes.php` |
| Form authorization | `app/Domains/News/Private/Requests/NewsRequest.php` |
| Draft preview | `app/Domains/News/Private/Controllers/NewsController.php` (`show`) |
| Admin nav registration | `app/Domains/News/Public/Providers/NewsServiceProvider.php` |
| Edit link on show | `app/Domains/News/Private/Resources/views/pages/show.blade.php` |
| Tests | `News/Tests/Feature/Admin/NewsControllerTest.php`, `PinnedNewsControllerTest.php`, `NewsDetailsTest.php` (6 moderator cases) |
| Migrations | none |

## Extension points used

- **AdminNavigationRegistry** — both news-management and pinned-carousel entries
  now pass `[MODERATOR, ADMIN, TECH_ADMIN]`.

## Decisions worth remembering

1. **Widen inline role lists; no `NewsPolicy`** (#7 / A7) — matches Calendar,
   Comment, Moderation; revisit if a sixth gate appears.
2. **No shared Auth “CMS managers” helper** (#8 / A8) — News-only; FAQ/StaticPage
   would need their own follow-on.
3. **Draft preview and show-page edit link included** (#3 / A1) — part of
   “the whole part”, not admin-only leftovers.
4. **Pin/carousel included** (A2) — request named them explicitly.
5. **Existing PATCH publish/unpublish routes left alone** (A9) — pre-existing WAF
   debt; out of scope cleanup.

### Assumptions made without asking (reversible)

| # | Assumption |
|---|------------|
| A1 | Draft preview + edit link are part of moderator management |
| A2 | No subset — pin/carousel included |
| A3 | FAQ / StaticPage stay admins-only |
| A4 | No new notifications or domain events |
| A5 | Comment moderation unchanged |
| A6 | Pattern: `moderator,admin,tech-admin` on routes, FormRequest, nav, draft checks |
| A7 | No NewsPolicy |
| A8 | No shared Auth CMS helper |
| A9 | PATCH publish/unpublish verbs untouched |

## Not done

- **Deliberate non-goals**: FAQ / StaticPage admin for moderators; new
  notifications or events; schema changes; separating content edit from
  pin/carousel; changing guest/user visibility on published news.
- **Admin nav has no PHP feature test** — nav registration is only exercised by
  middleware + route tests; architecture accepted this (plan open item).
- **e2e:** `e2e/tests/features/news-moderator-access.spec.ts` (4 tests, 11/11
  VERIFY) is **deleted** — route auth, draft preview, edit link and denial cases
  are covered by the PHP feature tests above; the spec guarded browser-only
  surfaces (sidebar nav visibility, Alpine reorder buttons, mobile viewport).
  Page objects `NewsAdminListPage`, `PinnedNewsAdminPage` and `AdminSidebar` are
  deleted with it; `E2eNewsSeeder` / `fixtures.ts` e2e-only additions (draft
  row, second pinned article) are reverted.
- No rows pushed back to `docs/Feature_Planning/BACKLOG.md` — FAQ/StaticPage
  moderator parity is parked (functional §9); `_done/admin-menus-e2e.md` covers
  broader admin nav smoke per role.
