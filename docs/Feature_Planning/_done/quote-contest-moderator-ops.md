# Quote contest — moderator category operations

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** `Calendar` (Quote Contest activity)

**Living documentation:**
[`app/Domains/Calendar/Private/Activities/QuoteContest/README.md`](../../../app/Domains/Calendar/Private/Activities/QuoteContest/README.md)
— admin configuration section now states category-route roles.

## What it does

Moderators who could already open a Concours de citations activity edit page
(and save contest dates) can now persist category changes too. The categories
panel was always rendered for them; only the three category CRUD routes still
required `admin` or `tech-admin`, so submits redirected to the dashboard. The
fix widens that route middleware to include `moderator`. No schema, Blade,
services, or content-moderation changes.

## Key behaviour

- **Moderators** — POST store, PUT update, DELETE destroy on categories succeed
  with the same flash and validation as admins (empty-only destroy unchanged).
- **Confirmed users without staff roles** — still redirected to the dashboard on
  all three category routes; no rows created or mutated.
- **Content moderation** — *Résultats* tab and entry delete still use
  `QuoteContestModerationController::ROLES` only; that constant and its role
  set were not changed.
- **Two enforcement points, same three roles** — category writes use route
  middleware (`admin,tech-admin,moderator`, Calendar activity order); moderation
  uses the controller constant (`moderator,admin,tech-admin`). Order is
  cosmetic for `CheckRole`; the sets match.
- **Contest dates** — still saved through the activity form / `CalendarPublicApi`
  (already included moderators); not part of this fix.

## Where the code lives

| Concern | Path |
|---------|------|
| Category route middleware | `app/Domains/Calendar/Private/Activities/QuoteContest/Http/routes.php` |
| Moderation role docblock (clarifies split from category middleware) | `…/Http/Controllers/QuoteContestModerationController.php` |
| Tests | `app/Domains/Calendar/Tests/Feature/QuoteContest/AdminCategoryTest.php` |
| Migrations | none |

## Extension points used

None — existing Quote Contest routes and Calendar activity admin parity only.

## Decisions worth remembering

1. **Widen category route middleware only** (#4) — `SaveCategoryRequest::authorize()`
   stays `true`; no Policy or shared Auth CMS helper (News precedent).
2. **Role middleware order** (#5 / A6) — `admin,tech-admin,moderator` to match
   neighbouring Calendar routes, not News’s `moderator` first.
3. **No Blade change** (A5) — hiding the panel from moderators would contradict
   the goal; the UI was correct, middleware was wrong.
4. **Scope: categories only** (#1–3) — activity CRUD, contest dates, and content
   moderation already allowed moderators; BUILD re-scanned `Http/routes.php` and
   found no other two-role write surfaces.

### Assumptions made without asking (reversible)

| # | Assumption |
|---|------------|
| A1 | Moderators should gain writes, not lose the visible panel |
| A2 | No other quote-contest write routes need changing |
| A3 | Middleware role order is cosmetic |
| A4 | Destroy-non-empty and validation rules unchanged |

## Not done

- **Deliberate non-goals**: FAQ / StaticPage moderator access; redesign of
  config-vs-content permissions; new UI; changing public contest participation.
- **e2e:** `e2e/tests/features/quote-contest-moderator-ops.spec.ts` **deleted**
  — narrow middleware fix; moderator add/edit/delete, blocked delete, admin
  regression, confirmed-user denial, and mobile viewport are covered by
  `AdminCategoryTest` plus VERIFY browser flow during the task.
- **run-app flow:** `.agents/skills/run-app/flows/quote-contest-moderator-categories.mjs`
  **deleted** with the temporary VERIFY spec.
- No rows pushed back to `docs/Feature_Planning/BACKLOG.md`.
